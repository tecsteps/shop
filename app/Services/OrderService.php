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
use App\Exceptions\BankTransferConfirmationException;
use App\Exceptions\CheckoutStateException;
use App\Exceptions\PaymentFailedException;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private readonly PaymentProvider $payments,
        private readonly InventoryService $inventory,
        private readonly PricingEngine $pricing,
    ) {}

    /**
     * @param  array<string, mixed>  $paymentDetails
     */
    public function createFromCheckout(Checkout $checkout, array $paymentDetails = []): Order
    {
        $outcome = DB::transaction(function () use ($checkout, $paymentDetails): Order|PaymentFailedException {
            $checkout = $this->lockCheckout($checkout);

            $existingOrder = Order::withoutGlobalScopes()
                ->with('lines.variant.inventoryItem', 'payments', 'fulfillments.lines')
                ->where('checkout_id', $checkout->id)
                ->first();

            if ($existingOrder !== null) {
                return $existingOrder;
            }

            if ($checkout->status !== CheckoutStatus::PaymentSelected || $checkout->payment_method === null) {
                throw new CheckoutStateException('The checkout must have a selected payment method before payment.');
            }

            $this->pricing->calculate($checkout);
            $checkout = $this->lockCheckout($checkout);
            $paymentResult = $this->payments->charge($checkout, $checkout->payment_method, $paymentDetails);

            if (! $paymentResult->success) {
                $this->releaseCheckoutInventory($checkout);
                $checkout->forceFill([
                    'status' => CheckoutStatus::ShippingSelected,
                    'payment_method' => null,
                ])->save();

                return new PaymentFailedException(
                    $paymentResult->errorCode ?? 'payment_failed',
                    $paymentResult->message ?? 'Payment failed.',
                );
            }

            $customer = $this->customerForCheckout($checkout);
            $totals = $checkout->totals_json ?? [];
            $order = Order::withoutGlobalScopes()->create([
                'store_id' => $checkout->store_id,
                'customer_id' => $customer?->id,
                'checkout_id' => $checkout->id,
                'order_number' => $this->generateOrderNumber($checkout->store),
                'payment_method' => $checkout->payment_method,
                'status' => $paymentResult->status === PaymentStatus::Captured ? OrderStatus::Paid : OrderStatus::Pending,
                'financial_status' => $paymentResult->status === PaymentStatus::Captured ? FinancialStatus::Paid : FinancialStatus::Pending,
                'fulfillment_status' => FulfillmentStatus::Unfulfilled,
                'currency' => (string) ($totals['currency'] ?? $checkout->cart->currency),
                'subtotal_amount' => (int) ($totals['subtotal'] ?? 0),
                'discount_amount' => (int) ($totals['discount'] ?? 0),
                'shipping_amount' => (int) ($totals['shipping'] ?? 0),
                'tax_amount' => (int) ($totals['tax'] ?? 0),
                'total_amount' => (int) ($totals['total'] ?? 0),
                'email' => $checkout->email,
                'billing_address_json' => $checkout->billing_address_json,
                'shipping_address_json' => $checkout->shipping_address_json,
                'placed_at' => now(),
            ]);

            $this->createLines($order, $checkout);
            $order->payments()->create([
                'provider' => 'mock',
                'method' => $checkout->payment_method,
                'provider_payment_id' => $paymentResult->reference,
                'status' => $paymentResult->status,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'raw_json_encrypted' => $paymentResult->raw,
            ]);

            if ($paymentResult->status === PaymentStatus::Captured) {
                $this->commitOrderInventory($order);
            }

            $checkout->cart->forceFill([
                'customer_id' => $customer?->id,
                'status' => CartStatus::Converted,
            ])->save();

            $checkout->forceFill([
                'customer_id' => $customer?->id,
                'status' => CheckoutStatus::Completed,
                'expires_at' => null,
            ])->save();

            $this->incrementDiscountUsage($checkout);

            OrderCreated::dispatch($order);

            if ($paymentResult->status === PaymentStatus::Captured) {
                OrderPaid::dispatch($order);
                $this->autoFulfillDigitalOrder($order);
            }

            return $order->refresh()->load('lines.variant.inventoryItem', 'payments', 'fulfillments.lines');
        });

        if ($outcome instanceof PaymentFailedException) {
            throw $outcome;
        }

        return $outcome;
    }

    public function generateOrderNumber(Store $store): string
    {
        $prefix = $this->orderNumberPrefix($store);
        $latest = Order::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('order_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->latest('id')
            ->value('order_number');

        $lastNumber = $latest === null
            ? 1000
            : (int) preg_replace('/\D+/', '', $latest);

        return $prefix.($lastNumber + 1);
    }

    public function accessToken(Order $order): string
    {
        return hash_hmac(
            'sha256',
            implode('|', [$order->store_id, $order->id, $order->order_number]),
            (string) config('app.key'),
        );
    }

    public function validAccessToken(Order $order, string $token): bool
    {
        return hash_equals($this->accessToken($order), $token);
    }

    public function confirmBankTransfer(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $order = $this->lockOrder($order);

            if ($order->payment_method !== PaymentMethod::BankTransfer) {
                throw new BankTransferConfirmationException('Only bank transfer orders can be confirmed manually.');
            }

            if ($order->financial_status !== FinancialStatus::Pending) {
                throw new BankTransferConfirmationException('Only pending bank transfer orders can be confirmed.');
            }

            $order->payments()
                ->where('status', PaymentStatus::Pending)
                ->latest('id')
                ->firstOrFail()
                ->forceFill(['status' => PaymentStatus::Captured])
                ->save();

            $order->forceFill([
                'status' => OrderStatus::Paid,
                'financial_status' => FinancialStatus::Paid,
            ])->save();

            $this->commitOrderInventory($order);
            OrderPaid::dispatch($order);
            $this->autoFulfillDigitalOrder($order);

            return $order->refresh()->load('lines.variant.inventoryItem', 'payments', 'fulfillments.lines');
        });
    }

    public function cancel(Order $order, string $reason): void
    {
        DB::transaction(function () use ($order, $reason): void {
            $order = $this->lockOrder($order);

            if ($order->fulfillment_status !== FulfillmentStatus::Unfulfilled) {
                throw new CheckoutStateException('Fulfilled orders cannot be cancelled.');
            }

            if ($order->financial_status === FinancialStatus::Pending) {
                $this->releaseOrderInventory($order);
                $order->payments()->update(['status' => PaymentStatus::Failed->value]);
            } elseif (in_array($order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded], true)) {
                $this->restockOrderInventory($order);
            }

            $order->forceFill([
                'status' => OrderStatus::Cancelled,
                'financial_status' => $order->financial_status === FinancialStatus::Pending
                    ? FinancialStatus::Voided
                    : $order->financial_status,
            ])->save();

            OrderCancelled::dispatch($order, $reason);
        });
    }

    private function lockCheckout(Checkout $checkout): Checkout
    {
        return Checkout::withoutGlobalScopes()
            ->with('store', 'cart.lines.variant.product', 'cart.lines.variant.inventoryItem', 'cart.lines.variant.optionValues.option', 'shippingRate')
            ->whereKey($checkout->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function lockOrder(Order $order): Order
    {
        return Order::withoutGlobalScopes()
            ->with('lines.variant.inventoryItem', 'payments', 'fulfillments.lines')
            ->whereKey($order->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function customerForCheckout(Checkout $checkout): ?Customer
    {
        if ($checkout->email === null) {
            return null;
        }

        $shippingAddress = $checkout->shipping_address_json ?? [];
        $name = trim((string) (($shippingAddress['first_name'] ?? '').' '.($shippingAddress['last_name'] ?? '')));
        $customer = Customer::withoutGlobalScopes()->firstOrCreate(
            [
                'store_id' => $checkout->store_id,
                'email' => Str::lower($checkout->email),
            ],
            [
                'name' => $name !== '' ? $name : null,
                'password_hash' => null,
                'marketing_opt_in' => false,
            ],
        );

        if ($shippingAddress !== [] && ! $customer->addresses()->exists()) {
            CustomerAddress::query()->create([
                'customer_id' => $customer->id,
                'label' => 'Default',
                'address_json' => $shippingAddress,
                'is_default' => true,
            ]);
        }

        return $customer;
    }

    private function createLines(Order $order, Checkout $checkout): void
    {
        $discount = $checkout->discount_code === null
            ? null
            : Discount::withoutGlobalScopes()
                ->where('store_id', $checkout->store_id)
                ->whereRaw('lower(code) = ?', [Str::lower($checkout->discount_code)])
                ->first();

        foreach ($checkout->cart->lines as $line) {
            $variant = $line->variant;
            $product = $variant?->product;
            $discountAllocations = $line->line_discount_amount > 0
                ? [[
                    'discount_id' => $discount?->id,
                    'amount' => $line->line_discount_amount,
                ]]
                : [];

            $order->lines()->create([
                'product_id' => $product?->id,
                'variant_id' => $variant?->id,
                'title_snapshot' => $product?->title ?? 'Unknown item',
                'sku_snapshot' => $variant?->sku,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'total_amount' => $line->line_total_amount,
                'tax_lines_json' => [],
                'discount_allocations_json' => $discountAllocations,
            ]);
        }
    }

    private function incrementDiscountUsage(Checkout $checkout): void
    {
        if ($checkout->discount_code === null) {
            return;
        }

        Discount::withoutGlobalScopes()
            ->where('store_id', $checkout->store_id)
            ->whereRaw('lower(code) = ?', [Str::lower($checkout->discount_code)])
            ->increment('usage_count');
    }

    private function commitOrderInventory(Order $order): void
    {
        foreach ($order->lines as $line) {
            $item = $line->variant?->inventoryItem;

            if ($item !== null) {
                $this->inventory->commit($item, $line->quantity);
            }
        }
    }

    private function releaseCheckoutInventory(Checkout $checkout): void
    {
        foreach ($checkout->cart->lines as $line) {
            $item = $line->variant?->inventoryItem;

            if ($item !== null) {
                $this->inventory->release($item, $line->quantity);
            }
        }
    }

    private function releaseOrderInventory(Order $order): void
    {
        foreach ($order->lines as $line) {
            $item = $line->variant?->inventoryItem;

            if ($item !== null) {
                $this->inventory->release($item, $line->quantity);
            }
        }
    }

    private function restockOrderInventory(Order $order): void
    {
        foreach ($order->lines as $line) {
            $item = $line->variant?->inventoryItem;

            if ($item !== null) {
                $this->inventory->restock($item, $line->quantity);
            }
        }
    }

    private function autoFulfillDigitalOrder(Order $order): void
    {
        $order->loadMissing('lines.variant', 'fulfillments');

        if ($order->lines->isEmpty() || $order->fulfillments->isNotEmpty()) {
            return;
        }

        $allDigital = $order->lines->every(fn ($line): bool => $line->variant?->requires_shipping === false);

        if (! $allDigital) {
            return;
        }

        $fulfillment = $order->fulfillments()->create([
            'status' => FulfillmentShipmentStatus::Delivered,
            'shipped_at' => now(),
            'delivered_at' => now(),
        ]);

        foreach ($order->lines as $line) {
            $fulfillment->lines()->create([
                'order_line_id' => $line->id,
                'quantity' => $line->quantity,
            ]);
        }

        $order->forceFill([
            'status' => OrderStatus::Fulfilled,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
        ])->save();

        OrderFulfilled::dispatch($order);
    }

    private function orderNumberPrefix(Store $store): string
    {
        $settings = StoreSettings::query()->find($store->id)?->settings_json ?? [];

        return (string) ($settings['order_number_prefix'] ?? '#');
    }
}
