<?php

namespace App\Services;

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\Store;
use App\ValueObjects\DiscountValidationResult;
use Illuminate\Support\Facades\DB;

class DiscountService
{
    public function validate(string $code, Store $store, Cart $cart): DiscountValidationResult
    {
        $discount = Discount::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereRaw('LOWER(code) = ?', [strtolower($code)])
            ->first();

        if (! $discount) {
            return DiscountValidationResult::failure('discount_not_found', 'Discount code not found.');
        }

        if ($discount->status !== DiscountStatus::Active) {
            return DiscountValidationResult::failure('discount_expired', 'This discount is no longer available.');
        }

        if ($discount->starts_at && $discount->starts_at->isFuture()) {
            return DiscountValidationResult::failure('discount_not_yet_active', 'This discount is not yet active.');
        }

        if ($discount->ends_at && $discount->ends_at->isPast()) {
            return DiscountValidationResult::failure('discount_expired', 'This discount has expired.');
        }

        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            return DiscountValidationResult::failure('discount_usage_limit_reached', 'This discount has reached its usage limit.');
        }

        $rules = $discount->rules_json ?? [];

        $subtotal = $cart->lines->sum('line_subtotal_amount');
        $minPurchase = $rules['min_purchase_amount'] ?? null;

        if ($minPurchase !== null && $subtotal < $minPurchase) {
            return DiscountValidationResult::failure(
                'discount_min_purchase_not_met',
                "Minimum purchase of {$minPurchase} cents required."
            );
        }

        $applicableProductIds = $rules['applicable_product_ids'] ?? null;
        $applicableCollectionIds = $rules['applicable_collection_ids'] ?? null;

        if (! empty($applicableProductIds) || ! empty($applicableCollectionIds)) {
            $hasQualifyingLine = $this->hasQualifyingLines($cart, $applicableProductIds, $applicableCollectionIds);

            if (! $hasQualifyingLine) {
                return DiscountValidationResult::failure('discount_not_applicable', 'No qualifying products in cart.');
            }
        }

        return DiscountValidationResult::success($discount);
    }

    /**
     * @param  array<int>  $qualifyingProductIds
     * @return array{total_discount: int, line_discounts: array<int, int>}
     */
    public function calculate(Discount $discount, int $subtotal, array $lines, ?array $qualifyingProductIds = null): array
    {
        if ($discount->value_type === DiscountValueType::FreeShipping) {
            return ['total_discount' => 0, 'line_discounts' => []];
        }

        $rules = $discount->rules_json ?? [];
        $applicableProductIds = $qualifyingProductIds ?? $rules['applicable_product_ids'] ?? null;
        $applicableCollectionIds = $rules['applicable_collection_ids'] ?? null;

        $qualifyingLines = [];
        $qualifyingSubtotal = 0;

        foreach ($lines as $line) {
            $isQualifying = true;

            if (! empty($applicableProductIds) || ! empty($applicableCollectionIds)) {
                $productId = $line['product_id'] ?? null;
                $productCollectionIds = $line['collection_ids'] ?? [];

                $matchesProduct = ! empty($applicableProductIds) && in_array($productId, $applicableProductIds);
                $matchesCollection = ! empty($applicableCollectionIds) && ! empty(array_intersect($productCollectionIds, $applicableCollectionIds));

                $isQualifying = $matchesProduct || $matchesCollection;
            }

            if ($isQualifying) {
                $qualifyingLines[] = $line;
                $qualifyingSubtotal += $line['line_subtotal_amount'];
            }
        }

        if ($qualifyingSubtotal === 0) {
            return ['total_discount' => 0, 'line_discounts' => []];
        }

        $totalDiscount = match ($discount->value_type) {
            DiscountValueType::Percent => (int) round($qualifyingSubtotal * $discount->value_amount / 100),
            DiscountValueType::Fixed => min($discount->value_amount, $qualifyingSubtotal),
            default => 0,
        };

        $lineDiscounts = [];
        $remainingDiscount = $totalDiscount;
        $lastIndex = count($qualifyingLines) - 1;

        foreach ($qualifyingLines as $index => $line) {
            if ($index === $lastIndex) {
                $lineDiscounts[$line['line_id']] = $remainingDiscount;
            } else {
                $lineDiscount = (int) round($totalDiscount * $line['line_subtotal_amount'] / $qualifyingSubtotal);
                $lineDiscounts[$line['line_id']] = $lineDiscount;
                $remainingDiscount -= $lineDiscount;
            }
        }

        return ['total_discount' => $totalDiscount, 'line_discounts' => $lineDiscounts];
    }

    /**
     * @param  array<int>|null  $applicableProductIds
     * @param  array<int>|null  $applicableCollectionIds
     */
    protected function hasQualifyingLines(Cart $cart, ?array $applicableProductIds, ?array $applicableCollectionIds): bool
    {
        foreach ($cart->lines as $line) {
            $variant = $line->variant;

            if (! $variant || ! $variant->product) {
                continue;
            }

            $productId = $variant->product_id;

            if (! empty($applicableProductIds) && in_array($productId, $applicableProductIds)) {
                return true;
            }

            if (! empty($applicableCollectionIds)) {
                $productCollections = DB::table('collection_products')
                    ->where('product_id', $productId)
                    ->pluck('collection_id')
                    ->toArray();

                if (! empty(array_intersect($productCollections, $applicableCollectionIds))) {
                    return true;
                }
            }
        }

        return false;
    }
}
