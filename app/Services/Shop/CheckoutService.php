<?php

namespace App\Services\Shop;

use App\Enums\CheckoutStatus;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly ShippingService $shipping,
        private readonly InventoryService $inventory,
        private readonly MockPaymentProvider $payments,
    ) {}

    public function start(Cart $cart): Checkout
    {
        if ($cart->lines()->count() === 0) {
            throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
        }

        return Checkout::query()->firstOrCreate(
            ['cart_id' => $cart->id, 'status' => CheckoutStatus::Started],
            [
                'store_id' => $cart->store_id,
                'customer_id' => $cart->customer_id,
                'email' => auth('customer')->user()?->email,
                'totals_json' => $this->pricing->cartTotals($cart),
            ],
        );
    }

    public function address(Checkout $checkout, string $email, array $address): Checkout
    {
        $rate = $this->shipping->rateForCountry((string) $address['country_code'], $checkout->cart->lines->sum(fn ($line): int => $line->quantity * $line->unit_price_amount));

        if (! $rate) {
            throw ValidationException::withMessages(['shipping' => 'No shipping rate is available for this address.']);
        }

        $checkout->update([
            'status' => CheckoutStatus::Addressed,
            'email' => $email,
            'shipping_address_json' => $address,
            'shipping_rate_id' => $rate->id,
            'totals_json' => $this->pricing->cartTotals($checkout->cart, $rate),
        ]);

        return $checkout->refresh();
    }

    public function payment(Checkout $checkout, string $method): Checkout
    {
        $checkout->update([
            'status' => CheckoutStatus::PaymentSelected,
            'payment_method' => $method,
        ]);

        return $checkout->refresh();
    }

    public function complete(Checkout $checkout, array $paymentPayload = []): Order
    {
        return DB::transaction(function () use ($checkout, $paymentPayload): Order {
            $checkout = Checkout::query()->lockForUpdate()->findOrFail($checkout->id);

            if ($checkout->order_id) {
                return $checkout->order;
            }

            if (! $checkout->email || ! $checkout->shipping_address_json || ! $checkout->shipping_rate_id || ! $checkout->payment_method) {
                throw ValidationException::withMessages(['checkout' => 'Checkout is incomplete.']);
            }

            $cart = $checkout->cart()->with('lines.variant.product')->firstOrFail();
            $rate = ShippingRate::query()->find($checkout->shipping_rate_id);
            $totals = $this->pricing->cartTotals($cart, $rate);
            $payment = $this->payments->charge($checkout->payment_method, $totals['total'], $paymentPayload);

            $order = Order::query()->create([
                'store_id' => $checkout->store_id,
                'customer_id' => $checkout->customer_id,
                'order_number' => $this->nextOrderNumber($checkout->store_id),
                'email' => $checkout->email,
                'status' => $payment['status'] === 'paid' ? 'paid' : 'pending',
                'financial_status' => $payment['status'],
                'currency' => $totals['currency'],
                'subtotal_amount' => $totals['subtotal'],
                'discount_amount' => $totals['discount'],
                'shipping_amount' => $totals['shipping'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'],
                'shipping_address_json' => $checkout->shipping_address_json,
                'timeline_json' => [
                    ['at' => now()->toISOString(), 'message' => 'Order created'],
                    ['at' => now()->toISOString(), 'message' => $payment['status'] === 'paid' ? 'Payment captured' : 'Awaiting bank transfer'],
                ],
            ]);

            foreach ($cart->lines as $line) {
                $order->lines()->create([
                    'product_variant_id' => $line->product_variant_id,
                    'title' => $line->variant->product->title,
                    'sku' => $line->variant->sku,
                    'quantity' => $line->quantity,
                    'unit_price_amount' => $line->unit_price_amount,
                    'total_amount' => $line->quantity * $line->unit_price_amount,
                    'snapshot_json' => $line->snapshot_json,
                ]);

                if ($payment['status'] === 'pending') {
                    $this->inventory->reserve($line->variant, $line->quantity);
                } else {
                    $this->inventory->commit($line->variant, $line->quantity);
                }
            }

            $order->payments()->create([
                'store_id' => $checkout->store_id,
                'provider' => 'mock',
                'method' => $checkout->payment_method,
                'status' => $payment['status'],
                'amount' => $totals['total'],
                'reference' => $payment['reference'],
                'raw_payload_encrypted' => encrypt($payment),
            ]);

            if ($cart->discount_code) {
                Discount::query()->where('code', $cart->discount_code)->increment('used_count');
            }

            $cart->update(['status' => 'converted']);
            $checkout->update([
                'status' => CheckoutStatus::Completed,
                'order_id' => $order->id,
                'totals_json' => $totals,
            ]);

            session()->forget('cart_id');

            return $order->refresh()->load('lines', 'payments');
        });
    }

    public function confirmBankTransfer(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $payment = $order->payments()->where('method', 'bank_transfer')->firstOrFail();

            if ($payment->status === 'paid') {
                return $order;
            }

            foreach ($order->lines()->with('order')->get() as $line) {
                if ($line->product_variant_id) {
                    $variant = ProductVariant::query()->find($line->product_variant_id);
                    $variant && $this->inventory->commit($variant, $line->quantity);
                }
            }

            $payment->update(['status' => 'paid']);
            $order->update([
                'status' => 'paid',
                'financial_status' => 'paid',
                'timeline_json' => array_merge($order->timeline_json ?? [], [
                    ['at' => now()->toISOString(), 'message' => 'Bank transfer confirmed'],
                ]),
            ]);

            return $order->refresh();
        });
    }

    private function nextOrderNumber(int $storeId): string
    {
        $last = Order::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->orderByDesc('id')
            ->value('order_number');

        return (string) max(1001, ((int) $last) + 1);
    }
}

