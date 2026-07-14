<?php

namespace App\Services;

use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\OrderLine;
use App\Models\Store;
use App\ValueObjects\DiscountResult;
use App\ValueObjects\DiscountValidationResult;
use BackedEnum;
use Illuminate\Support\Collection;

final class DiscountService
{
    public function validate(string $code, Store $store, Cart $cart): Discount
    {
        $discount = Discount::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereRaw('LOWER(code) = ?', [mb_strtolower(trim($code))])
            ->first();

        if ($discount === null) {
            throw new InvalidDiscountException('not_found');
        }

        return $this->validateDiscount($discount, $store, $cart);
    }

    public function validateDiscount(Discount $discount, Store $store, Cart $cart, ?int $customerId = null): Discount
    {
        if ((int) $discount->store_id !== (int) $store->id) {
            throw new InvalidDiscountException('not_found');
        }
        if ($this->value($discount->status) !== 'active') {
            throw new InvalidDiscountException('expired');
        }
        if ($discount->starts_at !== null && $discount->starts_at->isFuture()) {
            throw new InvalidDiscountException('not_yet_active');
        }
        if ($discount->ends_at !== null && $discount->ends_at->isPast()) {
            throw new InvalidDiscountException('expired');
        }
        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            throw new InvalidDiscountException('usage_limit_reached');
        }

        $cart->loadMissing('lines.variant.product.collections');
        $subtotal = (int) $cart->lines->sum('line_subtotal_amount');
        $rules = (array) ($discount->rules_json ?? []);
        $minimum = (int) ($rules['min_purchase_amount'] ?? $rules['minimum_purchase'] ?? 0);
        if ($minimum > 0 && $subtotal < $minimum) {
            throw new InvalidDiscountException('minimum_not_met');
        }
        if ($this->qualifyingLines($discount, $cart->lines)->isEmpty() && $cart->lines->isNotEmpty()) {
            throw new InvalidDiscountException('not_applicable');
        }
        $rules = (array) ($discount->rules_json ?? []);
        $oncePerCustomer = (bool) ($rules['one_per_customer'] ?? $rules['once_per_customer'] ?? false);
        $customerId ??= $cart->customer_id === null ? null : (int) $cart->customer_id;
        if ($oncePerCustomer && $customerId !== null && $this->customerHasRedeemed($discount, $customerId)) {
            throw new InvalidDiscountException('customer_usage_limit_reached');
        }

        return $discount;
    }

    /** @return Collection<int, Discount> */
    public function automaticDiscounts(Store $store, Cart $cart): Collection
    {
        return Discount::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('type', 'automatic')
            ->where('status', 'active')
            ->orderBy('id')
            ->get()
            ->filter(function (Discount $discount) use ($store, $cart): bool {
                try {
                    $this->validateDiscount($discount, $store, $cart);

                    return true;
                } catch (InvalidDiscountException) {
                    return false;
                }
            })
            ->values();
    }

    public function validateResult(string $code, Store $store, Cart $cart): DiscountValidationResult
    {
        try {
            return new DiscountValidationResult(true, $this->validate($code, $store, $cart));
        } catch (InvalidDiscountException $exception) {
            return new DiscountValidationResult(false, errorCode: $exception->reason, errorMessage: $exception->getMessage());
        }
    }

    /** @param array<int, mixed>|Collection<int, mixed> $lines */
    public function calculate(Discount $discount, int $subtotal, array|Collection $lines): DiscountResult
    {
        $lines = $lines instanceof Collection ? $lines : collect($lines);
        $qualifying = $this->qualifyingLines($discount, $lines);
        $qualifyingSubtotal = (int) $qualifying->sum(fn (mixed $line): int => $this->lineSubtotal($line));

        if ($this->value($discount->value_type) === 'free_shipping') {
            return new DiscountResult(0, [], true);
        }
        if ($qualifyingSubtotal < 1 || $subtotal < 1) {
            return new DiscountResult(0);
        }

        $amount = $this->value($discount->value_type) === 'percent'
            ? (int) round($qualifyingSubtotal * (int) $discount->value_amount / 100)
            : min((int) $discount->value_amount, $qualifyingSubtotal);
        $amount = min($amount, $subtotal);

        $remaining = $amount;
        $allocations = [];
        $lastIndex = $qualifying->count() - 1;
        foreach ($qualifying->values() as $index => $line) {
            $lineId = (int) (is_array($line) ? ($line['id'] ?? $index) : ($line->id ?? $index));
            $allocation = $index === $lastIndex
                ? $remaining
                : (int) round($amount * $this->lineSubtotal($line) / $qualifyingSubtotal);
            $allocation = min($remaining, $allocation);
            $allocations[$lineId] = $allocation;
            $remaining -= $allocation;
        }

        return new DiscountResult($amount, $allocations);
    }

    /** @param Collection<int, mixed> $lines @return Collection<int, mixed> */
    private function qualifyingLines(Discount $discount, Collection $lines): Collection
    {
        $rules = (array) ($discount->rules_json ?? []);
        $productIds = array_map('intval', (array) ($rules['applicable_product_ids'] ?? []));
        $collectionIds = array_map('intval', (array) ($rules['applicable_collection_ids'] ?? []));
        if ($productIds === [] && $collectionIds === []) {
            return $lines;
        }

        return $lines->filter(function (mixed $line) use ($productIds, $collectionIds): bool {
            $product = is_array($line) ? ($line['product'] ?? null) : ($line->variant?->product ?? null);
            $productId = (int) (is_array($line) ? ($line['product_id'] ?? 0) : ($product?->id ?? 0));
            $ids = $product?->relationLoaded('collections') ? $product->collections->modelKeys() : [];

            return in_array($productId, $productIds, true) || array_intersect($ids, $collectionIds) !== [];
        });
    }

    private function lineSubtotal(mixed $line): int
    {
        return (int) (is_array($line)
            ? ($line['line_subtotal_amount'] ?? $line['subtotal'] ?? (($line['unit_price_amount'] ?? 0) * ($line['quantity'] ?? 0)))
            : ($line->line_subtotal_amount ?? (($line->unit_price_amount ?? 0) * ($line->quantity ?? 0))));
    }

    private function value(mixed $value): string
    {
        return $value instanceof BackedEnum ? (string) $value->value : (string) $value;
    }

    public function customerHasRedeemed(Discount $discount, int $customerId): bool
    {
        return OrderLine::query()
            ->whereHas('order', fn ($orders) => $orders->withoutGlobalScopes()
                ->where('store_id', $discount->store_id)
                ->where('customer_id', $customerId))
            ->get(['discount_allocations_json'])
            ->contains(fn (OrderLine $line): bool => collect((array) $line->discount_allocations_json)
                ->contains(fn (mixed $allocation): bool => (int) data_get($allocation, 'discount_id') === (int) $discount->id));
    }
}
