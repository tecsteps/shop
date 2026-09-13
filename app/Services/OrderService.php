<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\CheckoutCompleted;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Store;
use App\ValueObjects\PaymentResult;
use Illuminate\Support\Facades\DB;

/**
 * Order creation, numbering, cancellation and bank-transfer
 * confirmation per spec 05 sections 6.2, 10.7, 11.1-11.3.
 */
class OrderService
{
    public function __construct(
        protected PricingEngine $pricing,
        protected InventoryService $inventory,
        protected FulfillmentService $fulfillments,
    ) {}

    public function findByCheckout(Checkout $checkout): ?Order
    {
        return Order::query()->where('checkout_id', $checkout->getKey())->first();
    }

    /**
     * @param  array<string, mixed>  $paymentDetails
     */
    public function createFromCheckout(Checkout $checkout, PaymentResult $result, array $paymentDetails = []): Order
    {
        return DB::transaction(function () use ($checkout, $result, $paymentDetails): Order {
            $locked = Checkout::query()->whereKey($checkout->getKey())->lockForUpdate()->firstOrFail();

            $existing = $this->findByCheckout($locked);

            if ($existing !== null) {
                return $existing;
            }

            if ($locked->status === CheckoutStatus::Completed) {
                throw new InvalidOrderTransitionException('This checkout has already been completed.');
            }

            $pricing = $this->pricing->calculate($locked->refresh());
            $locked = $locked->refresh();

            $method = $locked->payment_method;
            $isInstantCapture = $method !== PaymentMethod::BankTransfer;

            $orderNumber = $this->generateOrderNumber($locked->store);
            $customer = $this->resolveCustomer($locked);

            $order = Order::query()->create([
                'store_id' => $locked->store_id,
                'checkout_id' => $locked->getKey(),
                'customer_id' => $customer?->getKey(),
                'order_number' => $orderNumber,
                'payment_method' => $method,
                'status' => $isInstantCapture ? OrderStatus::Paid : OrderStatus::Pending,
                'financial_status' => $isInstantCapture ? FinancialStatus::Paid : FinancialStatus::Pending,
                'fulfillment_status' => FulfillmentStatus::Unfulfilled,
                'currency' => $pricing->currency,
                'subtotal_amount' => $pricing->subtotal,
                'discount_amount' => $pricing->discount,
                'shipping_amount' => $pricing->shipping,
                'tax_amount' => $pricing->taxTotal,
                'total_amount' => $pricing->total,
                'email' => $locked->email,
                'billing_address_json' => $locked->billing_address_json,
                'shipping_address_json' => $locked->shipping_address_json,
                'placed_at' => now(),
            ]);

            $this->createOrderLines($locked, $order);

            $order->payments()->create([
                'provider' => \App\Enums\PaymentProvider::Mock,
                'method' => $method,
                'provider_payment_id' => $result->referenceId,
                'status' => $isInstantCapture ? PaymentStatus::Captured : PaymentStatus::Pending,
                'amount' => $pricing->total,
                'currency' => $pricing->currency,
                'raw_json_encrypted' => json_encode($this->paymentSnapshot($result, $method, $paymentDetails)),
            ]);

            if ($isInstantCapture) {
                $this->commitInventory($locked);
            }

            $this->incrementDiscountUsage($locked);

            $cart = $locked->cart;
            $cart->status = CartStatus::Converted;
            $cart->save();

            $locked->customer_id = $customer?->getKey() ?? $locked->customer_id;
            $locked->status = CheckoutStatus::Completed;
            $locked->save();

            if ($isInstantCapture) {
                $this->fulfillments->autoFulfillDigital($order->refresh());
            }

            event(new OrderCreated($order->refresh()));
            event(new CheckoutCompleted($locked->refresh(), $order->refresh()));

            return $order->refresh();
        });
    }

    public function generateOrderNumber(Store $store): string
    {
        $settings = $store->storeSetting?->settings_json ?? [];
        $prefix = (string) ($settings['order_number_prefix'] ?? '#');

        $max = 1000;

        foreach (Order::query()->where('store_id', $store->getKey())->pluck('order_number') as $number) {
            $numeric = (int) preg_replace('/\D+/', '', (string) $number);

            if ($numeric > $max) {
                $max = $numeric;
            }
        }

        return $prefix.($max + 1);
    }

