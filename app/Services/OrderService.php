<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\CartStatus;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Models\Checkout;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private PaymentProvider $paymentProvider,
        private InventoryService $inventoryService,
    ) {}

    /**
     * @param  array<string, mixed>  $paymentDetails
     */
    public function createFromCheckout(Checkout $checkout, array $paymentDetails = []): Order
    {
        return DB::transaction(function () use ($checkout, $paymentDetails) {
            $cart = $checkout->cart()->with('lines.variant.product', 'lines.variant.inventoryItem')->first();
            $totals = $checkout->totals_json;
            $paymentMethod = PaymentMethod::from($checkout->payment_method);

            $order = Order::withoutGlobalScopes()->create([
                'store_id' => $checkout->store_id,
                'customer_id' => $checkout->customer_id,
                'order_number' => $this->generateOrderNumber($checkout->store),
                'email' => $checkout->email,
                'status' => OrderStatus::Pending,
                'financial_status' => FinancialStatus::Pending,
                'fulfillment_status' => FulfillmentStatus::Unfulfilled,
                'currency' => $cart->currency,
                'subtotal' => $totals['subtotal'] ?? 0,
                'discount_amount' => $totals['discount'] ?? 0,
                'shipping_amount' => $totals['shipping'] ?? 0,
                'tax_amount' => $totals['tax_total'] ?? 0,
                'total' => $totals['total'] ?? 0,
                'shipping_address_json' => $checkout->shipping_address_json,
                'billing_address_json' => $checkout->billing_address_json,
                'discount_code' => $checkout->discount_code,
                'payment_method' => $checkout->payment_method,
                'placed_at' => now(),
                'totals_json' => $totals,
            ]);

            foreach ($cart->lines as $line) {
                $variant = $line->variant;
                $product = $variant->product;

                $order->lines()->create([
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'title_snapshot' => $product->title,
                    'sku_snapshot' => $variant->sku,
                    'variant_title_snapshot' => $variant->is_default ? null : $variant->sku,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'subtotal' => $line->subtotal,
                    'total' => $line->total,
                    'requires_shipping' => $variant->requires_shipping,
                ]);

                if ($variant->inventoryItem) {
                    if (in_array($paymentMethod, [PaymentMethod::CreditCard, PaymentMethod::Paypal])) {
                        $this->inventoryService->commit($variant->inventoryItem, $line->quantity);
                    } else {
                        $this->inventoryService->reserve($variant->inventoryItem, $line->quantity);
                    }
                }
            }

            // Process payment
            $paymentResult = $this->paymentProvider->charge($checkout, $paymentMethod, $paymentDetails);

            $payment = $order->payments()->create([
                'method' => $paymentMethod,
                'provider' => 'mock',
                'provider_payment_id' => $paymentResult->providerPaymentId,
                'amount' => $order->total,
                'currency' => $order->currency,
                'status' => $paymentResult->success
                    ? PaymentStatus::from($paymentResult->status)
                    : PaymentStatus::Failed,
                'error_code' => $paymentResult->errorCode,
                'error_message' => $paymentResult->errorMessage,
                'captured_at' => $paymentResult->success && $paymentResult->status === 'captured' ? now() : null,
            ]);

            if (! $paymentResult->success) {
                throw new \App\Exceptions\PaymentFailedException(
                    $paymentResult->errorMessage ?? 'Payment failed.',
                );
            }

            if ($paymentResult->status === 'captured') {
                $order->update([
                    'status' => OrderStatus::Paid,
                    'financial_status' => FinancialStatus::Paid,
                ]);
            }

            $cart->update(['status' => CartStatus::Converted]);
            $checkout->update([
                'status' => \App\Enums\CheckoutStatus::Completed,
                'completed_at' => now(),
            ]);

            event(new OrderCreated($order));

            return $order;
        });
    }

    public function generateOrderNumber(Store $store): string
    {
        $lastOrder = Order::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if (! $lastOrder) {
            return '#1001';
        }

        $lastNumber = (int) str_replace('#', '', $lastOrder->order_number);

        return '#'.($lastNumber + 1);
    }

    public function cancel(Order $order, string $reason): void
    {
        if ($order->fulfillment_status !== FulfillmentStatus::Unfulfilled) {
            throw new \App\Exceptions\FulfillmentGuardException('Cannot cancel a fulfilled order.');
        }

        $order->update([
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ]);

        // Release reserved inventory
        foreach ($order->lines()->with('variant.inventoryItem')->get() as $line) {
            if ($line->variant?->inventoryItem) {
                $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
            }
        }

        event(new OrderCancelled($order));
    }
}
