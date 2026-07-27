<?php

namespace App\Services;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\Store;
use App\ValueObjects\DiscountValidationResult;
use Illuminate\Support\Collection;

/**
 * Discount validation, amount calculation and proportional allocation
 * (spec 05 §7). All amounts are integers in minor units.
 */
class DiscountService
{
    /**
     * Validate a discount code for a cart (spec 05 §7.4 flow).
     * Codes match case-insensitively within the store.
     */
    public function validate(string $code, Store $store, Cart $cart): DiscountValidationResult
    {
        $discount = Discount::query()
            ->where('store_id', $store->id)
            ->where('type', DiscountType::Code)
            ->whereRaw('lower(code) = ?', [mb_strtolower($code)])
            ->first();

        if ($discount === null) {
            return DiscountValidationResult::invalid('discount_not_found', 'This discount code is invalid.');
        }

        if ($discount->status !== DiscountStatus::Active) {
            return DiscountValidationResult::invalid('discount_expired', 'This discount code has expired.');
        }

        if ($discount->starts_at !== null && $discount->starts_at->isFuture()) {
            return DiscountValidationResult::invalid('discount_not_yet_active', 'This discount code is not active yet.');
        }

        if ($discount->ends_at !== null && $discount->ends_at->isPast()) {
            return DiscountValidationResult::invalid('discount_expired', 'This discount code has expired.');
        }

        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            return DiscountValidationResult::invalid('discount_usage_limit_reached', 'This discount code has reached its usage limit.');
        }

        $minPurchase = $discount->rules_json['min_purchase_amount'] ?? null;

        if ($minPurchase !== null && $cart->subtotal() < (int) $minPurchase) {
            return DiscountValidationResult::invalid('discount_min_purchase_not_met', 'The cart does not meet the minimum purchase amount for this discount.');
        }

        if ($this->qualifyingLineIndexes($discount, $this->linesForCalculation($cart)) === []) {
            return DiscountValidationResult::invalid('discount_not_applicable', 'This discount does not apply to the items in your cart.');
        }

        return DiscountValidationResult::valid($discount);
    }

    /**
     * Calculate the discount amount and allocate it proportionally across
     * qualifying lines using the largest-remainder method (spec 05 §7.6):
     * every qualifying line except the last gets ROUND(total * share), the
     * last line gets the remainder.
     *
     * Percent discounts use integer truncation for the total
     * (subtotal * value / 100), per the spec 09 test tables.
     *
     * @param  array<int, array{product_id?: int|null, collection_ids?: array<int>, line_subtotal_amount: int}>  $lines
     * @return array{amount: int, allocations: array<int, int>, free_shipping: bool}
     */
    public function calculate(Discount $discount, int $subtotal, array $lines): array
    {
        if ($discount->value_type === DiscountValueType::FreeShipping) {
            return ['amount' => 0, 'allocations' => [], 'free_shipping' => true];
        }

        $qualifying = $this->qualifyingLineIndexes($discount, $lines);
        $qualifyingSubtotal = 0;

        foreach ($qualifying as $index) {
            $qualifyingSubtotal += $lines[$index]['line_subtotal_amount'];
        }

        if ($qualifying === [] || $qualifyingSubtotal <= 0) {
            return ['amount' => 0, 'allocations' => [], 'free_shipping' => false];
        }

        $total = match ($discount->value_type) {
            DiscountValueType::Percent => intdiv($qualifyingSubtotal * $discount->value_amount, 100),
            default => min($discount->value_amount, $qualifyingSubtotal),
        };

        $allocations = [];
        $remaining = $total;
        $lastIndex = end($qualifying);

        foreach ($qualifying as $index) {
            if ($index === $lastIndex) {
                $allocations[$index] = $remaining;
            } else {
                $lineDiscount = (int) round($total * $lines[$index]['line_subtotal_amount'] / $qualifyingSubtotal);
                $allocations[$index] = $lineDiscount;
                $remaining -= $lineDiscount;
            }
        }

        return ['amount' => $total, 'allocations' => $allocations, 'free_shipping' => false];
    }

    /**
     * Automatic discounts currently applicable to the cart, ordered by ID
     * for deterministic stacking (spec 05 §7.1).
     *
     * @return Collection<int, Discount>
     */
    public function getApplicableAutomaticDiscounts(Store $store, Cart $cart): Collection
    {
        $lines = $this->linesForCalculation($cart);
        $subtotal = $cart->subtotal();

        return Discount::query()
            ->where('store_id', $store->id)
            ->where('type', DiscountType::Automatic)
            ->active()
            ->orderBy('id')
            ->get()
            ->filter(function (Discount $discount) use ($subtotal, $lines): bool {
                if ($discount->starts_at !== null && $discount->starts_at->isFuture()) {
                    return false;
                }

                if ($discount->ends_at !== null && $discount->ends_at->isPast()) {
                    return false;
                }

                if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
                    return false;
                }

                $minPurchase = $discount->rules_json['min_purchase_amount'] ?? null;

                if ($minPurchase !== null && $subtotal < (int) $minPurchase) {
                    return false;
                }

                return $this->qualifyingLineIndexes($discount, $lines) !== [];
            })
            ->values();
    }

    /**
     * Indexes of the lines the discount applies to: union of
     * applicable_product_ids / applicable_collection_ids rules; when both
     * are empty the discount applies to every line.
     *
     * @param  array<int, array{product_id?: int|null, collection_ids?: array<int>}>  $lines
     * @return array<int, int>
     */
    public function qualifyingLineIndexes(Discount $discount, array $lines): array
    {
        $productIds = array_map('intval', $discount->rules_json['applicable_product_ids'] ?? []);
        $collectionIds = array_map('intval', $discount->rules_json['applicable_collection_ids'] ?? []);

        if ($productIds === [] && $collectionIds === []) {
            return array_keys($lines);
        }

        $qualifying = [];

        foreach ($lines as $index => $line) {
            $lineProductId = (int) ($line['product_id'] ?? 0);
            $lineCollectionIds = array_map('intval', $line['collection_ids'] ?? []);

            if (in_array($lineProductId, $productIds, true)
                || array_intersect($collectionIds, $lineCollectionIds) !== []) {
                $qualifying[] = $index;
            }
        }

        return $qualifying;
    }

    /**
     * Flat calculation representation of a cart's lines.
     *
     * @return array<int, array{variant_id: int, product_id: int|null, collection_ids: array<int>, line_subtotal_amount: int}>
     */
    private function linesForCalculation(Cart $cart): array
    {
        $cart->loadMissing('lines.variant.product.collections');

        return $cart->lines->values()->map(fn ($line): array => [
            'variant_id' => $line->variant_id,
            'product_id' => $line->variant?->product_id,
            'collection_ids' => $line->variant?->product?->collections->pluck('id')->all() ?? [],
            'line_subtotal_amount' => $line->line_subtotal_amount,
        ])->all();
    }
}
