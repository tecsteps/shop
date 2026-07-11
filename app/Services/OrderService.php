<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Store;
use App\ValueObjects\PaymentResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function createFromCheckout(Checkout $checkout, PaymentResult $paymentResult): Order
    {
        return DB::transaction(function () use ($checkout, $paymentResult): Order {
            $existingOrder = Order::query()->where('checkout_id', $checkout->id)->first();

            if ($existingOrder) {
                return $existingOrder;
            }

            $checkout->loadMissing('cart.lines.variant.product');
            $captured = $paymentResult->status === PaymentStatus::Captured;
            $totals = $checkout->totals_json ?? [];

            $order = Order::query()->create([
                'store_id' => $checkout->store_id,
                'customer_id' => $checkout->customer_id,
                'checkout_id' => $checkout->id,
                'order_number' => $this->generateOrderNumber($checkout->store),
                'payment_method' => $checkout->payment_method,
                'status' => $captured ? OrderStatus::Paid : OrderStatus::Pending,
                'financial_status' => $captured ? FinancialStatus::Paid : FinancialStatus::Pending,
                'fulfillment_status' => FulfillmentStatus::Unfulfilled,
                'currency' => $checkout->cart->currency,
                'subtotal_amount' => $totals['subtotal'] ?? 0,
                'discount_amount' => $totals['discount'] ?? 0,
                'shipping_amount' => $totals['shipping'] ?? 0,
                'tax_amount' => $totals['tax_total'] ?? 0,
                'total_amount' => $totals['total'] ?? 0,
                'email' => $checkout->email,
                'billing_address_json' => $checkout->billing_address_json,
                'shipping_address_json' => $checkout->shipping_address_json,
                'placed_at' => now(),
            ]);

            foreach ($checkout->cart->lines as $cartLine) {
                $order->lines()->create([
                    'product_id' => $cartLine->variant->product_id,
                    'variant_id' => $cartLine->variant_id,
                    'title_snapshot' => $cartLine->variant->product->title,
                    'sku_snapshot' => $cartLine->variant->sku,
                    'quantity' => $cartLine->quantity,
                    'unit_price_amount' => $cartLine->unit_price_amount,
                    'total_amount' => $cartLine->line_total_amount,
                    'tax_lines_json' => $totals['tax_lines'] ?? [],
                    'discount_allocations_json' => $cartLine->line_discount_amount > 0 ? [['amount' => $cartLine->line_discount_amount]] : [],
                ]);

                if ($captured) {
                    $this->inventoryService->commit($cartLine->variant->inventoryItem, $cartLine->quantity);
                }
            }

            $order->payments()->create([
                'provider' => 'mock',
                'method' => $checkout->payment_method,
                'provider_payment_id' => $paymentResult->providerPaymentId,
                'status' => $paymentResult->status,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'raw_json_encrypted' => $paymentResult->raw,
            ]);

            if ($checkout->discount_code) {
                Discount::query()->whereRaw('LOWER(code) = ?', [Str::lower($checkout->discount_code)])->increment('usage_count');
            }

            $checkout->cart->update(['status' => CartStatus::Converted]);

            if ($captured && $checkout->cart->lines->every(fn ($line): bool => ! $line->variant->requires_shipping)) {
                $this->autoFulfillDigitalOrder($order);
            }

            OrderCreated::dispatch($order);

            return $order->refresh()->load(['lines', 'payments', 'fulfillments.lines']);
        });
    }

    public function generateOrderNumber(Store $store): string
    {
        $prefix = $store->settings?->settings_json['order_number_prefix'] ?? '#';
        $latestNumber = Order::query()->where('store_id', $store->id)->latest('id')->value('order_number');
        $nextNumber = $latestNumber ? ((int) preg_replace('/\D/', '', $latestNumber)) + 1 : 1001;

        return $prefix.$nextNumber;
    }

    public function cancel(Order $order, string $reason): void
    {
        if ($order->fulfillment_status !== FulfillmentStatus::Unfulfilled) {
            throw ValidationException::withMessages(['order' => 'A fulfilled order cannot be cancelled.']);
        }

        DB::transaction(function () use ($order): void {
            if ($order->payment_method === PaymentMethod::BankTransfer && $order->financial_status === FinancialStatus::Pending) {
                foreach ($order->lines()->with('variant.inventoryItem')->get() as $line) {
                    $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                }
            }

            $order->payments()->where('status', PaymentStatus::Pending)->update(['status' => PaymentStatus::Failed]);
            $order->update(['status' => OrderStatus::Cancelled, 'financial_status' => FinancialStatus::Voided]);
            OrderCancelled::dispatch($order);
        });
    }

    public function autoFulfillDigitalOrder(Order $order): void
    {
        $fulfillment = $order->fulfillments()->create(['status' => 'delivered', 'shipped_at' => now(), 'delivered_at' => now()]);

        foreach ($order->lines as $line) {
            $fulfillment->lines()->create(['order_line_id' => $line->id, 'quantity' => $line->quantity]);
        }

        $order->update(['fulfillment_status' => FulfillmentStatus::Fulfilled, 'status' => OrderStatus::Fulfilled]);
    }
}
