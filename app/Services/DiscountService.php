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
        $discount = Discount::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereRaw('LOWER(code) = ?', [strtolower($code)])
            ->first();

        if (! $discount) {
            throw new InvalidDiscountException('not_found');
        }

        if ($discount->status !== DiscountStatus::Active) {
            throw new InvalidDiscountException('expired');
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

        $rules = $discount->rules_json ?? [];
        $cart->load('lines');

        $subtotal = $cart->lines->sum('line_subtotal_amount');

        if (! empty($rules['min_purchase_amount']) && $subtotal < $rules['min_purchase_amount']) {
            throw new InvalidDiscountException('minimum_not_met');
        }

        $hasApplicableProducts = $this->hasApplicableProducts($discount, $cart);
        if (! $hasApplicableProducts) {
            throw new InvalidDiscountException('not_applicable');
        }

        return $discount;
    }

    /**
     * Calculate the discount amount for a given discount and cart subtotal.
     *
     * @param  list<array{line_subtotal_amount: int, product_id: int}>  $lines
     * @return array{amount: int, free_shipping: bool}
     */
    public function calculate(Discount $discount, int $subtotal, array $lines = []): array
    {
        if ($discount->value_type === DiscountValueType::FreeShipping) {
            return ['amount' => 0, 'free_shipping' => true];
        }

        $qualifyingSubtotal = $this->getQualifyingSubtotal($discount, $subtotal, $lines);

        if ($discount->value_type === DiscountValueType::Percent) {
            $amount = (int) round($qualifyingSubtotal * $discount->value_amount / 100);
        } else {
            $amount = min($discount->value_amount, $qualifyingSubtotal);
        }

        return ['amount' => $amount, 'free_shipping' => false];
    }

    /**
     * Allocate discount proportionally across qualifying lines.
     *
     * @param  list<array{line_subtotal_amount: int, product_id: int}>  $lines
     * @return list<int>
     */
    public function allocateToLines(int $totalDiscount, array $lines): array
    {
        if (empty($lines) || $totalDiscount <= 0) {
            return array_fill(0, count($lines), 0);
        }

        $totalSubtotal = array_sum(array_column($lines, 'line_subtotal_amount'));

        if ($totalSubtotal <= 0) {
            return array_fill(0, count($lines), 0);
        }

        $allocations = [];
        $remaining = $totalDiscount;

        for ($i = 0; $i < count($lines) - 1; $i++) {
            $allocation = (int) round($totalDiscount * $lines[$i]['line_subtotal_amount'] / $totalSubtotal);
            $allocations[] = $allocation;
            $remaining -= $allocation;
        }

        $allocations[] = $remaining;

        return $allocations;
    }

    /**
     * Increment the usage count of a discount.
     */
    public function incrementUsage(Discount $discount): void
    {
        $discount->increment('usage_count');
    }

    /**
     * Check if the cart has qualifying products for the discount rules.
     */
    private function hasApplicableProducts(Discount $discount, Cart $cart): bool
    {
        $rules = $discount->rules_json ?? [];

        $productIds = $rules['applicable_product_ids'] ?? null;
        $collectionIds = $rules['applicable_collection_ids'] ?? null;

        if (empty($productIds) && empty($collectionIds)) {
            return true;
        }

        $cart->load('lines.variant.product.collections');

        foreach ($cart->lines as $line) {
            $product = $line->variant->product;

            if (! empty($productIds) && in_array($product->id, $productIds)) {
                return true;
            }

            if (! empty($collectionIds)) {
                foreach ($product->collections as $collection) {
                    if (in_array($collection->id, $collectionIds)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get the qualifying subtotal based on discount rules.
     *
     * @param  list<array{line_subtotal_amount: int, product_id: int}>  $lines
     */
    private function getQualifyingSubtotal(Discount $discount, int $subtotal, array $lines): int
    {
        $rules = $discount->rules_json ?? [];
        $productIds = $rules['applicable_product_ids'] ?? null;
        $collectionIds = $rules['applicable_collection_ids'] ?? null;

        if (empty($productIds) && empty($collectionIds)) {
            return $subtotal;
        }

        if (empty($lines)) {
            return $subtotal;
        }

        $qualifying = 0;

        foreach ($lines as $line) {
            if (! empty($productIds) && in_array($line['product_id'], $productIds)) {
                $qualifying += $line['line_subtotal_amount'];
            }
        }

        return $qualifying > 0 ? $qualifying : $subtotal;
    }
}
