<?php

namespace App\Services;

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\Store;
use Carbon\Carbon;

class DiscountService
{
    /**
     * Validate a discount code for a given store and cart.
     *
     * @throws InvalidDiscountException
     */
    public function validate(string $code, Store $store, Cart $cart): Discount
    {
        $discount = Discount::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereRaw('LOWER(code) = ?', [strtolower($code)])
            ->first();

        if (! $discount) {
            throw new InvalidDiscountException('discount_not_found');
        }

        if ($discount->status !== DiscountStatus::Active) {
            throw new InvalidDiscountException('discount_expired');
        }

        $now = Carbon::now();

        if ($discount->starts_at && $now->lt($discount->starts_at)) {
            throw new InvalidDiscountException('discount_not_yet_active');
        }

        if ($discount->ends_at && $now->gt($discount->ends_at)) {
            throw new InvalidDiscountException('discount_expired');
        }

        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            throw new InvalidDiscountException('discount_usage_limit_reached');
        }

        $cart->load('lines');
        $subtotal = $cart->lines->sum('line_subtotal_amount');

        $rules = $discount->rules_json ?? [];
        $minPurchase = $rules['min_purchase_amount'] ?? null;

        if ($minPurchase !== null && $subtotal < $minPurchase) {
            throw new InvalidDiscountException('discount_min_purchase_not_met');
        }

        $applicableProductIds = $rules['applicable_product_ids'] ?? null;
        $applicableCollectionIds = $rules['applicable_collection_ids'] ?? null;

        if ($this->hasProductRestrictions($applicableProductIds, $applicableCollectionIds)) {
            $qualifyingLines = $this->getQualifyingLines(
                $cart,
                $applicableProductIds,
                $applicableCollectionIds
            );

            if ($qualifyingLines->isEmpty()) {
                throw new InvalidDiscountException('discount_not_applicable');
            }
        }

        return $discount;
    }

    /**
     * Calculate the discount amount and allocate proportionally across qualifying lines.
     *
     * @param  array<\App\Models\CartLine>  $lines
     * @return array{total: int, allocations: array<int, int>}
     */
    public function calculate(Discount $discount, int $subtotal, $lines): array
    {
        if ($discount->value_type === DiscountValueType::FreeShipping) {
            return ['total' => 0, 'allocations' => []];
        }

        $rules = $discount->rules_json ?? [];
        $applicableProductIds = $rules['applicable_product_ids'] ?? null;
        $applicableCollectionIds = $rules['applicable_collection_ids'] ?? null;

        $qualifyingLines = collect($lines);

        if ($this->hasProductRestrictions($applicableProductIds, $applicableCollectionIds)) {
            $qualifyingLines = $qualifyingLines->filter(function ($line) use ($applicableProductIds, $applicableCollectionIds) {
                return $this->lineQualifies($line, $applicableProductIds, $applicableCollectionIds);
            });
        }

        if ($qualifyingLines->isEmpty()) {
            return ['total' => 0, 'allocations' => []];
        }

        $qualifyingSubtotal = $qualifyingLines->sum('line_subtotal_amount');

        if ($qualifyingSubtotal <= 0) {
            return ['total' => 0, 'allocations' => []];
        }

        $totalDiscount = $this->calculateTotalDiscount($discount, $qualifyingSubtotal);

        return $this->allocateProportionally($totalDiscount, $qualifyingLines, $qualifyingSubtotal);
    }

    /**
     * Calculate the raw total discount amount.
     */
    private function calculateTotalDiscount(Discount $discount, int $qualifyingSubtotal): int
    {
        return match ($discount->value_type) {
            DiscountValueType::Percent => (int) round($qualifyingSubtotal * $discount->value_amount / 100),
            DiscountValueType::Fixed => min($discount->value_amount, $qualifyingSubtotal),
            DiscountValueType::FreeShipping => 0,
        };
    }

    /**
     * Allocate discount proportionally across qualifying lines using largest-remainder method.
     *
     * @return array{total: int, allocations: array<int, int>}
     */
    private function allocateProportionally($totalDiscount, $qualifyingLines, int $qualifyingSubtotal): array
    {
        $allocations = [];
        $remaining = $totalDiscount;
        $lineValues = $qualifyingLines->values();

        foreach ($lineValues as $index => $line) {
            $isLast = $index === $lineValues->count() - 1;

            if ($isLast) {
                $lineDiscount = $remaining;
            } else {
                $lineDiscount = (int) round($totalDiscount * $line->line_subtotal_amount / $qualifyingSubtotal);
                $remaining -= $lineDiscount;
            }

            $allocations[$line->id] = $lineDiscount;
        }

        return ['total' => $totalDiscount, 'allocations' => $allocations];
    }

    /**
     * Check if there are product or collection restrictions.
     *
     * @param  array<int>|null  $productIds
     * @param  array<int>|null  $collectionIds
     */
    private function hasProductRestrictions(?array $productIds, ?array $collectionIds): bool
    {
        return (! empty($productIds)) || (! empty($collectionIds));
    }

    /**
     * Get cart lines that qualify for the discount.
     *
     * @param  array<int>|null  $applicableProductIds
     * @param  array<int>|null  $applicableCollectionIds
     */
    private function getQualifyingLines(Cart $cart, ?array $applicableProductIds, ?array $applicableCollectionIds): \Illuminate\Support\Collection
    {
        $cart->load('lines.variant.product.collections');

        return $cart->lines->filter(function ($line) use ($applicableProductIds, $applicableCollectionIds) {
            return $this->lineQualifies($line, $applicableProductIds, $applicableCollectionIds);
        });
    }

    /**
     * Check if a single line qualifies for the discount (union logic).
     *
     * @param  array<int>|null  $applicableProductIds
     * @param  array<int>|null  $applicableCollectionIds
     */
    private function lineQualifies($line, ?array $applicableProductIds, ?array $applicableCollectionIds): bool
    {
        $variant = $line->variant;

        if (! $variant || ! $variant->product) {
            return false;
        }

        if (! empty($applicableProductIds) && in_array($variant->product->id, $applicableProductIds, true)) {
            return true;
        }

        if (! empty($applicableCollectionIds) && $variant->product->collections) {
            $productCollectionIds = $variant->product->collections->pluck('id')->toArray();
            if (array_intersect($applicableCollectionIds, $productCollectionIds)) {
                return true;
            }
        }

        return false;
    }
}
