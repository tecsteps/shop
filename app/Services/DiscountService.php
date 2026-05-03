<?php

namespace App\Services;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
use App\Models\Store;
use App\ValueObjects\DiscountResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DiscountService
{
    public function validate(string $code, Store $store, Cart $cart): Discount
    {
        $discount = Discount::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('type', DiscountType::Code)
            ->whereRaw('lower(code) = ?', [Str::lower($code)])
            ->first();

        if ($discount === null) {
            throw new InvalidDiscountException('discount_not_found', 'This discount code does not exist.');
        }

        if ($discount->status !== DiscountStatus::Active) {
            throw new InvalidDiscountException('discount_expired', 'This discount code is not active.');
        }

        if ($discount->starts_at->isFuture()) {
            throw new InvalidDiscountException('discount_not_yet_active', 'This discount code is not active yet.');
        }

        if ($discount->ends_at !== null && $discount->ends_at->isPast()) {
            throw new InvalidDiscountException('discount_expired', 'This discount code has expired.');
        }

        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            throw new InvalidDiscountException('discount_usage_limit_reached', 'This discount code has reached its usage limit.');
        }

        $cart->loadMissing('lines.variant.product.collections');
        $subtotal = (int) $cart->lines->sum('line_subtotal_amount');
        $rules = $discount->rules_json ?? [];
        $minimum = $rules['min_purchase_amount'] ?? null;

        if ($minimum !== null && $subtotal < (int) $minimum) {
            throw new InvalidDiscountException('discount_min_purchase_not_met', 'This discount requires a larger cart subtotal.');
        }

        if ($this->qualifyingLines($cart->lines, $rules)->isEmpty()) {
            throw new InvalidDiscountException('discount_not_applicable', 'This discount does not apply to the cart items.');
        }

        return $discount;
    }

    /**
     * @param  iterable<CartLine>  $lines
     */
    public function calculate(Discount $discount, int $subtotal, iterable $lines): DiscountResult
    {
        if ($discount->value_type === DiscountValueType::FreeShipping) {
            return new DiscountResult(0, [], true);
        }

        $qualifyingLines = $this->qualifyingLines(collect($lines), $discount->rules_json ?? []);
        $qualifyingSubtotal = (int) $qualifyingLines->sum('line_subtotal_amount');

        if ($qualifyingSubtotal < 1 || $subtotal < 1) {
            return new DiscountResult(0, []);
        }

        $amount = match ($discount->value_type) {
            DiscountValueType::Percent => (int) round($qualifyingSubtotal * $discount->value_amount / 100),
            DiscountValueType::Fixed => min($discount->value_amount, $qualifyingSubtotal),
            DiscountValueType::FreeShipping => 0,
        };

        if ($amount < 1) {
            return new DiscountResult(0, []);
        }

        $remaining = $amount;
        $allocations = [];
        $lastIndex = $qualifyingLines->count() - 1;

        foreach ($qualifyingLines->values() as $index => $line) {
            $lineAmount = $index === $lastIndex
                ? $remaining
                : (int) round($amount * $line->line_subtotal_amount / $qualifyingSubtotal);

            $lineAmount = min($lineAmount, $line->line_subtotal_amount);
            $allocations[$line->id] = $lineAmount;
            $remaining -= $lineAmount;
        }

        return new DiscountResult(array_sum($allocations), $allocations);
    }

    /**
     * @param  Collection<int, CartLine>  $lines
     * @param  array<string, mixed>  $rules
     * @return Collection<int, CartLine>
     */
    private function qualifyingLines(Collection $lines, array $rules): Collection
    {
        $productIds = array_filter($rules['applicable_product_ids'] ?? []);
        $collectionIds = array_filter($rules['applicable_collection_ids'] ?? []);

        if ($productIds === [] && $collectionIds === []) {
            return $lines;
        }

        return $lines->filter(function (CartLine $line) use ($productIds, $collectionIds): bool {
            $product = $line->variant?->product;

            if ($product === null) {
                return false;
            }

            if ($productIds !== [] && in_array($product->id, $productIds, true)) {
                return true;
            }

            if ($collectionIds === []) {
                return false;
            }

            return $product->collections
                ->pluck('id')
                ->intersect($collectionIds)
                ->isNotEmpty();
        });
    }
}
