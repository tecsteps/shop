<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use App\Models\Checkout;
use App\Models\Order;
use App\Models\StoreSettings;
use App\ValueObjects\PaymentResult;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly FulfillmentService $fulfillmentService,
    ) {}

    public function createFromCheckout(Checkout $checkout, PaymentResult $paymentResult): Order
    {
        $existing = Order::query()->where('checkout_id', $checkout->id)->first();

        if ($existing) {
            return $existing;
        }

        $checkout->loadMissing('cart.lines.variant.product');
        $isDeferred = $checkout->payment_method === PaymentMethod::BankTransfer;
        $totals = $checkout->totals_json;
        $discount = $checkout->discount_code
            ? $checkout->store->discounts()
                ->whereRaw('LOWER(code) = ?', [mb_strtolower($checkout->discount_code)])
                ->first()
            : null;
        $order = Order::query()->create([
            'store_id' => $checkout->store_id,
            'customer_id' => $checkout->customer_id,
            'checkout_id' => $checkout->id,
            'order_number' => $this->nextOrderNumber($checkout->store_id),
            'payment_method' => $checkout->payment_method,
            'status' => $isDeferred ? OrderStatus::Pending : OrderStatus::Paid,
            'financial_status' => $isDeferred ? FinancialStatus::Pending : FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentOrderStatus::Unfulfilled,
            'currency' => $checkout->cart->currency,
            'subtotal_amount' => $totals['subtotal'],
            'discount_amount' => $totals['discount'],
            'shipping_amount' => $totals['shipping'],
            'tax_amount' => $totals['tax_total'],
            'total_amount' => $totals['total'],
            'email' => $checkout->email,
            'billing_address_json' => $checkout->billing_address_json,
            'shipping_address_json' => $checkout->shipping_address_json,
            'placed_at' => now(),
        ]);

        foreach ($checkout->cart->lines->values() as $index => $cartLine) {
            $order->lines()->create([
                'product_id' => $cartLine->variant->product_id,
                'variant_id' => $cartLine->variant_id,
                'title_snapshot' => $cartLine->variant->product->title,
                'sku_snapshot' => $cartLine->variant->sku,
                'quantity' => $cartLine->quantity,
                'unit_price_amount' => $cartLine->unit_price_amount,
                'total_amount' => $cartLine->line_total_amount,
                'tax_lines_json' => isset($totals['tax_lines'][$index]) ? [$totals['tax_lines'][$index]] : [],
                'discount_allocations_json' => $cartLine->line_discount_amount > 0
                    ? [['discount_id' => $discount?->id, 'amount' => $cartLine->line_discount_amount]]
                    : [],
            ]);

            if (! $isDeferred) {
                $this->inventoryService->commit($cartLine->variant->inventoryItem, $cartLine->quantity);
            }
        }

        $order->payments()->create([
            'provider' => 'mock',
            'method' => $checkout->payment_method,
            'provider_payment_id' => $paymentResult->referenceId,
            'status' => $isDeferred ? PaymentStatus::Pending : PaymentStatus::Captured,
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'raw_json_encrypted' => $paymentResult->raw,
        ]);

        $discount?->increment('usage_count');

        OrderCreated::dispatch($order);

        if (! $isDeferred) {
            OrderPaid::dispatch($order);
            $this->fulfillmentService->autoFulfillDigitalOrder($order);
        }

        return $order->load(['lines', 'payments']);
    }

    public function confirmBankTransferPayment(Order $order): Order
    {
        if ($order->payment_method !== PaymentMethod::BankTransfer
            || $order->financial_status !== FinancialStatus::Pending) {
            return $order;
        }

        return DB::transaction(function () use ($order): Order {
            $order->loadMissing(['lines.variant.inventoryItem', 'payments']);

            foreach ($order->lines as $line) {
                if ($line->variant?->inventoryItem) {
                    $this->inventoryService->commit($line->variant->inventoryItem, $line->quantity);
                }
            }

            $order->payments()->update(['status' => PaymentStatus::Captured]);
            $order->update(['financial_status' => FinancialStatus::Paid, 'status' => OrderStatus::Paid]);
            OrderPaid::dispatch($order);
            $this->fulfillmentService->autoFulfillDigitalOrder($order);

            return $order->refresh();
        });
    }

    private function nextOrderNumber(int $storeId): string
    {
        $settings = StoreSettings::query()->find($storeId)?->settings_json ?? [];
        $prefix = (string) ($settings['order_number_prefix'] ?? '#');
        $maximum = Order::query()
            ->where('store_id', $storeId)
            ->pluck('order_number')
            ->map(fn (string $number): int => (int) preg_replace('/\D/', '', $number))
            ->max() ?? 1000;

        return $prefix.($maximum + 1);
    }
}
