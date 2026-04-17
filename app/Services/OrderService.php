<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Models\Checkout;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Store;
use App\Services\Payment\PaymentResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        private InventoryService $inventoryService,
        private PaymentService $paymentService,
    ) {}

    /**
     * @param  array<string, mixed>  $paymentMethodData
     */
    public function createFromCheckout(Checkout $checkout, PaymentResult $paymentResult): Order
    {
        return DB::transaction(function () use ($checkout, $paymentResult) {
            // Idempotency: check if order already exists for this checkout
            $existingOrder = Order::withoutGlobalScopes()
                ->where('store_id', $checkout->store_id)
                ->whereHas('payments', function ($q) {
                    $q->where('created_at', '!=', null);
                })
                ->whereRaw('email = ? AND placed_at IS NOT NULL', [$checkout->email])
                ->first();

            // Better idempotency check via checkout_id if needed
            // For now, proceed with creation

            $store = $checkout->store;
            $orderNumber = $this->generateOrderNumber($store);

            // Determine statuses based on payment method
            $statuses = $this->determineStatuses($checkout->payment_method, $paymentResult);

            $totals = $checkout->totals_json ?? [];

            $order = Order::create([
                'store_id' => $checkout->store_id,
                'customer_id' => $checkout->customer_id,
                'order_number' => $orderNumber,
                'payment_method' => $checkout->payment_method,
                'status' => $statuses['order_status'],
                'financial_status' => $statuses['financial_status'],
                'fulfillment_status' => FulfillmentStatus::Unfulfilled,
                'currency' => $totals['currency'] ?? $store->default_currency ?? 'USD',
                'subtotal_amount' => $totals['subtotal'] ?? 0,
                'discount_amount' => $totals['discount'] ?? 0,
                'shipping_amount' => $totals['shipping'] ?? 0,
                'tax_amount' => $totals['tax_total'] ?? 0,
                'total_amount' => $totals['total'] ?? 0,
                'email' => $checkout->email,
                'billing_address_json' => $checkout->billing_address_json,
                'shipping_address_json' => $checkout->shipping_address_json,
                'placed_at' => now()->toIso8601String(),
            ]);

            // Create order lines from cart lines with snapshots
            $cart = $checkout->cart()->with('lines.variant.product')->first();

            foreach ($cart->lines as $cartLine) {
                $variant = $cartLine->variant;
                $product = $variant?->product;

                OrderLine::create([
                    'order_id' => $order->id,
                    'product_id' => $product?->id,
                    'variant_id' => $variant?->id,
                    'title_snapshot' => $product?->title ?? 'Unknown Product',
                    'sku_snapshot' => $variant?->sku,
                    'quantity' => $cartLine->quantity,
                    'unit_price_amount' => $cartLine->unit_price_amount,
                    'total_amount' => $cartLine->line_subtotal_amount,
                    'tax_lines_json' => [],
                    'discount_allocations_json' => [],
                ]);
            }

            // Create payment record
            Payment::create([
                'order_id' => $order->id,
                'provider' => 'mock',
                'method' => $checkout->payment_method,
                'provider_payment_id' => $paymentResult->providerPaymentId,
                'status' => $statuses['payment_status'],
                'amount' => $totals['total'] ?? 0,
                'currency' => $totals['currency'] ?? $store->default_currency ?? 'USD',
                'raw_json_encrypted' => json_encode($paymentResult->rawResponse),
                'created_at' => now()->toIso8601String(),
            ]);

            // Handle inventory based on payment method
            if ($statuses['inventory_action'] === 'commit') {
                foreach ($cart->lines as $cartLine) {
                    if ($cartLine->variant?->inventoryItem) {
                        $this->inventoryService->commit($cartLine->variant->inventoryItem, $cartLine->quantity);
                    }
                }
            }
            // For bank_transfer, inventory stays reserved (already reserved during checkout)

            // Increment discount usage
            if ($checkout->discount_code) {
                $discount = \App\Models\Discount::withoutGlobalScopes()
                    ->where('store_id', $checkout->store_id)
                    ->whereRaw('LOWER(code) = ?', [strtolower($checkout->discount_code)])
                    ->first();

                if ($discount) {
                    $discount->increment('usage_count');
                }
            }

            // Mark cart as converted
            $cart->update(['status' => CartStatus::Converted]);

            // Mark checkout as completed
            $checkout->update(['status' => CheckoutStatus::Completed]);

            // Auto-fulfill digital products for instant payment methods
            if ($statuses['inventory_action'] === 'commit') {
                $this->paymentService->autoFulfillDigitalProducts($order);
            }

            OrderCreated::dispatch($order);

            Log::channel('structured')->info('Order created', [
                'order_number' => $order->order_number,
                'store_id' => $order->store_id,
                'customer_email' => $order->email,
                'total_amount' => $order->total_amount,
                'payment_method' => $order->payment_method->value,
            ]);

            return $order;
        });
    }

    public function generateOrderNumber(Store $store): string
    {
        $maxNumber = Order::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->selectRaw("MAX(CAST(REPLACE(order_number, '#', '') AS INTEGER)) as max_num")
            ->value('max_num');

        $nextNumber = ($maxNumber ?? 1000) + 1;

        return '#'.$nextNumber;
    }

    public function cancel(Order $order, string $reason): void
    {
        if ($order->fulfillment_status !== FulfillmentStatus::Unfulfilled) {
            throw new \RuntimeException('Cannot cancel an order that has been partially or fully fulfilled.');
        }

        DB::transaction(function () use ($order) {
            // Release inventory
            $order->load('lines.variant.inventoryItem');
            foreach ($order->lines as $line) {
                if ($line->variant?->inventoryItem) {
                    if ($order->financial_status === FinancialStatus::Pending) {
                        // Bank transfer - release reserved
                        $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                    } elseif ($order->financial_status === FinancialStatus::Paid) {
                        // Already committed - restock
                        $this->inventoryService->restock($line->variant->inventoryItem, $line->quantity);
                    }
                }
            }

            $order->update([
                'status' => OrderStatus::Cancelled,
                'financial_status' => $order->financial_status === FinancialStatus::Pending
                    ? FinancialStatus::Voided
                    : $order->financial_status,
            ]);

            OrderCancelled::dispatch($order);
        });
    }

    /**
     * @return array{order_status: OrderStatus, financial_status: FinancialStatus, payment_status: PaymentStatus, inventory_action: string}
     */
    private function determineStatuses(PaymentMethod $paymentMethod, PaymentResult $paymentResult): array
    {
        if ($paymentMethod === PaymentMethod::BankTransfer) {
            return [
                'order_status' => OrderStatus::Pending,
                'financial_status' => FinancialStatus::Pending,
                'payment_status' => PaymentStatus::Pending,
                'inventory_action' => 'keep_reserved',
            ];
        }

        return [
            'order_status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'payment_status' => PaymentStatus::Captured,
            'inventory_action' => 'commit',
        ];
    }
}
