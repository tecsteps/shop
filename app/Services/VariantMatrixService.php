<?php

namespace App\Services;

use App\Enums\VariantStatus;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VariantMatrixService
{
    public function rebuildMatrix(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $product->load(['options.values', 'variants.optionValues']);

            $optionValueGroups = $product->options
                ->map(fn ($option) => $option->values->pluck('id')->all())
                ->filter(fn ($group) => ! empty($group))
                ->all();

            if (empty($optionValueGroups)) {
                $this->ensureDefaultVariant($product);

                return;
            }

            $desiredCombos = $this->cartesianProduct($optionValueGroups);
            $existingVariants = $product->variants()->with('optionValues')->get();

            $matchedVariantIds = [];
            $referenceVariant = $existingVariants->first();

            foreach ($desiredCombos as $position => $combo) {
                $comboSorted = collect($combo)->sort()->values()->all();

                $matchingVariant = $existingVariants->first(function ($variant) use ($comboSorted) {
                    $variantValues = $variant->optionValues->pluck('id')->sort()->values()->all();

                    return $variantValues === $comboSorted;
                });

                if ($matchingVariant) {
                    $matchedVariantIds[] = $matchingVariant->id;
                } else {
                    $newVariant = ProductVariant::query()->create([
                        'product_id' => $product->id,
                        'price_amount' => $referenceVariant?->price_amount ?? 0,
                        'currency' => $referenceVariant?->currency ?? 'USD',
                        'weight_g' => $referenceVariant?->weight_g,
                        'requires_shipping' => $referenceVariant?->requires_shipping ?? true,
                        'is_default' => false,
                        'position' => $position,
                    ]);

                    $newVariant->optionValues()->attach($combo);

                    InventoryItem::query()->create([
                        'store_id' => $product->store_id,
                        'variant_id' => $newVariant->id,
                        'quantity_on_hand' => 0,
                        'quantity_reserved' => 0,
                    ]);

                    $matchedVariantIds[] = $newVariant->id;
                }
            }

            $orphanedVariants = $existingVariants->whereNotIn('id', $matchedVariantIds);

            foreach ($orphanedVariants as $variant) {
                $hasOrderReferences = Schema::hasTable('order_lines') && DB::table('order_lines')
                    ->where('variant_id', $variant->id)
                    ->exists();

                if ($hasOrderReferences) {
                    $variant->update(['status' => VariantStatus::Archived]);
                } else {
                    $variant->inventoryItem?->delete();
                    $variant->delete();
                }
            }

            if (! $product->variants()->where('is_default', true)->exists()) {
                $product->variants()->orderBy('position')->first()?->update(['is_default' => true]);
            }
        });
    }

    protected function ensureDefaultVariant(Product $product): void
    {
        if ($product->variants()->count() === 0) {
            $variant = ProductVariant::query()->create([
                'product_id' => $product->id,
                'price_amount' => 0,
                'currency' => 'USD',
                'is_default' => true,
                'position' => 0,
            ]);

            InventoryItem::query()->create([
                'store_id' => $product->store_id,
                'variant_id' => $variant->id,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
            ]);
        }
    }

    /**
     * @param  array<array<int>>  $arrays
     * @return array<array<int>>
     */
    protected function cartesianProduct(array $arrays): array
    {
        $result = [[]];

        foreach ($arrays as $values) {
            $newResult = [];
            foreach ($result as $combo) {
                foreach ($values as $value) {
                    $newResult[] = array_merge($combo, [$value]);
                }
            }
            $result = $newResult;
        }

        return $result;
    }
}
