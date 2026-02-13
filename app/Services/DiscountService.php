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
        if (isset($rules['minimum_purchase'])) {
            $cartSubtotal = $cart->lines->sum('line_subtotal_amount');
            if ($cartSubtotal < $rules['minimum_purchase']) {
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
        $freeShipping = false;
        $totalDiscount = 0;
        $lineAllocations = [];

        if ($discount->value_type === DiscountValueType::FreeShipping) {
            $freeShipping = true;

            return new DiscountResult(
                totalDiscount: 0,
                freeShipping: true,
                lineAllocations: [],
            );
        }

        if ($discount->value_type === DiscountValueType::Percent) {
            $totalDiscount = intdiv($subtotal * $discount->value_amount, 10000);
        } elseif ($discount->value_type === DiscountValueType::Fixed) {
            $totalDiscount = min($discount->value_amount, $subtotal);
        }

        if ($subtotal > 0 && count($lines) > 0) {
            $allocated = 0;
            $lastIndex = count($lines) - 1;

            foreach (array_values($lines) as $index => $line) {
                if ($index === $lastIndex) {
                    $lineAllocations[$line['id']] = $totalDiscount - $allocated;
                } else {
                    $lineShare = intdiv($totalDiscount * $line['subtotal'], $subtotal);
                    $lineAllocations[$line['id']] = $lineShare;
                    $allocated += $lineShare;
                }
            }
        }

        return new DiscountResult(
            totalDiscount: $totalDiscount,
            freeShipping: $freeShipping,
            lineAllocations: $lineAllocations,
        );
    }
}
