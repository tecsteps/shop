<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Enums\VariantStatus;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use App\Support\OrderReferenceChecker;
use Illuminate\Support\Facades\DB;

class VariantMatrixService
{
    public function __construct(private OrderReferenceChecker $orderReferences) {}

    /**
     * Rebuild the variant matrix from the product's current options.
     *
     * Computes the cartesian product of option values, preserves variants
     * whose option value combination still exists (prices, SKUs, and
     * inventory intact), creates missing combinations, and removes orphans:
     * archived when referenced by order lines, deleted otherwise.
     */
    public function rebuildMatrix(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $product->load(['options.values', 'variants.optionValues']);

            $optionValueIdSets = $product->options
                ->map(fn (ProductOption $option): array => $option->values->pluck('id')->all())
                ->filter(fn (array $ids): bool => $ids !== [])
                ->values()
                ->all();

            if ($optionValueIdSets === []) {
                $this->ensureDefaultVariant($product);

                return;
            }

            $desiredCombinations = $this->cartesianProduct($optionValueIdSets);

            $existingByCombination = $product->variants->keyBy(
                fn (ProductVariant $variant): string => $this->combinationKey($variant->optionValues->pluck('id')->all()),
            );

            $template = $product->variants->first();
            $nextPosition = $product->variants->isEmpty() ? 0 : (int) $product->variants->max('position') + 1;
            $matchedKeys = [];

            foreach ($desiredCombinations as $combination) {
                $key = $this->combinationKey($combination);

                if ($existingByCombination->has($key)) {
                    $matchedKeys[$key] = true;

                    continue;
                }

                $this->createVariantForCombination($product, $combination, $template, $nextPosition);
                $nextPosition++;
            }

            foreach ($existingByCombination as $key => $variant) {
                if (isset($matchedKeys[$key])) {
                    continue;
                }

                if ($this->orderReferences->variantHasOrderReferences($variant)) {
                    $variant->update(['status' => VariantStatus::Archived]);
                } else {
                    $variant->delete();
                }
            }
        });
    }

    /**
     * Auto-create the single default variant for a product without options.
     */
    private function ensureDefaultVariant(Product $product): ProductVariant
    {
        $existing = $product->variants->first();

        if ($existing !== null) {
            return $existing;
        }

        $variant = $product->variants()->create([
            'price_amount' => 0,
            'currency' => $product->store->default_currency,
            'is_default' => true,
            'position' => 0,
            'status' => VariantStatus::Active,
        ]);

        $this->createInventoryItem($product, $variant);

        return $variant;
    }

    /**
     * @param  list<int>  $combination
     */
    private function createVariantForCombination(
        Product $product,
        array $combination,
        ?ProductVariant $template,
        int $position,
    ): void {
        $variant = $product->variants()->create([
            'price_amount' => $template?->price_amount ?? 0,
            'compare_at_amount' => $template?->compare_at_amount,
            'currency' => $template?->currency ?? $product->store->default_currency,
            'weight_g' => $template?->weight_g,
            'requires_shipping' => $template?->requires_shipping ?? true,
            'is_default' => false,
            'position' => $position,
            'status' => VariantStatus::Active,
        ]);

        $variant->optionValues()->attach($combination);

        $this->createInventoryItem($product, $variant);
    }

    private function createInventoryItem(Product $product, ProductVariant $variant): void
    {
        $variant->inventoryItem()->create([
            'store_id' => $product->store_id,
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
            'policy' => InventoryPolicy::Deny,
        ]);
    }

    /**
     * @param  list<list<int>>  $sets
     * @return list<list<int>>
     */
    private function cartesianProduct(array $sets): array
    {
        $combinations = [[]];

        foreach ($sets as $set) {
            $next = [];

            foreach ($combinations as $combination) {
                foreach ($set as $value) {
                    $next[] = [...$combination, $value];
                }
            }

            $combinations = $next;
        }

        return $combinations;
    }

    /**
     * @param  list<int>  $optionValueIds
     */
    private function combinationKey(array $optionValueIds): string
    {
        sort($optionValueIds);

        return implode('-', $optionValueIds);
    }
}
