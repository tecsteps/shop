<?php

namespace App\Services;

use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Store;
use App\ValueObjects\PaymentResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class OrderService
{
    public function __construct(
        private readonly PricingEngine $pricingEngine,
        private readonly InventoryService $inventoryService,
    ) {}

    public function createFromCheckout(Checkout $checkout, PaymentResult $paymentResult): Order
    {
        return DB::transaction(function () use ($checkout, $paymentResult) {
            $cart = $checkout->cart()->with('lines.variant.product', 'lines.variant.optionValues')->first();
            $totals = $checkout->totals_json ?? [];

            if ($checkout->payment_method === 'bank_transfer') {
                $status = 'pending';
                $financialStatus = 'pending';
                $paymentStatus = 'pending';
                $commit = false;
            } else {
                $status = 'paid';
                $financialStatus = 'paid';
                $paymentStatus = 'captured';
                $commit = true;
            }

            $order = Order::create([
                'store_id' => $checkout->store_id,
                'checkout_id' => $checkout->id,
                'customer_id' => $checkout->customer_id,
                'order_number' => $this->generateOrderNumber($checkout->store),
                'payment_method' => $checkout->payment_method,
                'status' => $status,
                'financial_status' => $financialStatus,
                'fulfillment_status' => 'unfulfilled',
                'currency' => $cart->currency,
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

            foreach ($cart->lines as $line) {
                $variantTitle = $line->variant->optionValues
                    ->sortBy(fn ($ov) => $ov->option?->position ?? 0)
                    ->pluck('value')
                    ->join(' / ');

                $order->lines()->create([
                    'product_id' => $line->variant->product_id,
                    'variant_id' => $line->variant_id,
                    'title_snapshot' => $line->variant->product->title.($variantTitle !== '' ? ' / '.$variantTitle : ''),
                    'sku_snapshot' => $line->variant->sku,
                    'quantity' => $line->quantity,
                    'unit_price_amount' => $line->unit_price_amount,
                    'total_amount' => $line->unit_price_amount * $line->quantity,
                    'tax_lines_json' => $totals['tax_lines'] ?? [],
                    'discount_allocations_json' => [],
                ]);
            }

            $order->payments()->create([
                'provider' => 'mock',
                'method' => $checkout->payment_method,
                'provider_payment_id' => $paymentResult->referenceId,
                'status' => $paymentStatus,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'raw_json_encrypted' => ['reference' => $paymentResult->referenceId, 'status' => $paymentStatus],
            ]);

            if ($commit) {
                $this->commitInventory($cart);
            }

            if ($checkout->discount_code) {
                Discount::where('store_id', $checkout->store_id)
                    ->whereRaw('LOWER(code) = ?', [Str::lower($checkout->discount_code)])
                    ->increment('usage_count');
            }

            $cart->update(['status' => 'converted']);
            $checkout->update(['status' => 'completed']);

            OrderCreated::dispatch($order);

            return $order;
        });
    }

    public function generateOrderNumber(Store $store): string
    {
        $prefix = '#';
        $last = Order::where('store_id', $store->id)->orderByDesc('id')->first();

        $next = $last ? ((int) preg_replace('/[^0-9]/', '', $last->order_number) + 1) : 1001;

        return $prefix.$next;
    }

    public function confirmPayment(Order $order): void
    {
        DB::transaction(function () use ($order) {
            if ($order->payment_method !== 'bank_transfer') {
                throw new InvalidArgumentException('Only bank transfer orders can be confirmed.');
            }

            if ($order->financial_status !== 'pending') {
                throw new InvalidArgumentException('This order has already been confirmed.');
            }

            $order->update(['financial_status' => 'paid', 'status' => 'paid']);
            $order->payments()->update(['status' => 'captured']);

            foreach ($order->lines()->with('variant.inventoryItem')->get() as $line) {
                $inventory = $line->variant?->inventoryItem;

                if ($inventory) {
                    $this->inventoryService->commit($inventory, $line->quantity);
                }
            }

            app(FulfillmentService::class)->autoFulfillDigital($order);

            \App\Events\OrderPaid::dispatch($order);
        });
    }

    public function cancel(Order $order, string $reason): void
    {
        DB::transaction(function () use ($order, $reason) {
            if (in_array($order->status, ['cancelled', 'fulfilled', 'refunded'], true)) {
                return;
            }

            $this->releaseInventory($order);
            $order->update(['status' => 'cancelled']);
            OrderCancelled::dispatch($order);
        });
    }

    private function commitInventory(mixed $cart): void
    {
        foreach ($cart->lines as $line) {
            $inventory = $line->variant->inventoryItem;

            if ($inventory) {
                $this->inventoryService->commit($inventory, $line->quantity);
            }
        }
    }

    private function releaseInventory(Order $order): void
    {
        foreach ($order->lines()->with('variant.inventoryItem')->get() as $line) {
            $inventory = $line->variant?->inventoryItem;

            if ($inventory) {
                $this->inventoryService->release($inventory, $line->quantity);
            }
        }
    }
}
