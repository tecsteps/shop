<?php

namespace App\Services;

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\Store;

class DiscountService
{
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

        if ($discount->starts_at->isFuture()) {
            throw new InvalidDiscountException('discount_not_yet_active');
        }

        if ($discount->ends_at && $discount->ends_at->isPast()) {
            throw new InvalidDiscountException('discount_expired');
        }

        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            throw new InvalidDiscountException('discount_usage_limit_reached');
        }

        $rules = $discount->rules_json ?? [];
        $cartSubtotal = $cart->lines->sum('line_subtotal_amount');

        if (! empty($rules['min_purchase_amount']) && $cartSubtotal < $rules['min_purchase_amount']) {
            throw new InvalidDiscountException('discount_min_purchase_not_met');
        }

        $hasProductRestrictions = ! empty($rules['applicable_product_ids']) || ! empty($rules['applicable_collection_ids']);

        if ($hasProductRestrictions) {
            $qualifyingLines = $this->getQualifyingLines($cart, $rules);
            if ($qualifyingLines->isEmpty()) {
                throw new InvalidDiscountException('discount_not_applicable');
            }
        }

        return $discount;
    }

    /**
     * @return array{total_discount: int, line_allocations: array<int, int>}
     */
    public function calculate(Discount $discount, int $subtotal, array $lines): array
    {
        if ($discount->value_type === DiscountValueType::FreeShipping) {
            return [
                'total_discount' => 0,
                'line_allocations' => [],
                'free_shipping' => true,
            ];
        }

        $rules = $discount->rules_json ?? [];
        $qualifyingLineIds = null;

        if (! empty($rules['applicable_product_ids']) || ! empty($rules['applicable_collection_ids'])) {
            $qualifyingLineIds = [];
            foreach ($lines as $line) {
                $productId = $line['product_id'] ?? null;
                $collectionIds = $line['collection_ids'] ?? [];

                $matchesProduct = ! empty($rules['applicable_product_ids'])
                    && in_array($productId, $rules['applicable_product_ids']);
                $matchesCollection = ! empty($rules['applicable_collection_ids'])
                    && ! empty(array_intersect($collectionIds, $rules['applicable_collection_ids']));

                if ($matchesProduct || $matchesCollection) {
                    $qualifyingLineIds[] = $line['line_id'];
                }
            }
        }

        $qualifyingLines = $qualifyingLineIds !== null
            ? array_filter($lines, fn ($l) => in_array($l['line_id'], $qualifyingLineIds))
            : $lines;

        $qualifyingSubtotal = array_sum(array_column($qualifyingLines, 'line_subtotal_amount'));

        if ($qualifyingSubtotal <= 0) {
            return [
                'total_discount' => 0,
                'line_allocations' => [],
                'free_shipping' => false,
            ];
        }

        $totalDiscount = match ($discount->value_type) {
            DiscountValueType::Percent => (int) round($qualifyingSubtotal * $discount->value_amount / 100),
            DiscountValueType::Fixed => min($discount->value_amount, $qualifyingSubtotal),
            default => 0,
        };

        $allocations = [];
        $remaining = $totalDiscount;
        $qualifyingLinesArray = array_values($qualifyingLines);
        $count = count($qualifyingLinesArray);

        for ($i = 0; $i < $count; $i++) {
            $line = $qualifyingLinesArray[$i];
            if ($i === $count - 1) {
                $allocations[$line['line_id']] = $remaining;
            } else {
                $lineDiscount = (int) round($totalDiscount * $line['line_subtotal_amount'] / $qualifyingSubtotal);
                $allocations[$line['line_id']] = $lineDiscount;
                $remaining -= $lineDiscount;
            }
        }

        return [
            'total_discount' => $totalDiscount,
            'line_allocations' => $allocations,
            'free_shipping' => false,
        ];
    }

    protected function getQualifyingLines(Cart $cart, array $rules): \Illuminate\Support\Collection
    {
        return $cart->lines->filter(function ($line) use ($rules) {
            $variant = $line->variant()->with('product.collections')->first();
            $productId = $variant->product_id;
            $collectionIds = $variant->product->collections->pluck('id')->toArray();

            $matchesProduct = ! empty($rules['applicable_product_ids'])
                && in_array($productId, $rules['applicable_product_ids']);
            $matchesCollection = ! empty($rules['applicable_collection_ids'])
                && ! empty(array_intersect($collectionIds, $rules['applicable_collection_ids']));

            return $matchesProduct || $matchesCollection;
        });
    }
}
