<?php

namespace App\Services;

use App\Enums\VariantStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VariantMatrixService
{
    /**
     * Rebuild the variant matrix for a product based on its current options and values.
     *
     * Creates new variants for missing option value combinations, preserves existing
     * variants that match, and archives or deletes orphaned variants.
     */
    public function rebuildMatrix(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $product->load(['options.values', 'variants.optionValues']);

            $options = $product->options->sortBy('position');

            if ($options->isEmpty()) {
                $this->ensureDefaultVariant($product);

                return;
            }

            $optionValueGroups = $options->map(fn ($option) => $option->values->pluck('id')->toArray())->toArray();

            $desiredCombos = $this->cartesianProduct($optionValueGroups);
            $existingVariants = $product->variants;

            $matchedVariantIds = [];

            foreach ($desiredCombos as $combo) {
                $comboSet = collect($combo)->sort()->values()->toArray();
                $matchingVariant = $existingVariants->first(function (ProductVariant $variant) use ($comboSet) {
                    $variantValues = $variant->optionValues->pluck('id')->sort()->values()->toArray();

                    return $variantValues === $comboSet;
                });

                if ($matchingVariant) {
                    $matchedVariantIds[] = $matchingVariant->id;
                } else {
                    $this->createVariantForCombo($product, $combo);
                }
            }

            foreach ($existingVariants as $variant) {
                if (in_array($variant->id, $matchedVariantIds)) {
                    continue;
                }

                if ($variant->is_default && $options->isNotEmpty()) {
                    $this->removeOrArchiveVariant($variant);

                    continue;
                }

                $this->removeOrArchiveVariant($variant);
            }
        });
    }

    /**
     * Ensure a default variant exists when the product has no options.
     */
    private function ensureDefaultVariant(Product $product): void
    {
        $defaultVariant = $product->variants()->where('is_default', true)->first();

        if (! $defaultVariant) {
            $variant = $product->variants()->create([
                'is_default' => true,
                'position' => 0,
                'status' => VariantStatus::Active,
                'currency' => $product->store->default_currency ?? 'USD',
            ]);

            $variant->inventoryItem()->create([
                'store_id' => $product->store_id,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
            ]);
        }
    }

    /**
     * Create a new variant for a specific combination of option values.
     *
     * @param  array<int>  $combo
     */
    private function createVariantForCombo(Product $product, array $combo): void
    {
        $referenceVariant = $product->variants()->first();
        $priceAmount = $referenceVariant?->price_amount ?? 0;
        $currency = $referenceVariant?->currency ?? ($product->store->default_currency ?? 'USD');

        $maxPosition = $product->variants()->max('position') ?? -1;

        $variant = $product->variants()->create([
            'price_amount' => $priceAmount,
            'currency' => $currency,
            'is_default' => false,
            'position' => $maxPosition + 1,
            'status' => VariantStatus::Active,
        ]);

        $variant->optionValues()->attach($combo);

        $variant->inventoryItem()->create([
            'store_id' => $product->store_id,
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
        ]);
    }

    /**
     * Remove or archive a variant depending on whether it has order references.
     */
    private function removeOrArchiveVariant(ProductVariant $variant): void
    {
        $hasOrderReferences = Schema::hasTable('order_lines')
            && DB::table('order_lines')
                ->where('variant_id', $variant->id)
                ->exists();

        if ($hasOrderReferences) {
            $variant->update(['status' => VariantStatus::Archived]);
        } else {
            $variant->delete();
        }
    }

    /**
     * Compute the cartesian product of multiple arrays.
     *
     * @param  array<array<int>>  $sets
     * @return array<array<int>>
     */
    private function cartesianProduct(array $sets): array
    {
        if (empty($sets)) {
            return [[]];
        }

        $result = [[]];

        foreach ($sets as $set) {
            $newResult = [];

            foreach ($result as $existing) {
                foreach ($set as $item) {
                    $newResult[] = array_merge($existing, [$item]);
                }
            }

            $result = $newResult;
        }

        return $result;
    }
}
