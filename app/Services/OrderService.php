<?php

namespace App\Services;

use App\Events\CheckoutCompleted;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderFulfilled;
use App\Events\OrderPaid;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Order;
use App\Models\StoreSettings;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly DiscountCalculator $discountCalculator,
    ) {}

    /** @param array{id: string, status: string, message: string} $providerResult */
    public function createFromCheckout(Checkout $checkout, array $providerResult): Order
    {
        return DB::transaction(function () use ($checkout, $providerResult): Order {
            $existing = Order::withoutGlobalScopes()->where('checkout_id', $checkout->id)->first();

            if ($existing) {
                return $existing->load('lines', 'payments', 'fulfillments');
            }

            $checkout->loadMissing('cart.lines.variant.product', 'cart.lines.variant.inventoryItem');
            $cart = $checkout->cart;
            $lines = $cart->lines;
            $store = $checkout->store_id;
            $totals = $checkout->totals_json ?? [];
            $appliedDiscountIds = array_values(array_unique(array_map('intval', $totals['applied_discount_ids'] ?? [])));

            if ($appliedDiscountIds === [] && $checkout->discount_code) {
                $legacyDiscountId = Discount::withoutGlobalScopes()
                    ->where('store_id', $store)
                    ->where('code', $checkout->discount_code)
                    ->value('id');

                if ($legacyDiscountId !== null) {
                    $appliedDiscountIds[] = (int) $legacyDiscountId;
                }
            }

            $appliedDiscounts = Discount::withoutGlobalScopes()
                ->where('store_id', $store)
                ->whereIn('id', $appliedDiscountIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($appliedDiscounts as $discount) {
                if (($discount->rules_json['one_per_customer'] ?? false)
                    && $this->discountCalculator->hasCustomerRedeemed($discount, $checkout->customer_id, $checkout->email)) {
                    throw ValidationException::withMessages(['discount_code' => 'This discount can only be used once per customer.']);
                }
            }

            $settings = StoreSettings::query()->where('store_id', $store)->first()?->settings_json ?? [];
            $startNumber = (int) ($settings['order_number_start'] ?? 1001);
            $prefix = (string) ($settings['order_number_prefix'] ?? '#');
            $orderNumber = $prefix.str_pad((string) ($startNumber + Order::withoutGlobalScopes()->where('store_id', $store)->count()), 4, '0', STR_PAD_LEFT);
            $isPaid = $providerResult['status'] === 'captured';

            $order = Order::withoutGlobalScopes()->create([
                'checkout_id' => $checkout->id,
                'store_id' => $store,
                'customer_id' => $checkout->customer_id,
                'order_number' => $orderNumber,
                'payment_method' => $checkout->payment_method,
                'status' => $isPaid ? 'paid' : 'pending',
                'financial_status' => $isPaid ? 'paid' : 'pending',
                'fulfillment_status' => 'unfulfilled',
                'currency' => $cart->currency,
                'subtotal_amount' => $checkout->subtotal_amount,
                'discount_amount' => $checkout->discount_amount,
                'shipping_amount' => $checkout->shipping_amount,
                'tax_amount' => $checkout->tax_amount,
                'total_amount' => $checkout->total_amount,
                'email' => $checkout->email,
                'billing_address_json' => $checkout->billing_address_json,
                'shipping_address_json' => $checkout->shipping_address_json,
                'placed_at' => now(),
            ]);
            $discountAllocations = $totals['discountAllocations'] ?? [];

            foreach ($lines as $line) {
                $variant = $line->variant;
                $key = (string) $line->variant_id;
                $lineDiscount = (int) ($discountAllocations[$key] ?? $discountAllocations[(int) $line->variant_id] ?? 0);
                $lineTotal = max(0, $line->line_subtotal_amount - $lineDiscount);
                $lineDiscountDetails = [];

                foreach ($totals['appliedDiscounts'] ?? [] as $appliedDiscount) {
                    $amount = (int) ($appliedDiscount['allocations'][$key] ?? $appliedDiscount['allocations'][(int) $line->variant_id] ?? 0);

                    if ($amount > 0 && $appliedDiscount['discount_id'] !== null) {
                        $lineDiscountDetails[] = ['discount_id' => (int) $appliedDiscount['discount_id'], 'amount' => $amount];
                    }
                }

                if ($lineDiscountDetails === [] && $lineDiscount > 0) {
                    $lineDiscountDetails[] = ['amount' => $lineDiscount];
                }

                $order->lines()->create([
                    'product_id' => $variant?->product_id,
                    'variant_id' => $variant?->id,
                    'title_snapshot' => $variant?->product?->title ?? 'Removed product',
                    'variant_title_snapshot' => $variant?->title,
                    'sku_snapshot' => $variant?->sku,
                    'quantity' => $line->quantity,
                    'unit_price_amount' => $line->unit_price_amount,
                    'total_amount' => $lineTotal,
                    'discount_allocations_json' => $lineDiscountDetails,
                ]);

                if ($isPaid && $variant?->inventoryItem) {
                    $this->inventory->commit($variant->inventoryItem, $line->quantity);
                }
            }

            $order->payments()->create([
                'provider' => 'mock',
                'method' => $checkout->payment_method,
                'provider_payment_id' => $providerResult['id'],
                'status' => $providerResult['status'] === 'captured' ? 'captured' : 'pending',
                'amount' => $checkout->total_amount,
                'currency' => $cart->currency,
                'raw_json_encrypted' => Crypt::encryptString(json_encode($providerResult, JSON_THROW_ON_ERROR)),
                'created_at' => now(),
            ]);

            $checkout->forceFill(['status' => 'completed', 'completed_at' => now()])->save();
            $cart->forceFill(['status' => 'converted'])->save();

            foreach ($appliedDiscounts as $discount) {
                $discount->increment('usage_count');
            }

            if ($isPaid && $lines->isNotEmpty() && $lines->every(static fn ($line): bool => ! $line->variant?->requires_shipping)) {
                $fulfillment = $order->fulfillments()->create(['status' => 'delivered', 'created_at' => now()]);

                foreach ($order->lines as $line) {
                    $fulfillment->lines()->create(['order_line_id' => $line->id, 'quantity' => $line->quantity]);
                }

                $order->forceFill(['status' => 'fulfilled', 'fulfillment_status' => 'fulfilled'])->save();
            }

            $order = $order->refresh()->load('lines', 'payments', 'fulfillments');
            OrderCreated::dispatch($order);
            CheckoutCompleted::dispatch($order);

            if ($isPaid) {
                OrderPaid::dispatch($order);
            }

            if ($order->fulfillment_status === 'fulfilled') {
                OrderFulfilled::dispatch($order);
            }

            return $order;
        });
    }

    public function confirmBankTransfer(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $order = Order::withoutGlobalScopes()->with('payments', 'lines.variant.inventoryItem')->lockForUpdate()->findOrFail($order->id);

            if ($order->payment_method !== 'bank_transfer' || $order->financial_status !== 'pending') {
                abort(422, 'This order is not awaiting a bank transfer.');
            }

            $payment = $order->payments()->where('status', 'pending')->firstOrFail();

            foreach ($order->lines as $line) {
                if ($line->variant?->inventoryItem) {
                    $this->inventory->commit($line->variant->inventoryItem, $line->quantity);
                }
            }

            $payment->update(['status' => 'captured']);
            $order->forceFill(['status' => 'paid', 'financial_status' => 'paid'])->save();

            if ($order->lines->isNotEmpty() && $order->lines->every(static fn ($line): bool => ! $line->variant?->requires_shipping)) {
                $fulfillment = $order->fulfillments()->create(['status' => 'delivered', 'created_at' => now()]);

                foreach ($order->lines as $line) {
                    $fulfillment->lines()->create(['order_line_id' => $line->id, 'quantity' => $line->quantity]);
                }

                $order->forceFill(['status' => 'fulfilled', 'fulfillment_status' => 'fulfilled'])->save();
            }

            $order = $order->refresh();
            OrderPaid::dispatch($order);

            if ($order->fulfillment_status === 'fulfilled') {
                OrderFulfilled::dispatch($order);
            }

            return $order;
        });
    }

    public function cancelPending(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $order = Order::withoutGlobalScopes()->with('lines.variant.inventoryItem')->lockForUpdate()->findOrFail($order->id);

            if ($order->financial_status !== 'pending') {
                abort(422, 'Only unpaid orders can be cancelled.');
            }

            foreach ($order->lines as $line) {
                if ($line->variant?->inventoryItem) {
                    $this->inventory->release($line->variant->inventoryItem, $line->quantity);
                }
            }

            $order->payments()->where('status', 'pending')->update(['status' => 'failed']);
            $order->forceFill(['status' => 'cancelled', 'financial_status' => 'voided'])->save();
            OrderCancelled::dispatch($order->refresh());

            return $order->refresh();
        });
    }
}
