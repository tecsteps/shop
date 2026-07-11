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
use Illuminate\Support\Str;

class DiscountService
{
    public function validate(string $code, Store $store, Cart $cart): Discount
    {
        $discount = Discount::query()
            ->where('store_id', $store->id)
            ->whereRaw('LOWER(code) = ?', [Str::lower(Str::squish($code))])
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
        $minimum = (int) ($discount->rules_json['min_purchase_amount'] ?? 0);

        if ($subtotal < $minimum) {
            throw new InvalidDiscountException('discount_min_purchase_not_met');
        }

        if ($this->qualifyingLines($discount, $cart->lines()->with('variant.product.collections')->get())->isEmpty()) {
            throw new InvalidDiscountException('discount_not_applicable');
        }

        return $discount;
    }

    /** @param iterable<CartLine> $lines */
    public function calculate(Discount $discount, int $subtotal, iterable $lines): DiscountResult
    {
        $qualifyingLines = $this->qualifyingLines($discount, collect($lines));

        if ($discount->value_type === DiscountValueType::FreeShipping) {
            return new DiscountResult(0, [], true);
        }

        $qualifyingSubtotal = (int) $qualifyingLines->sum('line_subtotal_amount');

        if ($qualifyingSubtotal <= 0) {
            return new DiscountResult(0, []);
        }

        $amount = $discount->value_type === DiscountValueType::Percent
            ? (int) round($qualifyingSubtotal * $discount->value_amount / 100)
            : min($discount->value_amount, $qualifyingSubtotal, $subtotal);

        $remaining = $amount;
        $allocations = [];

        foreach ($qualifyingLines->values() as $index => $line) {
            $isLast = $index === $qualifyingLines->count() - 1;
            $allocation = $isLast ? $remaining : (int) round($amount * $line->line_subtotal_amount / $qualifyingSubtotal);
            $allocation = min($allocation, $remaining, $line->line_subtotal_amount);
            $allocations[$line->id] = $allocation;
            $remaining -= $allocation;
        }

        return new DiscountResult($amount - $remaining, $allocations);
    }

    /** @param Collection<int, CartLine> $lines
     * @return Collection<int, CartLine>
     */
    private function qualifyingLines(Discount $discount, Collection $lines): Collection
    {
        $productIds = collect($discount->rules_json['applicable_product_ids'] ?? [])->filter();
        $collectionIds = collect($discount->rules_json['applicable_collection_ids'] ?? [])->filter();

        if ($productIds->isEmpty() && $collectionIds->isEmpty()) {
            return $lines;
        }

        return $lines->filter(function (CartLine $line) use ($productIds, $collectionIds): bool {
            $product = $line->variant->product;

            return $productIds->contains($product->id)
                || $product->collections->pluck('id')->intersect($collectionIds)->isNotEmpty();
        });
    }
}
