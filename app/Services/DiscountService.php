<?php

namespace App\Services;

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\Store;
use App\ValueObjects\DiscountResult;
use Illuminate\Support\Carbon;

/**
 * Validates discount codes and computes their monetary allocation.
 *
 * Validation is case-insensitive and yields stable reason codes via
 * {@see InvalidDiscountException::$reason}: not_found, expired, not_yet_active,
 * usage_limit_reached, minimum_not_met, not_applicable.
 *
 * Allocation distributes the discount proportionally across qualifying lines,
 * assigning any rounding remainder to the last qualifying line so the sum of
 * allocations exactly equals the total discount (largest-remainder method).
 */
class DiscountService
{
    /**
     * Validate a code against a cart, returning the matched discount.
     *
     * @throws InvalidDiscountException
     */
    public function validate(string $code, Store $store, Cart $cart): Discount
    {
        $discount = Discount::query()
            ->where('store_id', $store->id)
            ->whereRaw('LOWER(code) = ?', [mb_strtolower(trim($code))])
            ->first();

        if ($discount === null) {
            throw new InvalidDiscountException('not_found', "Discount code '{$code}' does not exist.");
        }

        $now = Carbon::now();

        if ($discount->status !== DiscountStatus::Active) {
            throw new InvalidDiscountException('expired', 'This discount is no longer active.');
        }

        if ($discount->starts_at !== null && $discount->starts_at->greaterThan($now)) {
            throw new InvalidDiscountException('not_yet_active', 'This discount is not yet active.');
        }

        if ($discount->ends_at !== null && $discount->ends_at->lessThan($now)) {
            throw new InvalidDiscountException('expired', 'This discount has expired.');
        }

        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            throw new InvalidDiscountException('usage_limit_reached', 'This discount has reached its usage limit.');
        }

        $minimum = $discount->minimumPurchaseAmount();

        if ($minimum !== null && $cart->subtotalAmount() < $minimum) {
            throw new InvalidDiscountException('minimum_not_met', 'The cart does not meet the minimum purchase amount.');
        }

        if (! $this->hasQualifyingLines($discount, $cart)) {
            throw new InvalidDiscountException('not_applicable', 'No items in the cart qualify for this discount.');
        }

        return $discount;
    }

    /**
     * Compute the discount amount and its per-line allocation.
     *
     * `$lines` is a map of line id to line subtotal (cents). When the discount
     * restricts to specific products/collections, only qualifying line ids
     * should be passed.
     *
     * @param  array<int, int>  $lines  line id => line subtotal amount
     */
    public function calculate(Discount $discount, int $subtotal, array $lines): DiscountResult
    {
        if ($discount->value_type === DiscountValueType::FreeShipping) {
            return new DiscountResult(0, true, []);
        }

        $qualifyingSubtotal = array_sum($lines);

        $totalDiscount = match ($discount->value_type) {
            DiscountValueType::Percent => intdiv($qualifyingSubtotal * $discount->value_amount, 100),
            DiscountValueType::Fixed => min($discount->value_amount, $qualifyingSubtotal),
            default => 0,
        };

        if ($totalDiscount <= 0 || $qualifyingSubtotal <= 0) {
            return new DiscountResult(0, false, []);
        }

        return new DiscountResult($totalDiscount, false, $this->allocate($totalDiscount, $lines, $qualifyingSubtotal));
    }

    /**
     * Largest-remainder allocation: each non-last line gets its proportional,
     * rounded share; the last line absorbs the remainder so the sum is exact.
     *
     * @param  array<int, int>  $lines  line id => line subtotal amount
     * @return array<int, int> line id => allocated discount amount
     */
    private function allocate(int $totalDiscount, array $lines, int $qualifyingSubtotal): array
    {
        $allocations = [];
        $remaining = $totalDiscount;
        $lineIds = array_keys($lines);
        $lastId = end($lineIds);

        foreach ($lines as $lineId => $lineSubtotal) {
            if ($lineId === $lastId) {
                $allocations[$lineId] = $remaining;

                continue;
            }

            $share = (int) round($totalDiscount * $lineSubtotal / $qualifyingSubtotal);
            $allocations[$lineId] = $share;
            $remaining -= $share;
        }

        return $allocations;
    }

    /**
     * Whether at least one cart line qualifies under the discount's product /
     * collection restrictions. Unrestricted discounts always qualify.
     */
    private function hasQualifyingLines(Discount $discount, Cart $cart): bool
    {
        $productIds = $discount->applicableProductIds();
        $collectionIds = $discount->applicableCollectionIds();

        if ($productIds === [] && $collectionIds === []) {
            return true;
        }

        return $this->qualifyingLineSubtotals($discount, $cart) !== [];
    }

    /**
     * Map of qualifying cart line id => line subtotal under the discount's
     * product/collection restrictions. When unrestricted, every line qualifies.
     *
     * @return array<int, int>
     */
    public function qualifyingLineSubtotals(Discount $discount, Cart $cart): array
    {
        $productIds = $discount->applicableProductIds();
        $collectionIds = $discount->applicableCollectionIds();
        $unrestricted = $productIds === [] && $collectionIds === [];

        $result = [];

        foreach ($cart->lines as $line) {
            $variant = $line->variant;
            $product = $variant?->product;

            if ($unrestricted) {
                $result[$line->id] = $line->line_subtotal_amount;

                continue;
            }

            if ($product === null) {
                continue;
            }

            $matchesProduct = in_array($product->id, $productIds, true);
            $matchesCollection = $collectionIds !== []
                && $product->collections()->whereIn('collections.id', $collectionIds)->exists();

            if ($matchesProduct || $matchesCollection) {
                $result[$line->id] = $line->line_subtotal_amount;
            }
        }

        return $result;
    }
}
