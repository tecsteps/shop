<?php

namespace App\Services;

use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\Store;
use App\ValueObjects\DiscountResult;
use Illuminate\Support\Str;

class DiscountService
{
    public function validate(string $code, Store $store, Cart $cart): Discount
    {
        $discount = Discount::where('store_id', $store->id)
            ->where('type', 'code')
            ->whereRaw('LOWER(code) = ?', [Str::lower($code)])
            ->first();

        if (! $discount) {
            throw new InvalidDiscountException('discount_not_found', 'This discount code is not valid.');
        }

        if ($discount->status !== 'active') {
            throw new InvalidDiscountException('discount_expired', 'This discount code is no longer active.');
        }

        if ($discount->starts_at !== null && $discount->starts_at->isFuture()) {
            throw new InvalidDiscountException('discount_not_yet_active', 'This discount code is not active yet.');
        }

        if ($discount->ends_at !== null && $discount->ends_at->isPast()) {
            throw new InvalidDiscountException('discount_expired', 'This discount code has expired.');
        }

        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            throw new InvalidDiscountException('discount_usage_limit_reached', 'This discount code has reached its usage limit.');
        }

        $rules = $discount->rules_json ?? [];
        $subtotal = $this->cartSubtotal($cart);

        if (! empty($rules['min_purchase_amount']) && $subtotal < (int) $rules['min_purchase_amount']) {
            throw new InvalidDiscountException('discount_min_purchase_not_met', 'The minimum purchase amount has not been met.');
        }

        if (! $this->isApplicable($discount, $cart)) {
            throw new InvalidDiscountException('discount_not_applicable', 'This discount does not apply to the items in your cart.');
        }

        return $discount;
    }

    /**
     * @param  list<array{id: int, subtotal: int, product_id: int}>  $lines
     */
    public function calculate(Discount $discount, int $subtotal, array $lines): DiscountResult
    {
        $qualifying = $this->qualifyingLines($discount, $lines);
        $qualifyingSubtotal = array_sum(array_map(fn ($line) => $line['subtotal'], $qualifying));

        if ($discount->value_type === 'free_shipping') {
            return new DiscountResult(0, [], true);
        }

        if ($discount->value_type === 'percent') {
            $amount = intdiv($qualifyingSubtotal * $discount->value_amount, 100);
        } else {
            $amount = min((int) $discount->value_amount, $qualifyingSubtotal);
        }

        return new DiscountResult($amount, $this->allocate($amount, $qualifying, $qualifyingSubtotal));
    }

    public function cartSubtotal(Cart $cart): int
    {
        $cart->loadMissing('lines');

        return $cart->lines->sum(fn ($line) => $line->unit_price_amount * $line->quantity);
    }

    private function isApplicable(Discount $discount, Cart $cart): bool
    {
        $rules = $discount->rules_json ?? [];
        $productIds = $rules['applicable_product_ids'] ?? [];
        $collectionIds = $rules['applicable_collection_ids'] ?? [];

        if (empty($productIds) && empty($collectionIds)) {
            return true;
        }

        $cart->loadMissing('lines.variant.product.collections');

        $cartProductIds = $cart->lines->map(fn ($line) => $line->variant?->product_id)->filter()->all();

        if (! empty($productIds) && array_intersect($productIds, $cartProductIds) !== []) {
            return true;
        }

        if (! empty($collectionIds)) {
            foreach ($cart->lines as $line) {
                $product = $line->variant?->product;

                if ($product && $product->collections()->whereIn('collections.id', $collectionIds)->exists()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  list<array{id: int, subtotal: int, product_id: int}>  $lines
     * @return list<array{id: int, subtotal: int, product_id: int}>
     */
    private function qualifyingLines(Discount $discount, array $lines): array
    {
        $rules = $discount->rules_json ?? [];
        $productIds = $rules['applicable_product_ids'] ?? [];
        $collectionIds = $rules['applicable_collection_ids'] ?? [];

        if (empty($productIds) && empty($collectionIds)) {
            return $lines;
        }

        return array_values(array_filter($lines, function (array $line) use ($productIds, $collectionIds) {
            if (in_array($line['product_id'], $productIds, true)) {
                return true;
            }

            if (! empty($collectionIds)) {
                $product = \App\Models\Product::find($line['product_id']);

                return $product && $product->collections()->whereIn('collections.id', $collectionIds)->exists();
            }

            return false;
        }));
    }

    /**
     * @param  list<array{id: int, subtotal: int, product_id: int}>  $lines
     * @return array<int, int>
     */
    private function allocate(int $amount, array $lines, int $qualifyingSubtotal): array
    {
        if ($qualifyingSubtotal <= 0 || $lines === []) {
            return [];
        }

        $allocations = [];
        $remaining = $amount;
        $count = count($lines);

        foreach ($lines as $index => $line) {
            if ($index === $count - 1) {
                $allocations[$line['id']] = $remaining;
            } else {
                $lineDiscount = (int) round($amount * $line['subtotal'] / $qualifyingSubtotal);
                $remaining -= $lineDiscount;
                $allocations[$line['id']] = $lineDiscount;
            }
        }

        return $allocations;
    }
}
