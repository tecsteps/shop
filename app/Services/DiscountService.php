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
            throw new InvalidDiscountException('not_found');
        }

        if ($discount->status === DiscountStatus::Disabled) {
            throw new InvalidDiscountException('disabled');
        }

        if ($discount->status !== DiscountStatus::Active) {
            throw new InvalidDiscountException('disabled');
        }

        if ($discount->starts_at && $discount->starts_at->isFuture()) {
            throw new InvalidDiscountException('not_yet_active');
        }

        if ($discount->ends_at && $discount->ends_at->isPast()) {
            throw new InvalidDiscountException('expired');
        }

        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            throw new InvalidDiscountException('usage_limit_reached');
        }

        $subtotal = $cart->lines->sum('total');
        if ($discount->minimum_purchase !== null && $subtotal < $discount->minimum_purchase) {
            throw new InvalidDiscountException('minimum_not_met');
        }

        return $discount;
    }

    /**
     * @param  array<int, \App\Models\CartLine>  $lines
     */
    public function calculate(Discount $discount, int $subtotal, array $lines): DiscountResult
    {
        if ($discount->value_type === DiscountValueType::FreeShipping) {
            return new DiscountResult(amount: 0, freeShipping: true, allocations: []);
        }

        if ($discount->value_type === DiscountValueType::Fixed) {
            $amount = min($discount->value_amount, $subtotal);

            return new DiscountResult(amount: $amount, freeShipping: false, allocations: []);
        }

        // Percent
        $amount = (int) floor($subtotal * $discount->value_amount / 10000);

        return new DiscountResult(amount: $amount, freeShipping: false, allocations: []);
    }
}
