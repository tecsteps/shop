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
    /**
     * Validate a discount code for a given store and cart.
     *
     * @throws InvalidDiscountException
     */
    public function validate(string $code, Store $store, Cart $cart): Discount
    {
        /** @var Discount|null $discount */
        $discount = Discount::query()
            ->withoutGlobalScopes()
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
            throw new InvalidDiscountException('not_found');
        }

        if ($discount->ends_at && $discount->ends_at->isPast()) {
            throw new InvalidDiscountException('expired');
        }

        if ($discount->starts_at && $discount->starts_at->isFuture()) {
            throw new InvalidDiscountException('not_yet_active');
        }

        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            throw new InvalidDiscountException('usage_limit_reached');
        }

        $cartSubtotal = $cart->lines->sum('line_subtotal_amount');

        if ($discount->minimum_purchase_amount !== null && $cartSubtotal < $discount->minimum_purchase_amount) {
            throw new InvalidDiscountException('minimum_not_met');
        }

        $rulesJson = $discount->rules_json ?? [];
        if (isset($rulesJson['minimum_purchase']) && $cartSubtotal < $rulesJson['minimum_purchase']) {
            throw new InvalidDiscountException('minimum_not_met');
        }

        return $discount;
    }

    /**
     * Calculate the discount amount and allocate proportionally across lines.
     *
     * @param  array<int, array{line_id: int, subtotal: int}>  $lines
     * @return array{amount: int, free_shipping: bool, allocations: array<int, int>}
     */
    public function calculate(Discount $discount, int $subtotal, array $lines = []): array
    {
        if ($discount->value_type === DiscountValueType::FreeShipping) {
            return [
                'amount' => 0,
                'free_shipping' => true,
                'allocations' => [],
            ];
        }

        $discountAmount = match ($discount->value_type) {
            DiscountValueType::Percent => (int) round($subtotal * $discount->value_amount / 100),
            default => min($discount->value_amount, $subtotal),
        };

        $allocations = $this->allocateProportionally($discountAmount, $lines);

        return [
            'amount' => $discountAmount,
            'free_shipping' => false,
            'allocations' => $allocations,
        ];
    }

    /**
     * Allocate a discount amount proportionally across line items.
     *
     * @param  array<int, array{line_id: int, subtotal: int}>  $lines
     * @return array<int, int>
     */
    private function allocateProportionally(int $discountAmount, array $lines): array
    {
        if (empty($lines) || $discountAmount === 0) {
            return [];
        }

        $totalSubtotal = array_sum(array_column($lines, 'subtotal'));
        if ($totalSubtotal === 0) {
            return [];
        }

        $allocations = [];
        $allocated = 0;

        foreach ($lines as $index => $line) {
            if ($index === count($lines) - 1) {
                $allocations[$line['line_id']] = $discountAmount - $allocated;
            } else {
                $lineAllocation = (int) round($discountAmount * $line['subtotal'] / $totalSubtotal);
                $allocations[$line['line_id']] = $lineAllocation;
                $allocated += $lineAllocation;
            }
        }

        return $allocations;
    }
}
