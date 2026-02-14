<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
use App\Models\Store;
use App\ValueObjects\DiscountCalculationResult;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;

final class DiscountService
{
    public function validate(string $code, Store $store, Cart $cart): Discount
    {
        $normalizedCode = trim($code);

        if ($normalizedCode === '') {
            throw InvalidDiscountException::notFound($code);
        }

        /** @var Discount|null $discount */
        $discount = Discount::query()
            ->where('store_id', '=', (int) $store->id)
            ->whereRaw('lower(code) = ?', [mb_strtolower($normalizedCode)])
            ->first();

        if ($discount === null) {
            throw InvalidDiscountException::notFound($normalizedCode);
        }

        if ($discount->status !== DiscountStatus::Active) {
            throw InvalidDiscountException::expired((string) $discount->code);
        }

        $now = Carbon::now();

        if ($discount->starts_at !== null && $discount->starts_at->gt($now)) {
            throw InvalidDiscountException::notYetActive((string) $discount->code);
        }

        if ($discount->ends_at !== null && $discount->ends_at->lt($now)) {
            throw InvalidDiscountException::expired((string) $discount->code);
        }

        if ($discount->usage_limit !== null && (int) $discount->usage_count >= (int) $discount->usage_limit) {
            throw InvalidDiscountException::usageLimitReached((string) $discount->code);
        }

        $rules = $this->rules($discount);
        $subtotal = $this->subtotal($cart);
        $minimum = $this->minimumPurchaseAmount($rules);

        if ($minimum !== null && $subtotal < $minimum) {
            throw InvalidDiscountException::minimumNotMet((string) $discount->code, $minimum);
        }

        $cart->loadMissing('lines.variant.product.collections');

        if ($this->hasRestrictions($rules)) {
            $qualifyingLines = $this->qualifyingLines($cart->lines, $rules);

            if ($qualifyingLines->isEmpty()) {
                throw InvalidDiscountException::notApplicable((string) $discount->code);
            }
        }

        return $discount;
    }

    public function calculate(Discount $discount, Cart $cart): DiscountCalculationResult
    {
        $cart->loadMissing('lines.variant.product.collections');
        $rules = $this->rules($discount);

        $qualifyingLines = $this->qualifyingLines($cart->lines->sortBy('id')->values(), $rules);
        $qualifyingSubtotal = (int) $qualifyingLines->sum(static fn (CartLine $line): int => (int) $line->line_subtotal_amount);

        if ($discount->value_type === DiscountValueType::FreeShipping) {
            return new DiscountCalculationResult(amount: 0, lineAllocations: [], freeShipping: true);
        }

        if ($qualifyingSubtotal <= 0 || $qualifyingLines->isEmpty()) {
            return DiscountCalculationResult::none();
        }

        $totalDiscount = $this->calculateTotalDiscount($discount, $qualifyingSubtotal);

        if ($totalDiscount <= 0) {
            return DiscountCalculationResult::none();
        }

        $allocations = [];
        $remainingDiscount = $totalDiscount;
        $lineCount = $qualifyingLines->count();

        /** @var CartLine $line */
        foreach ($qualifyingLines as $index => $line) {
            $lineId = (int) $line->id;

            if ($index === $lineCount - 1) {
                $allocations[$lineId] = $remainingDiscount;
                break;
            }

            $lineSubtotal = (int) $line->line_subtotal_amount;
            $proportional = (int) round(($totalDiscount * $lineSubtotal) / $qualifyingSubtotal);
            $allocation = max(0, min($proportional, $remainingDiscount));

            $allocations[$lineId] = $allocation;
            $remainingDiscount -= $allocation;
        }

        return new DiscountCalculationResult(
            amount: $totalDiscount,
            lineAllocations: $allocations,
            freeShipping: false,
        );
    }

    public function applyToCartLines(Cart $cart, DiscountCalculationResult $result): void
    {
        $cart->loadMissing('lines');

        /** @var CartLine $line */
        foreach ($cart->lines as $line) {
            $lineSubtotal = (int) $line->unit_price_amount * (int) $line->quantity;
            $lineDiscount = max(0, min($lineSubtotal, $result->amountForLine((int) $line->id)));
            $lineTotal = $lineSubtotal - $lineDiscount;

            $line->unit_price_amount = (int) $line->unit_price_amount;
            $line->line_subtotal_amount = $lineSubtotal;
            $line->line_discount_amount = $lineDiscount;
            $line->line_total_amount = $lineTotal;
            $line->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(Discount $discount): array
    {
        $rules = $discount->rules_json;

        return is_array($rules) ? $rules : [];
    }

    private function subtotal(Cart $cart): int
    {
        $cart->loadMissing('lines');

        return (int) $cart->lines->sum(static fn (CartLine $line): int => (int) $line->line_subtotal_amount);
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    private function minimumPurchaseAmount(array $rules): ?int
    {
        $minimum = $rules['min_purchase_amount'] ?? $rules['min_subtotal_amount'] ?? null;

        if (! is_int($minimum) && ! is_float($minimum) && ! is_string($minimum)) {
            return null;
        }

        return max(0, (int) $minimum);
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    private function hasRestrictions(array $rules): bool
    {
        return $this->productIds($rules) !== [] || $this->collectionIds($rules) !== [];
    }

    /**
     * @param  EloquentCollection<int, CartLine>  $lines
     * @param  array<string, mixed>  $rules
     * @return EloquentCollection<int, CartLine>
     */
    private function qualifyingLines(EloquentCollection $lines, array $rules): EloquentCollection
    {
        $productIds = $this->productIds($rules);
        $collectionIds = $this->collectionIds($rules);

        if ($productIds === [] && $collectionIds === []) {
            return $lines;
        }

        return $lines->filter(function (CartLine $line) use ($productIds, $collectionIds): bool {
            $productId = (int) ($line->variant?->product_id ?? 0);

            $matchesProduct = $productIds !== [] && in_array($productId, $productIds, true);

            $matchesCollection = false;

            if ($collectionIds !== [] && $line->variant?->product !== null) {
                $collectionIdSet = $line->variant->product->collections
                    ->pluck('id')
                    ->map(static fn (mixed $id): int => (int) $id)
                    ->all();

                $matchesCollection = $this->containsAny($collectionIdSet, $collectionIds);
            }

            return $matchesProduct || $matchesCollection;
        })->values();
    }

    private function calculateTotalDiscount(Discount $discount, int $qualifyingSubtotal): int
    {
        if ($discount->value_type === DiscountValueType::Percent) {
            return max(0, (int) round(($qualifyingSubtotal * (int) $discount->value_amount) / 100));
        }

        if ($discount->value_type === DiscountValueType::Fixed) {
            return min($qualifyingSubtotal, max(0, (int) $discount->value_amount));
        }

        return 0;
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return list<int>
     */
    private function productIds(array $rules): array
    {
        $raw = $rules['applicable_product_ids'] ?? null;

        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_unique(array_map(static fn (mixed $value): int => (int) $value, $raw)));
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return list<int>
     */
    private function collectionIds(array $rules): array
    {
        $raw = $rules['applicable_collection_ids'] ?? null;

        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_unique(array_map(static fn (mixed $value): int => (int) $value, $raw)));
    }

    /**
     * @param  list<int>  $haystack
     * @param  list<int>  $needles
     */
    private function containsAny(array $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (in_array($needle, $haystack, true)) {
                return true;
            }
        }

        return false;
    }
}
