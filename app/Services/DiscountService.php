<?php

namespace App\Services;

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
use App\Models\Store;
use App\ValueObjects\DiscountResult;
use Illuminate\Support\Collection;

class DiscountService
{
    public function validate(string $code, Store $store, Cart $cart): Discount
    {
        $code = trim($code);

        if ($code === '') {
            throw new InvalidDiscountException(InvalidDiscountException::CODE_NOT_FOUND);
        }

        $discount = Discount::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->whereRaw('LOWER(code) = ?', [strtolower($code)])
            ->first();

        if ($discount === null) {
            throw new InvalidDiscountException(InvalidDiscountException::CODE_NOT_FOUND);
        }

        if ($discount->status !== DiscountStatus::Active) {
            throw new InvalidDiscountException(InvalidDiscountException::CODE_EXPIRED);
        }

        $now = now();

        if ($discount->starts_at !== null && $discount->starts_at->greaterThan($now)) {
            throw new InvalidDiscountException(InvalidDiscountException::CODE_NOT_YET_ACTIVE);
        }

        if ($discount->ends_at !== null && $discount->ends_at->lessThan($now)) {
            throw new InvalidDiscountException(InvalidDiscountException::CODE_EXPIRED);
        }

        if (! $discount->hasUsageRemaining()) {
            throw new InvalidDiscountException(InvalidDiscountException::CODE_USAGE_LIMIT_REACHED);
        }

        $lines = $cart->lines()->get();
        $subtotal = (int) $lines->sum('line_subtotal_amount');

        $rules = $discount->rules_json ?? [];
        $minPurchase = $rules['min_purchase_amount'] ?? null;

        if ($minPurchase !== null && $subtotal < (int) $minPurchase) {
            throw new InvalidDiscountException(InvalidDiscountException::CODE_MIN_PURCHASE_NOT_MET);
        }

        $qualifyingLines = $this->qualifyingLines($lines, $rules);

        if ($qualifyingLines->isEmpty() && $discount->value_type !== DiscountValueType::FreeShipping) {
            throw new InvalidDiscountException(InvalidDiscountException::CODE_NOT_APPLICABLE);
        }

        return $discount;
    }

    public function calculate(Discount $discount, Cart $cart): DiscountResult
    {
        $lines = $cart->lines()->get();
        $rules = $discount->rules_json ?? [];
        $qualifying = $this->qualifyingLines($lines, $rules);

        if ($discount->value_type === DiscountValueType::FreeShipping) {
            return new DiscountResult($discount, 0, true, []);
        }

        $qualifyingSubtotal = (int) $qualifying->sum('line_subtotal_amount');

        if ($qualifyingSubtotal === 0) {
            return new DiscountResult($discount, 0, false, []);
        }

        $totalDiscount = $discount->value_type === DiscountValueType::Percent
            ? (int) floor($qualifyingSubtotal * (int) $discount->value_amount / 100)
            : min((int) $discount->value_amount, $qualifyingSubtotal);

        $allocations = [];
        $remaining = $totalDiscount;
        $qualifyingArr = $qualifying->values();
        $lastIndex = $qualifyingArr->count() - 1;

        foreach ($qualifyingArr as $i => $line) {
            if ($i === $lastIndex) {
                $amount = $remaining;
            } else {
                $amount = (int) floor($totalDiscount * $line->line_subtotal_amount / $qualifyingSubtotal);
                $remaining -= $amount;
            }

            $allocations[] = [
                'line_id' => (int) $line->getKey(),
                'amount' => $amount,
            ];
        }

        return new DiscountResult($discount, $totalDiscount, false, $allocations);
    }

    public function incrementUsage(Discount $discount): void
    {
        $discount->increment('usage_count');
    }

    /**
     * @param  Collection<int, CartLine>  $lines
     * @param  array<string, mixed>  $rules
     * @return Collection<int, CartLine>
     */
    protected function qualifyingLines(Collection $lines, array $rules): Collection
    {
        $productIds = $rules['applicable_product_ids'] ?? null;
        $collectionIds = $rules['applicable_collection_ids'] ?? null;

        if (empty($productIds) && empty($collectionIds)) {
            return $lines;
        }

        return $lines->filter(function (CartLine $line) use ($productIds, $collectionIds): bool {
            $variant = $line->variant()->with('product.collections')->first();

            if ($variant === null || $variant->product === null) {
                return false;
            }

            if (! empty($productIds) && in_array((int) $variant->product->getKey(), array_map('intval', $productIds), true)) {
                return true;
            }

            if (! empty($collectionIds)) {
                $lineCollectionIds = $variant->product->collections->pluck('id')->map(fn ($id): int => (int) $id)->all();

                if (count(array_intersect(array_map('intval', $collectionIds), $lineCollectionIds)) > 0) {
                    return true;
                }
            }

            return false;
        })->values();
    }
}
