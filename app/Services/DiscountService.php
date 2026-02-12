<?php

namespace App\Services;

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\Discount;
use Illuminate\Support\Str;

class DiscountService
{
    public function validate(string $code, Cart $cart): Discount
    {
        $discount = Discount::where('store_id', $cart->store_id)
            ->whereRaw('LOWER(code) = ?', [Str::lower($code)])
            ->first();

        if (! $discount) {
            throw new InvalidDiscountException('discount_not_found', 'Discount code not found.');
        }

        if ($discount->status !== DiscountStatus::Active) {
            throw new InvalidDiscountException('discount_expired', 'Discount is not active.');
        }

        if ($discount->starts_at && $discount->starts_at->isFuture()) {
            throw new InvalidDiscountException('discount_not_yet_active', 'Discount is not yet active.');
        }

        if ($discount->ends_at && $discount->ends_at->isPast()) {
            throw new InvalidDiscountException('discount_expired', 'Discount has expired.');
        }

        if ($discount->usage_limit !== null && $discount->times_used >= $discount->usage_limit) {
            throw new InvalidDiscountException('discount_usage_limit_reached', 'Discount usage limit reached.');
        }

        $cart->loadMissing('lines.variant.product');
        $subtotal = $cart->lines->sum('line_subtotal_amount');

        if ($discount->minimum_purchase_amount !== null && $subtotal < $discount->minimum_purchase_amount) {
            throw new InvalidDiscountException('discount_min_purchase_not_met', 'Minimum purchase amount not met.');
        }

        $appliesTo = $discount->applies_to_json ?? [];
        $productIds = $appliesTo['product_ids'] ?? [];
        $collectionIds = $appliesTo['collection_ids'] ?? [];

        if (! empty($productIds) || ! empty($collectionIds)) {
            $hasQualifyingLine = $cart->lines->contains(function ($line) use ($productIds, $collectionIds) {
                $product = $line->variant?->product;

                if (! $product) {
                    return false;
                }

                if (! empty($productIds) && in_array($product->id, $productIds)) {
                    return true;
                }

                if (! empty($collectionIds)) {
                    $productCollectionIds = $product->collections()->pluck('collections.id')->all();

                    return ! empty(array_intersect($collectionIds, $productCollectionIds));
                }

                return false;
            });

            if (! $hasQualifyingLine) {
                throw new InvalidDiscountException('discount_not_applicable', 'No qualifying products in cart.');
            }
        }

        return $discount;
    }

    public function calculateDiscountAmount(Discount $discount, Cart $cart): int
    {
        if ($discount->value_type === DiscountValueType::FreeShipping) {
            return 0;
        }

        $cart->loadMissing('lines.variant.product');
        $qualifyingLines = $this->getQualifyingLines($discount, $cart);
        $qualifyingSubtotal = $qualifyingLines->sum('line_subtotal_amount');

        if ($qualifyingSubtotal <= 0) {
            return 0;
        }

        if ($discount->value_type === DiscountValueType::Percent) {
            return (int) round($qualifyingSubtotal * $discount->value_amount / 100);
        }

        return min($discount->value_amount, $qualifyingSubtotal);
    }

    /**
     * Allocate discount proportionally across qualifying lines.
     *
     * @return array<int, int> Map of cart_line_id to discount amount
     */
    public function allocateDiscount(Discount $discount, Cart $cart): array
    {
        if ($discount->value_type === DiscountValueType::FreeShipping) {
            return [];
        }

        $cart->loadMissing('lines.variant.product');
        $qualifyingLines = $this->getQualifyingLines($discount, $cart);
        $qualifyingSubtotal = $qualifyingLines->sum('line_subtotal_amount');

        if ($qualifyingSubtotal <= 0) {
            return [];
        }

        $totalDiscount = $this->calculateDiscountAmount($discount, $cart);
        $remaining = $totalDiscount;
        $allocations = [];
        $lineCount = $qualifyingLines->count();
        $index = 0;

        foreach ($qualifyingLines as $line) {
            $index++;

            if ($index === $lineCount) {
                $allocations[$line->id] = $remaining;
            } else {
                $lineDiscount = (int) round($totalDiscount * $line->line_subtotal_amount / $qualifyingSubtotal);
                $allocations[$line->id] = $lineDiscount;
                $remaining -= $lineDiscount;
            }
        }

        return $allocations;
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\CartLine>
     */
    private function getQualifyingLines(Discount $discount, Cart $cart): \Illuminate\Support\Collection
    {
        $appliesTo = $discount->applies_to_json ?? [];
        $productIds = $appliesTo['product_ids'] ?? [];
        $collectionIds = $appliesTo['collection_ids'] ?? [];

        if (empty($productIds) && empty($collectionIds)) {
            return $cart->lines;
        }

        return $cart->lines->filter(function ($line) use ($productIds, $collectionIds) {
            $product = $line->variant?->product;

            if (! $product) {
                return false;
            }

            if (! empty($productIds) && in_array($product->id, $productIds)) {
                return true;
            }

            if (! empty($collectionIds)) {
                $productCollectionIds = $product->collections()->pluck('collections.id')->all();

                return ! empty(array_intersect($collectionIds, $productCollectionIds));
            }

            return false;
        });
    }
}
