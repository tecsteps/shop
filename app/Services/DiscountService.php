<?php

namespace App\Services;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\Collection;
use App\Models\Discount;
use App\Models\Store;
use App\ValueObjects\DiscountValidationResult;

/**
 * Discount validation, calculation and allocation per spec 05 section 7.
 */
class DiscountService
{
    public function validate(string $code, Store $store, Cart $cart): DiscountValidationResult
    {
        $discount = Discount::query()
            ->where('store_id', $store->getKey())
            ->whereRaw('LOWER(code) = ?', [mb_strtolower($code)])
            ->first();

        if ($discount === null) {
            return DiscountValidationResult::invalid('discount_not_found', 'This discount code is invalid.');
        }

        if ($discount->status !== DiscountStatus::Active) {
            return DiscountValidationResult::invalid('discount_expired', 'This discount code has expired.');
        }

        if ($discount->starts_at !== null && $discount->starts_at->isFuture()) {
            return DiscountValidationResult::invalid('discount_not_yet_active', 'This discount code is not yet active.');
        }

        if ($discount->ends_at !== null && $discount->ends_at->isPast()) {
            return DiscountValidationResult::invalid('discount_expired', 'This discount code has expired.');
        }

        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            return DiscountValidationResult::invalid('discount_usage_limit_reached', 'This discount code has reached its usage limit.');
        }

        $subtotal = $this->cartSubtotal($cart);
        $minimum = $this->minimumPurchaseAmount($discount);

        if ($minimum !== null && $subtotal < $minimum) {
            return DiscountValidationResult::invalid('discount_min_purchase_not_met', 'This discount code requires a minimum purchase.');
        }

        if ($this->hasProductRestrictions($discount) && $this->qualifyingLines($discount, $cart)->isEmpty()) {
            return DiscountValidationResult::invalid('discount_not_applicable', 'This discount code does not apply to any items in your cart.');
        }

