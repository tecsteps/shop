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

class DiscountService
{
    /**
     * Validate a discount code against the store and cart (spec 05 section 7.4).
     *
     * @throws InvalidDiscountException
     */
    public function validate(string $code, Store $store, Cart $cart): Discount
    {
        $discount = Discount::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->whereRaw('LOWER(code) = ?', [mb_strtolower(trim($code))])
            ->first();

        if ($discount === null) {
            throw InvalidDiscountException::notFound();
        }

        if (in_array($discount->status, [DiscountStatus::Draft, DiscountStatus::Disabled], true)) {
            throw InvalidDiscountException::disabled();
        }

        if ($discount->starts_at !== null && $discount->starts_at->isFuture()) {
            throw InvalidDiscountException::notYetActive();
        }

        if ($discount->status === DiscountStatus::Expired
            || ($discount->ends_at !== null && $discount->ends_at->isPast())) {
            throw InvalidDiscountException::expired();
        }

        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            throw InvalidDiscountException::usageLimitReached();
        }

        $minimum = $discount->minimumPurchaseAmount();

        if ($minimum !== null && $cart->subtotalAmount() < $minimum) {
            throw InvalidDiscountException::minimumNotMet($minimum);
        }

        return $discount;
    }

    /**
     * Calculate the discount amount and its proportional allocation across
     * qualifying lines. The rounding remainder goes to the last qualifying
     * line (largest-remainder method, spec 05 section 7.6).
     *
     * @param  list<CartLine>  $lines
     */
    public function calculate(Discount $discount, int $subtotal, array $lines): DiscountResult
    {
        if ($discount->value_type === DiscountValueType::FreeShipping) {
            return new DiscountResult(0, true);
        }

        $qualifyingLines = $this->qualifyingLines($discount, $lines);

        $qualifyingSubtotal = 0;

        foreach ($qualifyingLines as $line) {
            $qualifyingSubtotal += $line->line_subtotal_amount;
        }

        if ($qualifyingLines === [] || $qualifyingSubtotal <= 0) {
            return DiscountResult::none();
        }

        $totalDiscount = match ($discount->value_type) {
            DiscountValueType::Percent => intdiv($qualifyingSubtotal * $discount->value_amount, 100),
            DiscountValueType::Fixed => min($discount->value_amount, $qualifyingSubtotal),
            DiscountValueType::FreeShipping => 0,
        };

        $allocations = [];
        $remaining = $totalDiscount;
        $lastIndex = count($qualifyingLines) - 1;

        foreach ($qualifyingLines as $index => $line) {
            if ($index === $lastIndex) {
                $lineDiscount = $remaining;
            } else {
                $lineDiscount = (int) round($totalDiscount * $line->line_subtotal_amount / $qualifyingSubtotal);
                $remaining -= $lineDiscount;
            }

            $allocations[$line->getKey() ?? $index] = $lineDiscount;
        }

        return new DiscountResult($totalDiscount, false, $allocations);
    }

    /**
     * Filter lines by the discount's product/collection restrictions. A line
     * qualifies when it matches either restriction (union). With no
     * restrictions, every line qualifies.
     *
     * @param  list<CartLine>  $lines
     * @return list<CartLine>
     */
    protected function qualifyingLines(Discount $discount, array $lines): array
    {
        $productIds = $discount->rules_json['applicable_product_ids'] ?? null;
        $collectionIds = $discount->rules_json['applicable_collection_ids'] ?? null;

        if (blank($productIds) && blank($collectionIds)) {
            return array_values($lines);
        }

        return array_values(array_filter($lines, function (CartLine $line) use ($productIds, $collectionIds): bool {
            $product = $line->variant?->product;

            if ($product === null) {
                return false;
            }

            if (filled($productIds) && in_array($product->getKey(), $productIds, false)) {
                return true;
            }

            if (filled($collectionIds)) {
                return $product->collections()
                    ->withoutGlobalScopes()
                    ->whereIn('collections.id', $collectionIds)
                    ->exists();
            }

            return false;
        }));
    }
}