    public function confirmBankTransferPayment(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $fresh = $order->refresh();

            if ($fresh->payment_method !== PaymentMethod::BankTransfer) {
                throw new InvalidOrderTransitionException('Only bank transfer orders require manual payment confirmation.');
            }

            if ($fresh->financial_status !== FinancialStatus::Pending) {
                throw new InvalidOrderTransitionException('This payment has already been confirmed.');
            }

            $payment = $fresh->payments()->where('status', PaymentStatus::Pending)->latest('id')->first();

            if ($payment !== null) {
                $payment->status = PaymentStatus::Captured;
                $payment->save();
            }

            $fresh->financial_status = FinancialStatus::Paid;
            $fresh->status = OrderStatus::Paid;
            $fresh->save();

            foreach ($fresh->orderLines()->get() as $line) {
                if ($line->variant_id === null) {
                    continue;
                }

                $item = $this->inventoryItem($fresh->store_id, $line->variant_id);

                if ($item !== null) {
                    $this->inventory->commit($item, $line->quantity);
                }
            }

            $this->fulfillments->autoFulfillDigital($fresh->refresh());

            event(new OrderPaid($fresh->refresh()));

            return $fresh->refresh();
        });
    }

    public function cancel(Order $order, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order): Order {
            $fresh = $order->refresh();

            if (in_array($fresh->status, [OrderStatus::Cancelled, OrderStatus::Refunded, OrderStatus::Fulfilled], true)) {
                throw new InvalidOrderTransitionException('This order cannot be cancelled.');
            }

            if ($fresh->fulfillment_status !== FulfillmentStatus::Unfulfilled) {
                throw new InvalidOrderTransitionException('This order cannot be cancelled because fulfillment has started.');
            }

            if (in_array($fresh->financial_status, [FinancialStatus::Pending, FinancialStatus::Authorized], true)) {
                foreach ($fresh->orderLines()->get() as $line) {
                    if ($line->variant_id === null) {
                        continue;
                    }

                    $item = $this->inventoryItem($fresh->store_id, $line->variant_id);

                    if ($item !== null) {
                        $this->inventory->release($item, $line->quantity);
                    }
                }

                $fresh->financial_status = FinancialStatus::Voided;
                $fresh->payments()->where('status', PaymentStatus::Pending)->update(['status' => PaymentStatus::Failed->value]);
            } elseif ($fresh->financial_status === FinancialStatus::Paid) {
                foreach ($fresh->orderLines()->get() as $line) {
                    if ($line->variant_id === null) {
                        continue;
                    }

                    $item = $this->inventoryItem($fresh->store_id, $line->variant_id);

                    if ($item !== null) {
                        $this->inventory->restock($item, $line->quantity);
                    }
                }

                $fresh->financial_status = FinancialStatus::Refunded;
            } else {
                throw new InvalidOrderTransitionException('This order cannot be cancelled. Use refunds instead.');
            }

            $fresh->status = OrderStatus::Cancelled;
            $fresh->save();

            event(new OrderCancelled($fresh->refresh()));

            return $fresh->refresh();
        });
    }

    protected function createOrderLines(Checkout $checkout, Order $order): void
    {
        $totals = (array) ($checkout->totals_json ?? []);
        $allocations = (array) ($totals['discount_allocations'] ?? []);
        $taxSnapshot = (array) ($checkout->tax_provider_snapshot_json ?? []);
        $taxLines = [];

        foreach ((array) ($taxSnapshot['lines'] ?? []) as $entry) {
            $taxLines[(int) ($entry['line_id'] ?? 0)] = $entry;
        }

        foreach ($checkout->cart->cartLines()->with(['variant.product', 'variant.optionValues'])->orderBy('id')->get() as $line) {
            $variant = $line->variant;
            $product = $variant->product;
            $labels = $variant->optionValues->pluck('value')->all();

            $title = $product->title;

            if ($labels !== []) {
                $title .= ' ('.implode(' / ', $labels).')';
            }

            $taxEntry = $taxLines[$line->getKey()] ?? null;

            $order->orderLines()->create([
                'product_id' => $product->getKey(),
                'variant_id' => $variant->getKey(),
                'title_snapshot' => $title,
                'sku_snapshot' => $variant->sku,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'total_amount' => $line->line_total_amount,
                'tax_lines_json' => $taxEntry !== null ? [[
                    'title' => 'Tax',
                    'rate' => (int) ($taxEntry['rate'] ?? 0),
                    'amount' => (int) ($taxEntry['tax_amount'] ?? 0),
                    'jurisdiction' => $taxEntry['jurisdiction'] ?? null,
                ]] : [],
                'discount_allocations_json' => array_values(array_map(
                    fn (array $allocation): array => [
                        'discount_id' => (int) $allocation['discount_id'],
                        'amount' => (int) $allocation['amount'],
                    ],
                    (array) ($allocations[$line->getKey()] ?? [])
                )),
            ]);
        }
    }

    protected function resolveCustomer(Checkout $checkout): ?Customer
    {
        if ($checkout->customer_id !== null) {
            return Customer::query()->whereKey($checkout->customer_id)->first();
        }

        if ($checkout->email === null || $checkout->email === '') {
            return null;
        }

        $existing = Customer::query()
            ->where('store_id', $checkout->store_id)
            ->where('email', $checkout->email)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return Customer::query()->create([
            'store_id' => $checkout->store_id,
            'email' => $checkout->email,
            'password_hash' => null,
            'name' => null,
            'marketing_opt_in' => false,
        ]);
    }

    protected function commitInventory(Checkout $checkout): void
    {
        foreach ($checkout->cart->cartLines()->get() as $line) {
            $item = $this->inventoryItem($checkout->store_id, $line->variant_id);

            if ($item !== null) {
                $this->inventory->commit($item, $line->quantity);
            }
        }
    }

    protected function incrementDiscountUsage(Checkout $checkout): void
    {
        $totals = (array) ($checkout->totals_json ?? []);
        $ids = [];

        foreach ((array) ($totals['applied_discounts'] ?? []) as $applied) {
            if (isset($applied['discount_id'])) {
                $ids[] = (int) $applied['discount_id'];
            }
        }

        $ids = array_values(array_unique($ids));

        if ($ids !== []) {
            Discount::query()->whereIn('id', $ids)->increment('usage_count');
        }
    }

    protected function inventoryItem(int $storeId, int $variantId): ?InventoryItem
    {
        return InventoryItem::query()
            ->where('store_id', $storeId)
            ->where('variant_id', $variantId)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $paymentDetails
     * @return array<string, mixed>
     */
    protected function paymentSnapshot(PaymentResult $result, ?PaymentMethod $method, array $paymentDetails): array
    {
        $snapshot = [
            'reference_id' => $result->referenceId,
            'status' => $result->status,
            'method' => $method?->value,
        ];

        $cardNumber = preg_replace('/\D+/', '', (string) ($paymentDetails['card_number'] ?? ''));

        if ($cardNumber !== '') {
            $snapshot['card_last4'] = substr($cardNumber, -4);
        }

        return $snapshot;
    }
}
