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
        $discount = Discount::query()
            ->where('store_id', $store->id)
            ->whereRaw('LOWER(code) = ?', [mb_strtolower($code)])
            ->first();

        if (! $discount) {
            throw new InvalidDiscountException('discount_not_found');
        }

        if ($discount->status !== DiscountStatus::Active || ($discount->ends_at && $discount->ends_at->isPast())) {
            throw new InvalidDiscountException('discount_expired');
        }

        if ($discount->starts_at->isFuture()) {
            throw new InvalidDiscountException('discount_not_yet_active');
        }

        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            throw new InvalidDiscountException('discount_usage_limit_reached');
        }

        $subtotal = $cart->lines()->sum('line_subtotal_amount');
        $minimum = $discount->rules_json['min_purchase_amount'] ?? null;

        if ($minimum !== null && $subtotal < $minimum) {
            throw new InvalidDiscountException('discount_min_purchase_not_met');
        }

        if ($this->qualifyingLines($discount, $cart->lines()->with('variant.product.collections')->get())->isEmpty()) {
            throw new InvalidDiscountException('discount_not_applicable');
        }

        return $discount;
    }

    /** @param Collection<int, CartLine>|array<int, CartLine> $lines */
    public function calculate(Discount $discount, int $subtotal, Collection|array $lines): DiscountResult
    {
        $lines = collect($lines);
        $minimum = $discount->rules_json['min_purchase_amount'] ?? null;

        if (($minimum !== null && $subtotal < $minimum)
            || ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit)) {
            return new DiscountResult(null, 0);
        }

        if ($discount->value_type === DiscountValueType::FreeShipping) {
            return new DiscountResult($discount, 0, [], true);
        }

        $qualifying = $this->qualifyingLines($discount, $lines);
        $qualifyingSubtotal = $qualifying->sum('line_subtotal_amount');

        if ($qualifyingSubtotal === 0) {
            return new DiscountResult($discount, 0);
        }

        $amount = $discount->value_type === DiscountValueType::Percent
            ? intdiv(($qualifyingSubtotal * $discount->value_amount) + 50, 100)
            : min($discount->value_amount, $qualifyingSubtotal);

        $remaining = $amount;
        $allocations = [];
        $lastIndex = $qualifying->count() - 1;

        foreach ($qualifying->values() as $index => $line) {
            $allocation = $index === $lastIndex
                ? $remaining
                : intdiv(($amount * $line->line_subtotal_amount) + intdiv($qualifyingSubtotal, 2), $qualifyingSubtotal);
            $allocation = min($allocation, $remaining);
            $allocations[$line->id] = $allocation;
            $remaining -= $allocation;
        }

        return new DiscountResult($discount, $amount, $allocations);
    }

    /** @param Collection<int, CartLine> $lines
     * @return Collection<int, CartLine>
     */
    private function qualifyingLines(Discount $discount, Collection $lines): Collection
    {
        $productIds = $discount->rules_json['applicable_product_ids'] ?? [];
        $collectionIds = $discount->rules_json['applicable_collection_ids'] ?? [];

        if ($productIds === [] && $collectionIds === []) {
            return $lines;
        }

        return $lines->filter(function (CartLine $line) use ($productIds, $collectionIds): bool {
            $product = $line->variant->product;

            return in_array($product->id, $productIds, true)
                || $product->collections->contains(fn ($collection): bool => in_array($collection->id, $collectionIds, true));
        });
    }
}
