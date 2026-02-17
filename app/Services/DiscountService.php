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
        $discount = Discount::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereRaw('LOWER(code) = ?', [strtolower($code)])
            ->first();

        if (! $discount) {
            throw new InvalidDiscountException('not_found', 'Discount code not found');
        }

        if ($discount->status !== DiscountStatus::Active) {
            throw new InvalidDiscountException('expired', 'Discount is not active');
        }

        if ($discount->starts_at && $discount->starts_at->isFuture()) {
            throw new InvalidDiscountException('not_yet_active', 'Discount is not yet active');
        }

        if ($discount->ends_at && $discount->ends_at->isPast()) {
            throw new InvalidDiscountException('expired', 'Discount has expired');
        }

        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            throw new InvalidDiscountException('usage_limit_reached', 'Discount usage limit reached');
        }

        $subtotal = $cart->lines->sum('line_subtotal_amount');
        $rules = $discount->rules_json ?? [];

        if (! empty($rules['minimum_purchase_amount']) && $subtotal < $rules['minimum_purchase_amount']) {
            throw new InvalidDiscountException('minimum_not_met', 'Minimum purchase not met');
        }

        return $discount;
    }

    /**
     * @param  array<int, array{line_subtotal_amount: int, product_id: int}>  $lines
     * @return array{total_discount: int, allocations: array<int, int>}
     */
    public function calculate(Discount $discount, int $subtotal, array $lines): array
    {
        if ($discount->value_type === DiscountValueType::FreeShipping) {
            return ['total_discount' => 0, 'allocations' => []];
        }

        $qualifyingLines = $this->getQualifyingLines($discount, $lines);
        $qualifyingSubtotal = array_sum(array_column($qualifyingLines, 'line_subtotal_amount'));

        if ($qualifyingSubtotal === 0) {
            return ['total_discount' => 0, 'allocations' => []];
        }

        $totalDiscount = match ($discount->value_type) {
            DiscountValueType::Percent => (int) round($qualifyingSubtotal * $discount->value_amount / 100),
            DiscountValueType::Fixed => min($discount->value_amount, $qualifyingSubtotal),
            default => 0,
        };

        $allocations = $this->allocateDiscount($totalDiscount, $qualifyingLines, $qualifyingSubtotal);

        return ['total_discount' => $totalDiscount, 'allocations' => $allocations];
    }

    /**
     * @param  array<int, array{line_subtotal_amount: int, product_id: int}>  $lines
     * @return array<int, array{line_subtotal_amount: int, product_id: int}>
     */
    private function getQualifyingLines(Discount $discount, array $lines): array
    {
        $rules = $discount->rules_json ?? [];
        $productIds = $rules['applicable_product_ids'] ?? null;
        $collectionIds = $rules['applicable_collection_ids'] ?? null;

        if (empty($productIds) && empty($collectionIds)) {
            return $lines;
        }

        return array_filter($lines, function (array $line) use ($productIds) {
            if (! empty($productIds) && in_array($line['product_id'], $productIds)) {
                return true;
            }

            return false;
        });
    }

    /**
     * @param  array<int, array{line_subtotal_amount: int, product_id: int}>  $qualifyingLines
     * @return array<int, int>
     */
    private function allocateDiscount(int $totalDiscount, array $qualifyingLines, int $qualifyingSubtotal): array
    {
        $allocations = [];
        $remaining = $totalDiscount;
        $lineKeys = array_keys($qualifyingLines);
        $lastKey = end($lineKeys);

        foreach ($qualifyingLines as $key => $line) {
            if ($key === $lastKey) {
                $allocations[$key] = $remaining;
            } else {
                $lineDiscount = (int) round($totalDiscount * $line['line_subtotal_amount'] / $qualifyingSubtotal);
                $allocations[$key] = $lineDiscount;
                $remaining -= $lineDiscount;
            }
        }

        return $allocations;
    }
}
