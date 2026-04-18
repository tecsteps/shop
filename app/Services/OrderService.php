<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderFulfilled;
use App\Events\OrderPaid;
use App\Exceptions\PaymentFailedException;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Store;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected PaymentProvider $payments,
        protected InventoryService $inventory,
        protected DiscountService $discounts,
        protected CheckoutService $checkouts,
    ) {}

    /**
     * @param  array<string, mixed>  $paymentDetails
     */
    public function createFromCheckout(Checkout $checkout, array $paymentDetails = []): Order
    {
        $existing = Order::query()->where('checkout_id', $checkout->id)->first();
        if ($existing) {
            return $existing;
        }

        $method = PaymentMethod::from((string) $checkout->payment_method);

        $result = $this->payments->charge($checkout, $method, $paymentDetails);

        if (! $result->success) {
            $this->releaseReservedInventory($checkout);
            throw new PaymentFailedException((string) $result->errorCode);
        }

        return DB::transaction(function () use ($checkout, $method, $result): Order {
            $store = $checkout->store()->first();
            $cart = $checkout->cart()->with('lines.variant.product')->first();
            $totals = $checkout->totals_json ?? [];

            $orderStatus = match ($method) {
                PaymentMethod::CreditCard, PaymentMethod::Paypal => OrderStatus::Paid,
                PaymentMethod::BankTransfer => OrderStatus::Pending,
            };
            $financialStatus = match ($method) {
                PaymentMethod::CreditCard, PaymentMethod::Paypal => FinancialStatus::Paid,
                PaymentMethod::BankTransfer => FinancialStatus::Pending,
            };

            $order = Order::create([
                'store_id' => $store->id,
                'customer_id' => $checkout->customer_id,
                'checkout_id' => $checkout->id,
                'order_number' => $this->generateOrderNumber($store),
                'payment_method' => $method,
                'status' => $orderStatus,
                'financial_status' => $financialStatus,
                'fulfillment_status' => FulfillmentStatus::Unfulfilled,
                'currency' => $cart->currency,
                'subtotal_amount' => (int) ($totals['subtotal'] ?? 0),
                'discount_amount' => (int) ($totals['discount'] ?? 0),
                'shipping_amount' => (int) ($totals['shipping'] ?? 0),
                'tax_amount' => (int) ($totals['tax'] ?? 0),
                'total_amount' => (int) ($totals['total'] ?? 0),
                'email' => $checkout->email,
                'shipping_address_json' => $checkout->shipping_address_json,
                'billing_address_json' => $checkout->billing_address_json,
                'placed_at' => now(),
            ]);

            foreach ($cart->lines as $line) {
                OrderLine::create([
                    'order_id' => $order->id,
                    'product_id' => $line->variant?->product?->id,
                    'variant_id' => $line->variant?->id,
                    'title_snapshot' => $line->variant?->product?->title ?? 'Item',
                    'sku_snapshot' => $line->variant?->sku,
                    'quantity' => $line->quantity,
                    'unit_price_amount' => $line->unit_price_amount,
                    'total_amount' => $line->line_total_amount,
                    'tax_lines_json' => [],
                    'discount_allocations_json' => [],
                ]);
            }

            Payment::create([
                'order_id' => $order->id,
                'provider' => 'mock',
                'method' => $method,
                'provider_payment_id' => $result->providerPaymentId,
                'status' => $result->status,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'raw_json_encrypted' => ! empty($result->raw) ? Crypt::encrypt($result->raw) : null,
                'created_at' => now(),
            ]);

            if ($method !== PaymentMethod::BankTransfer) {
                $this->commitReservedInventory($checkout);
            }

            if ($checkout->discount_code) {
                $discount = Discount::query()
                    ->where('store_id', $store->id)
                    ->whereRaw('LOWER(code) = ?', [mb_strtolower($checkout->discount_code)])
                    ->first();
                if ($discount) {
                    $this->discounts->incrementUsage($discount);
                }
            }

            $cart->status = CartStatus::Converted;
            $cart->save();

            $checkout->status = CheckoutStatus::Completed;
            $checkout->save();

            if ($method !== PaymentMethod::BankTransfer && $this->orderIsAllDigital($order)) {
                $this->autoFulfillDigital($order);
            }

            OrderCreated::dispatch($order);
            if ($financialStatus === FinancialStatus::Paid) {
                OrderPaid::dispatch($order);
            }

            return $order;
        });
    }

    public function confirmBankTransferPayment(Order $order): Order
    {
        if ($order->payment_method !== PaymentMethod::BankTransfer) {
            return $order;
        }

        if ($order->financial_status !== FinancialStatus::Pending) {
            return $order;
        }

        return DB::transaction(function () use ($order): Order {
            $order->financial_status = FinancialStatus::Paid;
            $order->status = OrderStatus::Paid;
            $order->save();

            $payment = $order->payments()->latest('id')->first();
            if ($payment) {
                $payment->status = PaymentStatus::Captured;
                $payment->save();
            }

            if ($order->checkout) {
                $this->commitReservedInventory($order->checkout);
            }

            if ($this->orderIsAllDigital($order)) {
                $this->autoFulfillDigital($order);
            }

            OrderPaid::dispatch($order);

            return $order;
        });
    }

    public function cancel(Order $order, ?string $reason = null): Order
    {
        if ($order->fulfillment_status === FulfillmentStatus::Fulfilled) {
            return $order;
        }

        return DB::transaction(function () use ($order, $reason): Order {
            if ($order->checkout) {
                $this->releaseReservedInventory($order->checkout);
            }

            $payment = $order->payments()->latest('id')->first();
            if ($payment && $payment->status === PaymentStatus::Pending) {
                $payment->status = PaymentStatus::Failed;
                $payment->save();
            }

            $order->status = OrderStatus::Cancelled;
            if ($order->financial_status === FinancialStatus::Pending) {
                $order->financial_status = FinancialStatus::Voided;
            }
            $order->save();

            OrderCancelled::dispatch($order, $reason);

            return $order;
        });
    }

    public function generateOrderNumber(Store $store): string
    {
        $max = Order::query()
            ->where('store_id', $store->id)
            ->max('order_number');

        $next = $max ? ((int) $max + 1) : 1001;

        return (string) $next;
    }

    protected function releaseReservedInventory(Checkout $checkout): void
    {
        $cart = $checkout->cart()->with('lines.variant.inventoryItem')->first();
        if (! $cart) {
            return;
        }

        foreach ($cart->lines as $line) {
            $item = $line->variant?->inventoryItem;
            if ($item) {
                $this->inventory->release($item, $line->quantity);
            }
        }
    }

    protected function commitReservedInventory(Checkout $checkout): void
    {
        $cart = $checkout->cart()->with('lines.variant.inventoryItem')->first();
        if (! $cart) {
            return;
        }

        foreach ($cart->lines as $line) {
            $item = $line->variant?->inventoryItem;
            if ($item) {
                $this->inventory->commit($item, $line->quantity);
            }
        }
    }

    protected function orderIsAllDigital(Order $order): bool
    {
        $order->loadMissing('lines.variant');
        foreach ($order->lines as $line) {
            if ($line->variant && $line->variant->requires_shipping) {
                return false;
            }
        }

        return true;
    }

    protected function autoFulfillDigital(Order $order): void
    {
        $fulfillment = Fulfillment::create([
            'order_id' => $order->id,
            'status' => FulfillmentShipmentStatus::Delivered,
            'shipped_at' => now(),
            'delivered_at' => now(),
            'created_at' => now(),
        ]);

        foreach ($order->lines as $line) {
            $fulfillment->lines()->create([
                'order_line_id' => $line->id,
                'quantity' => $line->quantity,
            ]);
        }

        $order->fulfillment_status = FulfillmentStatus::Fulfilled;
        $order->status = OrderStatus::Fulfilled;
        $order->save();

        OrderFulfilled::dispatch($order);
    }
}
