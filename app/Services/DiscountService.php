<?php

namespace App\Services;

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\Store;
use App\ValueObjects\DiscountResult;

class DiscountService
{
    public function validate(string $code, Store $store, Cart $cart): Discount
    {
        $discount = Discount::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereRaw('LOWER(code) = ?', [strtolower($code)])
            ->first();

        if (! $discount) {
            throw new InvalidDiscountException('not_found', 'Discount code not found.');
        }

        if ($discount->status !== DiscountStatus::Active) {
            throw new InvalidDiscountException('expired', 'Discount is not active.');
        }

        if ($discount->starts_at && $discount->starts_at->isFuture()) {
            throw new InvalidDiscountException('not_yet_active', 'Discount is not yet active.');
        }

        if ($discount->ends_at && $discount->ends_at->isPast()) {
            throw new InvalidDiscountException('expired', 'Discount has expired.');
        }

        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            throw new InvalidDiscountException('usage_limit_reached', 'Discount usage limit reached.');
        }

        $rules = $discount->rules_json ?? [];
        $minPurchase = $rules['min_purchase_amount'] ?? null;

        if ($minPurchase !== null) {
            $subtotal = $cart->lines->sum('line_subtotal_amount');
            if ($subtotal < $minPurchase) {
                throw new InvalidDiscountException('minimum_not_met', 'Minimum purchase amount not met.');
            }
        }

        return $discount;
    }

    /**
     * @param  array<int, array{id: int, subtotal: int}>  $lines
     */
    public function calculate(Discount $discount, int $subtotal, array $lines): DiscountResult
    {
        if ($discount->value_type === DiscountValueType::FreeShipping) {
            return new DiscountResult(amount: 0, isFreeShipping: true);
        }

        if ($discount->value_type === DiscountValueType::Percent) {
            $totalDiscount = (int) round($subtotal * $discount->value_amount / 100);
        } else {
            $totalDiscount = min($discount->value_amount, $subtotal);
        }

        $allocations = $this->allocateProportionally($totalDiscount, $subtotal, $lines);

        return new DiscountResult(
            amount: $totalDiscount,
            isFreeShipping: false,
            allocations: $allocations,
        );
    }

    /**
     * @param  array<int, array{id: int, subtotal: int}>  $lines
     * @return array<int, int>
     */
    private function allocateProportionally(int $totalDiscount, int $subtotal, array $lines): array
    {
        if ($subtotal === 0 || empty($lines)) {
            return [];
        }

        $allocations = [];
        $remaining = $totalDiscount;
        $lastIndex = count($lines) - 1;

        foreach ($lines as $i => $line) {
            if ($i === $lastIndex) {
                $allocations[$line['id']] = $remaining;
            } else {
                $lineDiscount = (int) round($totalDiscount * $line['subtotal'] / $subtotal);
                $allocations[$line['id']] = $lineDiscount;
                $remaining -= $lineDiscount;
            }
        }

        return $allocations;
    }
}
