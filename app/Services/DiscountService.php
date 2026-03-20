<?php

namespace App\Services;

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\Store;
use App\ValueObjects\DiscountResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DiscountService
{
    public function validate(string $code, Store $store, Cart $cart): Discount
    {
        $discount = Discount::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereRaw('LOWER(code) = ?', [strtolower($code)])
            ->first();

        if (! $discount) {
            throw new InvalidDiscountException('discount_not_found', 'Discount code not found.');
        }

        if ($discount->status !== DiscountStatus::Active) {
            throw new InvalidDiscountException('discount_expired', 'Discount is not active.');
        }

        if (Carbon::parse($discount->starts_at)->isFuture()) {
            throw new InvalidDiscountException('discount_not_yet_active', 'Discount is not yet active.');
        }

        if ($discount->ends_at && Carbon::parse($discount->ends_at)->isPast()) {
            throw new InvalidDiscountException('discount_expired', 'Discount has expired.');
        }

        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            throw new InvalidDiscountException('discount_usage_limit_reached', 'Discount usage limit reached.');
        }

        $subtotal = $this->getCartSubtotal($cart);

        if ($discount->minimum_purchase_amount !== null && $subtotal < $discount->minimum_purchase_amount) {
            throw new InvalidDiscountException('discount_min_purchase_not_met', 'Minimum purchase amount not met.');
        }

        $rules = $discount->rules_json ?? [];
        $minPurchase = $rules['min_purchase_amount'] ?? null;

        if ($minPurchase !== null && $subtotal < $minPurchase) {
            throw new InvalidDiscountException('discount_min_purchase_not_met', 'Minimum purchase amount not met.');
        }

        $productIds = $rules['applicable_product_ids'] ?? null;
        $collectionIds = $rules['applicable_collection_ids'] ?? null;

        if ($this->hasProductRestrictions($productIds, $collectionIds)) {
            $qualifyingLines = $this->getQualifyingLines($cart, $productIds, $collectionIds);

            if ($qualifyingLines->isEmpty()) {
                throw new InvalidDiscountException('discount_not_applicable', 'No qualifying products in cart.');
            }
        }

        return $discount;
    }

    /**
     * @param  Collection<int, \App\Models\CartLine>  $lines
     */
    public function calculate(Discount $discount, int $subtotal, Collection $lines): DiscountResult
    {
        if ($discount->value_type === DiscountValueType::FreeShipping) {
            return new DiscountResult(
                totalDiscount: 0,
                lineAllocations: [],
                isFreeShipping: true,
            );
        }

        $rules = $discount->rules_json ?? [];
        $productIds = $rules['applicable_product_ids'] ?? null;
        $collectionIds = $rules['applicable_collection_ids'] ?? null;

        if ($this->hasProductRestrictions($productIds, $collectionIds)) {
            $qualifyingLines = $lines->filter(function ($line) use ($productIds, $collectionIds) {
                return $this->lineQualifies($line, $productIds, $collectionIds);
            });
        } else {
            $qualifyingLines = $lines;
        }

        $qualifyingSubtotal = $qualifyingLines->sum('line_subtotal_amount');

        if ($qualifyingSubtotal <= 0) {
            return new DiscountResult(totalDiscount: 0, lineAllocations: []);
        }

        $totalDiscount = $this->calculateTotalDiscount($discount, $qualifyingSubtotal);

        $allocations = $this->allocateProportionally($totalDiscount, $qualifyingLines, $qualifyingSubtotal);

        return new DiscountResult(
            totalDiscount: $totalDiscount,
            lineAllocations: $allocations,
        );
    }

    private function calculateTotalDiscount(Discount $discount, int $qualifyingSubtotal): int
    {
        return match ($discount->value_type) {
            DiscountValueType::Percent => (int) round($qualifyingSubtotal * $discount->value_amount / 100),
            DiscountValueType::Fixed => min($discount->value_amount, $qualifyingSubtotal),
            default => 0,
        };
    }

    /**
     * @return array<int, int>
     */
    private function allocateProportionally(int $totalDiscount, Collection $qualifyingLines, int $qualifyingSubtotal): array
    {
        $allocations = [];
        $remaining = $totalDiscount;
        $lineCount = $qualifyingLines->count();
        $index = 0;

        foreach ($qualifyingLines as $line) {
            $index++;

            if ($index === $lineCount) {
                $allocations[$line->id] = $remaining;
            } else {
                $allocation = (int) round($totalDiscount * $line->line_subtotal_amount / $qualifyingSubtotal);
                $allocations[$line->id] = $allocation;
                $remaining -= $allocation;
            }
        }

        return $allocations;
    }

    private function getCartSubtotal(Cart $cart): int
    {
        return $cart->lines()->sum('line_subtotal_amount');
    }

    /**
     * @param  array<int>|null  $productIds
     * @param  array<int>|null  $collectionIds
     */
    private function hasProductRestrictions(?array $productIds, ?array $collectionIds): bool
    {
        return (! empty($productIds)) || (! empty($collectionIds));
    }

    /**
     * @param  array<int>|null  $productIds
     * @param  array<int>|null  $collectionIds
     */
    private function getQualifyingLines(Cart $cart, ?array $productIds, ?array $collectionIds): Collection
    {
        return $cart->lines()->with('variant.product.collections')->get()
            ->filter(fn ($line) => $this->lineQualifies($line, $productIds, $collectionIds));
    }

    /**
     * @param  array<int>|null  $productIds
     * @param  array<int>|null  $collectionIds
     */
    private function lineQualifies(mixed $line, ?array $productIds, ?array $collectionIds): bool
    {
        $productId = $line->variant->product_id;

        if (! empty($productIds) && in_array($productId, $productIds)) {
            return true;
        }

        if (! empty($collectionIds)) {
            $lineCollectionIds = $line->variant->product->collections->pluck('id')->toArray();

            return ! empty(array_intersect($lineCollectionIds, $collectionIds));
        }

        return false;
    }
}
