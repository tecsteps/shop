<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Store;
use App\ValueObjects\PaymentResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(private readonly InventoryService $inventory, private readonly AuditLogger $audit) {}

    public function createFromCheckout(Checkout $checkout, ?PaymentResult $paymentResult = null): Order
    {
        return DB::transaction(function () use ($checkout, $paymentResult): Order {
            $checkout->load(['cart.lines.variant.product', 'cart.lines.variant.inventory', 'shippingRate']);
            $existing = Order::withoutGlobalScopes()->where('checkout_id', $checkout->getKey())->first();

            if ($existing !== null) {
                return $existing->load(['lines', 'payments']);
            }

            $totals = $checkout->totals_json ?? ['subtotal' => 0, 'discount' => 0, 'shipping' => 0, 'tax' => 0, 'total' => 0, 'currency' => $checkout->cart->currency];
            $discount = $checkout->discount_code === null ? null : Discount::withoutGlobalScopes()->where('store_id', $checkout->store_id)->whereRaw('lower(code) = ?', [strtolower($checkout->discount_code)])->first();
            $status = $paymentResult?->status === PaymentStatus::Captured ? FinancialStatus::Paid : FinancialStatus::Pending;
            $taxByLine = $this->allocateTaxLines($checkout->cart->lines, $totals['tax_lines'] ?? []);
            $order = Order::withoutGlobalScopes()->create([
                'store_id' => $checkout->store_id,
                'customer_id' => $checkout->customer_id,
                'checkout_id' => $checkout->getKey(),
                'order_number' => $this->generateOrderNumber(Store::findOrFail($checkout->store_id)),
                'currency' => $totals['currency'],
                'status' => $status === FinancialStatus::Paid ? OrderStatus::Paid : OrderStatus::Pending,
                'financial_status' => $status,
                'fulfillment_status' => 'unfulfilled',
                'payment_method' => $checkout->payment_method,
                'email' => $checkout->email,
                'shipping_address_json' => $checkout->shipping_address_json,
                'billing_address_json' => $checkout->billing_address_json,
                'subtotal_amount' => $totals['subtotal'],
                'discount_amount' => $totals['discount'] ?? 0,
                'shipping_amount' => $totals['shipping'] ?? 0,
                'tax_amount' => $totals['tax'] ?? 0,
                'total_amount' => $totals['total'],
                'placed_at' => now(),
            ]);

            foreach ($checkout->cart->lines as $line) {
                $order->lines()->create([
                    'product_id' => $line->variant->product_id,
                    'variant_id' => $line->variant_id,
                    'product_title' => $line->variant->product->title,
                    'title_snapshot' => $line->variant->product->title.' · '.$line->variant->title,
                    'variant_title' => $line->variant->title,
                    'sku' => $line->variant->sku,
                    'sku_snapshot' => $line->variant->sku,
                    'quantity' => $line->quantity,
                    'unit_price_amount' => $line->unit_price_amount,
                    'line_subtotal_amount' => $line->line_subtotal_amount,
                    'line_discount_amount' => $line->line_discount_amount,
                    'line_total_amount' => $line->line_total_amount,
                    'tax_lines_json' => $taxByLine[$line->getKey()] ?? [],
                    'discount_allocations_json' => $discount === null || $line->line_discount_amount < 1 ? [] : [['discount_id' => $discount->getKey(), 'amount' => $line->line_discount_amount]],
                ]);

                if ($paymentResult?->status === PaymentStatus::Captured && $line->variant->inventory !== null) {
                    $this->inventory->commit($line->variant->inventory, $line->quantity);
                }
            }

            $checkout->update(['status' => 'completed']);
            $checkout->cart->update(['status' => 'converted']);
            OrderCreated::dispatch($order);
            $this->audit->record('order.created', $order, ['store_id' => $order->store_id, 'order_number' => $order->order_number]);
            $order->load(['lines', 'payments']);

            if ($status === FinancialStatus::Paid) {
                OrderPaid::dispatch($order);
                $this->audit->record('order.paid', $order, ['store_id' => $order->store_id, 'order_number' => $order->order_number]);

                if ($checkout->cart->lines->every(fn ($line): bool => ! $line->variant->requires_shipping)) {
                    $fulfillment = $order->fulfillments()->create(['status' => 'delivered', 'fulfilled_at' => now(), 'delivered_at' => now()]);

                    foreach ($order->lines as $line) {
                        $fulfillment->lines()->create(['order_line_id' => $line->getKey(), 'quantity' => $line->quantity]);
                    }

                    $order->update(['status' => OrderStatus::Fulfilled, 'fulfillment_status' => FulfillmentStatus::Fulfilled]);
                }
            }

            return $order;
        });
    }

    public function generateOrderNumber(Store $store): string
    {
        $lastNumber = Order::withoutGlobalScopes()->where('store_id', $store->getKey())->selectRaw("max(cast(replace(order_number, '#', '') as integer)) as value")->value('value');

        return (string) config('shop.order_prefix', '#').(max(1000, (int) $lastNumber) + 1);
    }

    public function cancel(Order $order, string $reason): void
    {
        if ($order->fulfillment_status !== FulfillmentStatus::Unfulfilled) {
            throw new \LogicException('Fulfilled orders cannot be cancelled.');
        }

        DB::transaction(function () use ($order, $reason): void {
            $wasPending = $order->financial_status === FinancialStatus::Pending;
            $order->load('lines.variant.inventory')->update(['status' => OrderStatus::Cancelled, 'financial_status' => $order->financial_status === FinancialStatus::Pending ? FinancialStatus::Voided : $order->financial_status, 'metadata' => array_merge($order->metadata ?? [], ['cancellation_reason' => $reason])]);

            foreach ($order->lines as $line) {
                if ($line->variant?->inventory !== null && $wasPending) {
                    $this->inventory->release($line->variant->inventory, $line->quantity);
                }
            }

            $order->payments()->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Authorized])->update(['status' => PaymentStatus::Failed]);

            OrderCancelled::dispatch($order->refresh());
            $this->audit->record('order.cancelled', $order, ['store_id' => $order->store_id, 'order_number' => $order->order_number, 'reason' => $reason]);
        });
    }

    public function confirmPayment(Order $order): void
    {
        if ($order->payment_method !== 'bank_transfer' || $order->financial_status !== FinancialStatus::Pending) {
            throw new \LogicException('Only pending bank transfer orders can be confirmed.');
        }

        DB::transaction(function () use ($order): void {
            $order->load('lines.variant.inventory')->update(['financial_status' => FinancialStatus::Paid, 'status' => OrderStatus::Paid]);

            foreach ($order->lines as $line) {
                if ($line->variant?->inventory !== null) {
                    $this->inventory->commit($line->variant->inventory, $line->quantity);
                }
            }

            $order->payments()->where('status', PaymentStatus::Pending)->update(['status' => PaymentStatus::Captured]);
            OrderPaid::dispatch($order->refresh());
            $this->audit->record('order.paid', $order, ['store_id' => $order->store_id, 'order_number' => $order->order_number]);

            if ($order->lines->every(fn ($line): bool => ! $line->variant?->requires_shipping)) {
                $this->autoFulfillDigitalOrder($order->refresh());
            }
        });
    }

    private function autoFulfillDigitalOrder(Order $order): void
    {
        if ($order->fulfillments()->exists()) {
            return;
        }

        $fulfillment = $order->fulfillments()->create(['status' => 'delivered', 'fulfilled_at' => now(), 'delivered_at' => now()]);
        foreach ($order->lines as $line) {
            $fulfillment->lines()->create(['order_line_id' => $line->getKey(), 'quantity' => $line->quantity]);
        }
        $order->update(['status' => OrderStatus::Fulfilled, 'fulfillment_status' => FulfillmentStatus::Fulfilled]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\CartLine>  $lines
     * @param  array<int, array{title?: string, rate?: int, amount?: int}>  $taxLines
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function allocateTaxLines(Collection $lines, array $taxLines): array
    {
        $base = max(1, (int) $lines->sum('line_total_amount'));
        $allocations = $lines->mapWithKeys(fn ($line): array => [$line->getKey() => []])->all();

        foreach ($taxLines as $taxLine) {
            $remaining = (int) ($taxLine['amount'] ?? 0);
            $lineCount = $lines->count();

            foreach ($lines->values() as $index => $line) {
                $amount = $index === $lineCount - 1
                    ? $remaining
                    : intdiv((int) ($taxLine['amount'] ?? 0) * (int) $line->line_total_amount, $base);
                $remaining -= $amount;

                if ($amount > 0) {
                    $allocations[$line->getKey()][] = [...$taxLine, 'amount' => $amount];
                }
            }
        }

        return $allocations;
    }
}
