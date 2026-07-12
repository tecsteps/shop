<?php

namespace App\Services;

use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use App\Exceptions\DomainException;
use App\Exceptions\InvalidDiscountException;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Store;
use App\ValueObjects\PaymentResult;
use BackedEnum;
use Illuminate\Support\Facades\DB;

final class OrderService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly FulfillmentService $fulfillments,
        private readonly DiscountService $discounts,
    ) {}

    public function createFromCheckout(Checkout $checkout, ?PaymentResult $paymentResult = null): Order
    {
        return DB::transaction(function () use ($checkout, $paymentResult): Order {
            $checkout = Checkout::withoutGlobalScopes()->lockForUpdate()->with([
                'cart' => fn ($query) => $query->withoutGlobalScopes(),
                'cart.lines.variant.product' => fn ($query) => $query->withoutGlobalScopes(),
                'cart.lines.variant.inventoryItem' => fn ($query) => $query->withoutGlobalScopes(),
                'store',
            ])->findOrFail($checkout->id);
            $snapshot = (array) ($checkout->totals_json ?? []);
            if (isset($snapshot['order_id'])) {
                return Order::withoutGlobalScopes()->findOrFail((int) $snapshot['order_id']);
            }

            $method = $this->value($checkout->payment_method);
            $pending = $method === 'bank_transfer';
            $paymentResult ??= new PaymentResult(true, 'mock_manual', $pending ? 'pending' : 'captured');
            $customer = $this->resolveCustomer($checkout);
            $appliedDiscounts = (array) ($snapshot['applied_discounts'] ?? []);
            if ($appliedDiscounts === [] && $checkout->discount_code !== null) {
                $legacyDiscount = Discount::withoutGlobalScopes()
                    ->where('store_id', $checkout->store_id)
                    ->whereRaw('LOWER(code) = ?', [mb_strtolower($checkout->discount_code)])
                    ->first();
                if ($legacyDiscount !== null) {
                    $appliedDiscounts = [[
                        'discount_id' => $legacyDiscount->id,
                        'code' => $legacyDiscount->code,
                        'amount' => (int) ($snapshot['discount'] ?? 0),
                        'allocations' => $checkout->cart->lines->mapWithKeys(fn ($line): array => [
                            (int) $line->id => (int) $line->line_discount_amount,
                        ])->all(),
                    ]];
                }
            }
            $lockedDiscounts = $this->lockAndValidateDiscounts($checkout, $appliedDiscounts, $customer?->id);
            $order = Order::withoutGlobalScopes()->create([
                'store_id' => $checkout->store_id,
                'customer_id' => $customer?->id,
                'order_number' => $this->generateOrderNumber($checkout->store),
                'payment_method' => $method,
                'status' => $pending ? 'pending' : 'paid',
                'financial_status' => $pending ? 'pending' : 'paid',
                'fulfillment_status' => 'unfulfilled',
                'currency' => $snapshot['currency'] ?? $checkout->store->default_currency,
                'subtotal_amount' => (int) ($snapshot['subtotal'] ?? 0),
                'discount_amount' => (int) ($snapshot['discount'] ?? 0),
                'shipping_amount' => (int) ($snapshot['shipping'] ?? 0),
                'tax_amount' => (int) ($snapshot['tax_total'] ?? $snapshot['tax'] ?? 0),
                'total_amount' => (int) ($snapshot['total'] ?? 0),
                'email' => $checkout->email,
                'billing_address_json' => $checkout->billing_address_json,
                'shipping_address_json' => $checkout->shipping_address_json,
                'placed_at' => now(),
            ]);

            foreach ($checkout->cart->lines as $line) {
                $variant = $line->variant;
                $title = $variant->product->title;
                if (method_exists($variant, 'optionValues')) {
                    $labels = $variant->optionValues()->pluck('value')->all();
                    $title .= $labels === [] ? '' : ' - '.implode(' / ', $labels);
                }
                $order->lines()->create([
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'title_snapshot' => $title,
                    'sku_snapshot' => $variant->sku,
                    'quantity' => $line->quantity,
                    'unit_price_amount' => $line->unit_price_amount,
                    'total_amount' => $line->line_total_amount,
                    'tax_lines_json' => (array) data_get($snapshot, 'line_tax_allocations.'.$line->id, $snapshot['tax_lines'] ?? []),
                    'discount_allocations_json' => collect($appliedDiscounts)
                        ->map(fn (array $discount): array => [
                            'discount_id' => (int) ($discount['discount_id'] ?? 0),
                            'amount' => (int) data_get($discount, 'allocations.'.$line->id, 0),
                        ])
                        ->filter(fn (array $allocation): bool => $allocation['discount_id'] > 0 && $allocation['amount'] > 0)
                        ->values()
                        ->all(),
                ]);

                if (! $pending && $variant->inventoryItem !== null) {
                    $this->inventory->commit($variant->inventoryItem, (int) $line->quantity);
                }
            }

            $order->payments()->create([
                'provider' => 'mock',
                'method' => $method,
                'provider_payment_id' => $paymentResult->referenceId,
                'status' => $pending ? 'pending' : 'captured',
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'raw_json_encrypted' => $paymentResult->raw,
            ]);

            foreach ($lockedDiscounts as $discount) {
                $discount->increment('usage_count');
            }

            $checkout->cart->update(['status' => 'converted']);
            $checkout->status = 'completed';
            $snapshot['order_id'] = $order->id;
            $checkout->totals_json = $snapshot;
            $checkout->save();
            event(new OrderCreated($order));

            if (! $pending) {
                event(new OrderPaid($order));
                $this->fulfillments->autoFulfillDigital($order);
            }

            return $order->refresh()->load(['lines', 'payments', 'fulfillments']);
        });
    }

    public function generateOrderNumber(Store $store): string
    {
        $prefix = (string) (($store->settings?->settings_json['order_number_prefix'] ?? null) ?: '#');
        $last = Order::withoutGlobalScopes()->where('store_id', $store->id)->pluck('order_number')
            ->map(fn (string $number): int => (int) preg_replace('/\D+/', '', $number))
            ->max() ?? 1000;

        return $prefix.max(1001, $last + 1);
    }

    public function confirmBankTransfer(Order $order): void
    {
        if ($this->value($order->payment_method) !== 'bank_transfer' || $this->value($order->financial_status) !== 'pending') {
            throw new DomainException('Only pending bank transfer orders can be confirmed.');
        }

        DB::transaction(function () use ($order): void {
            $order->load(['lines.variant.inventoryItem' => fn ($query) => $query->withoutGlobalScopes()]);
            foreach ($order->lines as $line) {
                if ($line->variant?->inventoryItem !== null) {
                    $this->inventory->commit($line->variant->inventoryItem, (int) $line->quantity);
                }
            }
            $order->payments()->where('status', 'pending')->update(['status' => 'captured']);
            $order->update(['financial_status' => 'paid', 'status' => 'paid']);
            event(new OrderPaid($order));
            $this->fulfillments->autoFulfillDigital($order);
        });
    }

    public function cancel(Order $order, string $reason): void
    {
        if ($this->value($order->fulfillment_status) !== 'unfulfilled') {
            throw new DomainException('Fulfilled orders cannot be cancelled.');
        }

        DB::transaction(function () use ($order, $reason): void {
            if ($this->value($order->financial_status) === 'pending') {
                $order->load(['lines.variant.inventoryItem' => fn ($query) => $query->withoutGlobalScopes()]);
                foreach ($order->lines as $line) {
                    if ($line->variant?->inventoryItem !== null) {
                        $this->inventory->release($line->variant->inventoryItem, (int) $line->quantity);
                    }
                }
            }
            $order->payments()->where('status', 'pending')->update(['status' => 'failed']);
            $order->update(['status' => 'cancelled', 'financial_status' => 'voided']);
            event(new OrderCancelled($order, $reason));
        });
    }

    private function resolveCustomer(Checkout $checkout): ?Customer
    {
        if ($checkout->customer_id !== null) {
            return Customer::withoutGlobalScopes()->find($checkout->customer_id);
        }
        if ($checkout->email === null || $checkout->email === '') {
            return null;
        }

        return Customer::withoutGlobalScopes()->firstOrCreate(
            ['store_id' => $checkout->store_id, 'email' => mb_strtolower($checkout->email)],
            ['name' => trim((string) data_get($checkout->shipping_address_json, 'first_name').' '.(string) data_get($checkout->shipping_address_json, 'last_name')), 'password_hash' => null, 'marketing_opt_in' => false],
        );
    }

    /**
     * @param  list<array<string, mixed>>  $appliedDiscounts
     * @return list<Discount>
     */
    private function lockAndValidateDiscounts(Checkout $checkout, array $appliedDiscounts, ?int $customerId): array
    {
        $discountIds = collect($appliedDiscounts)
            ->pluck('discount_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();
        if ($discountIds->isEmpty()) {
            return [];
        }

        $locked = Discount::withoutGlobalScopes()
            ->where('store_id', $checkout->store_id)
            ->whereKey($discountIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        if ($locked->count() !== $discountIds->count()) {
            throw new DomainException('An applied discount is no longer available.');
        }

        foreach ($locked as $discount) {
            try {
                $this->discounts->validateDiscount($discount, $checkout->store, $checkout->cart, $customerId);
            } catch (InvalidDiscountException $exception) {
                throw new DomainException('An applied discount is no longer valid: '.$exception->reason);
            }
        }

        return $locked->all();
    }

    private function value(mixed $value): string
    {
        return $value instanceof BackedEnum ? (string) $value->value : (string) $value;
    }
}
