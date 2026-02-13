<?php

namespace App\Services;

use App\Enums\VariantStatus;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class VariantMatrixService
{
    public function rebuildMatrix(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $product->load(['options.values', 'variants.optionValues']);

            $options = $product->options()->orderBy('position')->with('values')->get();

            if ($options->isEmpty()) {
                $this->ensureDefaultVariant($product);

                return;
            }

            /** @var array<int, array<int, int>> $valueSets */
            $valueSets = $options->map(fn ($option) => $option->values->pluck('id')->toArray())->toArray();

            $combinations = $this->cartesian($valueSets);

            $existingVariants = $product->variants()->with('optionValues')->get();
            $matchedVariantIds = [];

            foreach ($combinations as $position => $combination) {
                $combinationSet = collect($combination)->sort()->values()->toArray();

                $matchedVariant = $existingVariants->first(function (ProductVariant $variant) use ($combinationSet): bool {
                    return $variant->optionValues->pluck('id')->sort()->values()->toArray() === $combinationSet;
                });

                if ($matchedVariant) {
                    $matchedVariant->update(['position' => $position, 'status' => VariantStatus::Active]);
                    $matchedVariantIds[] = $matchedVariant->id;
                } else {
                    $variant = $product->variants()->create([
                        'position' => $position,
                        'is_default' => $position === 0 && $existingVariants->where('is_default', true)->isEmpty(),
                        'status' => VariantStatus::Active,
                    ]);

                    $variant->optionValues()->attach($combination);

                    InventoryItem::withoutGlobalScopes()->create([
                        'store_id' => $product->store_id,
                        'variant_id' => $variant->id,
                        'quantity_on_hand' => 0,
                        'quantity_reserved' => 0,
                    ]);

                    $matchedVariantIds[] = $variant->id;
                }
            }

            $orphanedVariants = $existingVariants->whereNotIn('id', $matchedVariantIds);

            foreach ($orphanedVariants as $variant) {
                if ($this->variantHasOrders($variant)) {
                    $variant->update(['status' => VariantStatus::Archived]);
                } else {
                    $variant->delete();
                }
            }
        });
    }

    /**
     * @param  array<int, array<int, int>>  $sets
     * @return array<int, array<int, int>>
     */
    private function cartesian(array $sets): array
    {
        if (empty($sets)) {
            return [[]];
        }

        $result = [[]];

        foreach ($sets as $set) {
            $newResult = [];
            foreach ($result as $combo) {
                foreach ($set as $item) {
                    $newResult[] = array_merge($combo, [$item]);
                }
            }
            $result = $newResult;
        }

        return $result;
    }

    private function ensureDefaultVariant(Product $product): void
    {
        if ($product->variants()->where('is_default', true)->doesntExist()) {
            $variant = $product->variants()->create([
                'is_default' => true,
                'position' => 0,
                'status' => VariantStatus::Active,
            ]);

            InventoryItem::withoutGlobalScopes()->create([
                'store_id' => $product->store_id,
                'variant_id' => $variant->id,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
            ]);
        }
    }

    private function variantHasOrders(ProductVariant $variant): bool
    {
        // Order items table will be created in Phase 5.
        // For now, check if the table exists before querying.
        try {
            return DB::table('order_items')
                ->where('variant_id', $variant->id)
                ->exists();
        } catch (\Exception) {
            return false;
        }
    }
}