        return DiscountValidationResult::valid($discount);
    }

    /**
     * Validate and throw on failure, for flows that need exceptions.
     */
    public function validateOrFail(string $code, Store $store, Cart $cart): Discount
    {
        $result = $this->validate($code, $store, $cart);

        if (! $result->valid || $result->discount === null) {
            throw new InvalidDiscountException($result->errorCode ?? 'discount_not_found', $result->errorMessage ?? 'Invalid discount code.');
        }

        return $result->discount;
    }

    /**
     * Calculate the discount amount for the given lines.
     *
     * @param  array<int, array{line_id: int, subtotal: int, product_id?: ?int}>  $lines
     * @return array{total: int, allocations: array<int, int>, free_shipping: bool}
     */
    public function calculate(Discount $discount, int $subtotal, array $lines): array
    {
        if ($discount->value_type === DiscountValueType::FreeShipping) {
            return ['total' => 0, 'allocations' => [], 'free_shipping' => true];
        }

        $qualifying = $this->filterQualifyingLines($discount, $lines);
        $qualifyingSubtotal = array_sum(array_column($qualifying, 'subtotal'));

        if ($qualifyingSubtotal <= 0) {
            return ['total' => 0, 'allocations' => [], 'free_shipping' => false];
        }

        if ($discount->value_type === DiscountValueType::Percent) {
            $total = intdiv($qualifyingSubtotal * $discount->value_amount, 100);
        } else {
            $total = min($discount->value_amount, $qualifyingSubtotal);
        }

        return [
            'total' => $total,
            'allocations' => $this->allocate($total, $qualifying),
            'free_shipping' => false,
        ];
    }

    /**
     * Allocate a discount total across qualifying lines, largest-remainder to last.
     *
     * @param  array<int, array{line_id: int, subtotal: int}>  $qualifyingLines
     * @return array<int, int> Map of line_id to discount amount.
     */
    public function allocate(int $total, array $qualifyingLines): array
    {
        $allocations = [];
        $qualifyingSubtotal = array_sum(array_column($qualifyingLines, 'subtotal'));

        if ($qualifyingSubtotal <= 0 || $total <= 0) {
            return $allocations;
        }

        $remaining = $total;
        $lastIndex = count($qualifyingLines) - 1;

        foreach (array_values($qualifyingLines) as $index => $line) {
            if ($index === $lastIndex) {
                $allocations[$line['line_id']] = $remaining;

                continue;
            }

            $share = intdiv($total * $line['subtotal'], $qualifyingSubtotal);
            $allocations[$line['line_id']] = $share;
            $remaining -= $share;
        }

        return $allocations;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Discount>
     */
    public function applicableAutomaticDiscounts(Store $store, Cart $cart): mixed
    {
        return Discount::query()
            ->where('store_id', $store->getKey())
            ->where('type', DiscountType::Automatic)
            ->where('status', DiscountStatus::Active)
            ->orderBy('id')
            ->get()
            ->filter(function (Discount $discount) use ($cart): bool {
                if ($discount->starts_at !== null && $discount->starts_at->isFuture()) {
                    return false;
                }

                if ($discount->ends_at !== null && $discount->ends_at->isPast()) {
                    return false;
                }

                if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
                    return false;
                }

                $minimum = $this->minimumPurchaseAmount($discount);

                if ($minimum !== null && $this->cartSubtotal($cart) < $minimum) {
                    return false;
                }

                if ($this->hasProductRestrictions($discount) && $this->qualifyingLines($discount, $cart)->isEmpty()) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    public function cartSubtotal(Cart $cart): int
    {
        return (int) $cart->cartLines()->sum('line_subtotal_amount');
    }

    public function minimumPurchaseAmount(Discount $discount): ?int
    {
        $rules = $discount->rules_json ?? [];

        $minimum = $rules['min_purchase_amount'] ?? $rules['minimum_purchase_amount'] ?? null;

        return $minimum === null ? null : (int) $minimum;
    }

    public function hasProductRestrictions(Discount $discount): bool
    {
        $rules = $discount->rules_json ?? [];

        return ! empty($rules['applicable_product_ids']) || ! empty($rules['applicable_collection_ids']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\CartLine>
     */
    public function qualifyingLines(Discount $discount, Cart $cart): mixed
    {
        $lines = $cart->cartLines()->with('variant.product')->get();

        if (! $this->hasProductRestrictions($discount)) {
            return $lines;
        }

        $rules = $discount->rules_json ?? [];
        $productIds = array_map('intval', (array) ($rules['applicable_product_ids'] ?? []));
        $collectionIds = array_map('intval', (array) ($rules['applicable_collection_ids'] ?? []));

        $collectionProductIds = $collectionIds === []
            ? []
            : Collection::query()
                ->where('store_id', $discount->store_id)
                ->whereIn('id', $collectionIds)
                ->with('products:id')
                ->get()
                ->flatMap(fn (Collection $collection) => $collection->products->pluck('id'))
                ->map(fn ($id): int => (int) $id)
                ->all();

        return $lines->filter(function ($line) use ($productIds, $collectionProductIds): bool {
            $productId = (int) $line->variant->product_id;

            return in_array($productId, $productIds, true) || in_array($productId, $collectionProductIds, true);
        })->values();
    }

    /**
     * @param  array<int, array{line_id: int, subtotal: int, product_id?: ?int}>  $lines
     * @return array<int, array{line_id: int, subtotal: int}>
     */
    protected function filterQualifyingLines(Discount $discount, array $lines): array
    {
        if (! $this->hasProductRestrictions($discount)) {
            return array_values($lines);
        }

        $rules = $discount->rules_json ?? [];
        $productIds = array_map('intval', (array) ($rules['applicable_product_ids'] ?? []));
        $collectionIds = array_map('intval', (array) ($rules['applicable_collection_ids'] ?? []));

        $collectionProductIds = $collectionIds === []
            ? []
            : Collection::query()
                ->where('store_id', $discount->store_id)
                ->whereIn('id', $collectionIds)
                ->with('products:id')
                ->get()
                ->flatMap(fn (Collection $collection) => $collection->products->pluck('id'))
                ->map(fn ($id): int => (int) $id)
                ->all();

        return array_values(array_filter($lines, function (array $line) use ($productIds, $collectionProductIds): bool {
            $productId = (int) ($line['product_id'] ?? 0);

            return in_array($productId, $productIds, true) || in_array($productId, $collectionProductIds, true);
        }));
    }
}
