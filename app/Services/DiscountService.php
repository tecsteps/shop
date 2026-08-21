<?php

namespace App\Services;

use App\Enums\DiscountValueType;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\Store;
use App\ValueObjects\DiscountResult;
use Illuminate\Support\Collection;

class DiscountService
{
    public function validate(string $code, Store $store, Cart $cart): Discount
    {
        $discount = Discount::withoutGlobalScopes()->where('store_id', $store->getKey())->whereRaw('lower(code) = ?', [strtolower($code)])->first();

        if ($discount === null) {
            throw new InvalidDiscountException('This discount code does not exist.', 'discount_not_found');
        }

        if ($discount->status !== 'active' || ($discount->ends_at !== null && $discount->ends_at->isPast())) {
            throw new InvalidDiscountException('This discount code has expired.', 'discount_expired');
        }

        if ($discount->starts_at !== null && $discount->starts_at->isFuture()) {
            throw new InvalidDiscountException('This discount code is not active yet.', 'discount_not_yet_active');
        }

        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            throw new InvalidDiscountException('This discount code has reached its usage limit.', 'discount_usage_limit_reached');
        }

        $cart->loadMissing('lines.variant.product.collections');
        $minimum = (int) ($discount->rules_json['min_purchase_amount'] ?? $discount->rules_json['minimum_purchase_amount'] ?? 0);

        if ((int) $cart->lines->sum('line_subtotal_amount') < $minimum) {
            throw new InvalidDiscountException('This discount requires a higher subtotal.', 'discount_min_purchase_not_met');
        }

        if ($this->qualifyingLines($discount, $cart->lines)->isEmpty()) {
            throw new InvalidDiscountException('This discount does not apply to the items in your cart.', 'discount_not_applicable');
        }

        return $discount;
    }

    /** @param array<int, array{line_id?: int, amount?: int, product_id?: int, collection_ids?: array<int, int>, quantity?: int}> $lines */
    public function calculate(Discount $discount, int $subtotal, array $lines): DiscountResult
    {
        $qualifyingLines = array_values(array_filter($lines, fn (array $line): bool => $this->lineQualifies($discount, $line)));
        $eligibleSubtotal = array_sum(array_map(fn (array $line): int => (int) ($line['amount'] ?? 0), $qualifyingLines));
        $amount = match ($discount->value_type) {
            DiscountValueType::Percent => intdiv($eligibleSubtotal * $discount->value_amount, 100),
            DiscountValueType::Fixed => min($discount->value_amount, $eligibleSubtotal),
            DiscountValueType::FreeShipping => 0,
        };
        $allocations = [];

        if ($amount > 0 && $eligibleSubtotal > 0) {
            $lastIndex = count($qualifyingLines) - 1;
            $allocated = 0;

            foreach ($qualifyingLines as $index => $line) {
                $lineAmount = (int) ($line['amount'] ?? 0);
                $allocation = $index === $lastIndex
                    ? $amount - $allocated
                    : min($amount - $allocated, intdiv((2 * $amount * $lineAmount) + $eligibleSubtotal, 2 * $eligibleSubtotal));
                $allocations[(int) ($line['line_id'] ?? 0)] = $allocation;
                $allocated += $allocation;
            }
        }

        return new DiscountResult($amount, $allocations, $discount->value_type === DiscountValueType::FreeShipping);
    }

    /** @param \Illuminate\Support\Collection<int, \App\Models\CartLine> $lines */
    private function qualifyingLines(Discount $discount, Collection $lines): Collection
    {
        return $lines->filter(function ($line) use ($discount): bool {
            return $this->lineQualifies($discount, [
                'product_id' => $line->variant?->product_id,
                'collection_ids' => $line->variant?->product?->collections?->modelKeys() ?? [],
            ]);
        });
    }

    /** @param array{product_id?: int|null, collection_ids?: array<int, int>} $line */
    private function lineQualifies(Discount $discount, array $line): bool
    {
        $rules = $discount->rules_json ?? [];
        $productIds = array_map('intval', array_filter($rules['applicable_product_ids'] ?? []));
        $collectionIds = array_map('intval', array_filter($rules['applicable_collection_ids'] ?? []));

        if ($productIds === [] && $collectionIds === []) {
            return true;
        }

        return in_array((int) ($line['product_id'] ?? 0), $productIds, true)
            || array_intersect($collectionIds, array_map('intval', $line['collection_ids'] ?? [])) !== [];
    }
}
