<?php

namespace App\Services\Orders;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Checkout;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Services\Discounts\DiscountService;
use App\Services\Inventory\InventoryService;
use App\Services\Payments\PaymentProvider;
use App\Services\Pricing\PricingService;
use App\Services\Shipping\ShippingService;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly PricingService $pricingService,
        private readonly ShippingService $shippingService,
        private readonly DiscountService $discountService,
        private readonly PaymentProvider $paymentProvider,
    ) {}

    public function placeFromCheckout(Checkout $checkout, array $paymentDetails): Order
    {
        return DB::transaction(function () use ($checkout, $paymentDetails) {
            $cart = $checkout->cart;
            $cart->load('lines.variant.inventory', 'lines.variant.product');

            if ($cart->lines->isEmpty()) {
                throw new \RuntimeException('Cannot place order for empty cart.');
            }

            $shippingRate = $checkout->shipping_method_id
                ? $this->shippingService->findRate($checkout->shipping_method_id)
                : null;

            $discount = $checkout->discount_code
                ? $this->discountService->findCode(app('current_store'), $checkout->discount_code)
                : null;

            $totals = $this->pricingService->computeTotals($cart, $shippingRate, $discount);
            $store = app('current_store');

            $order = Order::create([
                'store_id' => $store->id,
                'customer_id' => $checkout->customer_id,
                'order_number' => $this->generateOrderNumber(),
                'payment_method' => $checkout->payment_method?->value ?? PaymentMethod::CreditCard->value,
                'status' => OrderStatus::Open->value,
                'financial_status' => FinancialStatus::Pending->value,
                'fulfillment_status' => FulfillmentStatus::Unfulfilled->value,
                'currency' => $totals->currency,
                'subtotal_amount' => $totals->subtotal,
                'discount_amount' => $totals->discount,
                'shipping_amount' => $totals->shipping,
                'tax_amount' => $totals->tax,
                'total_amount' => $totals->total,
                'email' => $checkout->email,
                'billing_address_json' => $checkout->billing_address_json ?? $checkout->shipping_address_json,
                'shipping_address_json' => $checkout->shipping_address_json,
                'placed_at' => now(),
            ]);

            foreach ($cart->lines as $line) {
                OrderLine::create([
                    'order_id' => $order->id,
                    'product_id' => $line->variant->product_id,
                    'variant_id' => $line->variant_id,
                    'title_snapshot' => $line->variant->product->title.' - '.$line->variant->displayTitle(),
                    'sku_snapshot' => $line->variant->sku,
                    'quantity' => $line->quantity,
                    'unit_price_amount' => $line->unit_price_amount,
                    'total_amount' => $line->line_total_amount,
                ]);

                if ($line->variant->inventory) {
                    $this->inventoryService->commit($line->variant->inventory, $line->quantity);
                }
            }

            $method = $checkout->payment_method?->value ?? 'credit_card';
            $result = $this->paymentProvider->charge($order, array_merge($paymentDetails, ['method' => $method]));

            $payment = Payment::create([
                'order_id' => $order->id,
                'provider' => 'mock',
                'method' => $method,
                'provider_payment_id' => $result->providerPaymentId,
                'status' => $result->succeeded
                    ? ($result->status === 'authorized' ? PaymentStatus::Authorized->value : PaymentStatus::Captured->value)
                    : PaymentStatus::Failed->value,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'raw_json_encrypted' => $result->raw,
                'created_at' => now(),
            ]);

            if (! $result->succeeded) {
                $order->update([
                    'status' => OrderStatus::Cancelled->value,
                    'financial_status' => FinancialStatus::Voided->value,
                ]);

                throw new PaymentFailedException($result->error ?? 'Payment failed', $order, $payment);
            }

            if ($result->status === 'authorized') {
                $order->update(['financial_status' => FinancialStatus::Authorized->value]);
            } else {
                $order->update(['financial_status' => FinancialStatus::Paid->value]);
            }

            if ($discount) {
                $this->discountService->markUsed($discount);
            }

            $cart->update(['status' => CartStatus::Converted->value]);
            $checkout->update([
                'status' => CheckoutStatus::Completed->value,
                'totals_json' => $totals->toArray(),
            ]);

            return $order->fresh(['lines', 'payments']);
        });
    }

    public function generateOrderNumber(): string
    {
        $next = (int) (Order::where('store_id', app('current_store')->id)->max('id') ?? 0) + 1;

        return '#'.str_pad((string) (1000 + $next), 4, '0', STR_PAD_LEFT);
    }

    public function cancel(Order $order, ?string $reason = null): Order
    {
        $order->update([
            'status' => OrderStatus::Cancelled->value,
        ]);

        foreach ($order->lines as $line) {
            if ($line->variant && $line->variant->inventory) {
                $this->inventoryService->restock($line->variant->inventory, $line->quantity);
            }
        }

        return $order;
    }
}
