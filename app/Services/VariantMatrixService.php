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
            $options = $product->options()->orderBy('position')->with('values')->get();

            if ($options->isEmpty()) {
                $this->ensureDefaultVariant($product);

                return;
            }

            $optionValueGroups = $options->map(fn ($option) => $option->values->pluck('id')->all())->all();

            $desiredCombos = $this->cartesianProduct($optionValueGroups);

            $existingVariants = $product->variants()
                ->with('optionValues')
                ->get();

            $existingMap = [];
            foreach ($existingVariants as $variant) {
                $key = $this->comboKey($variant->optionValues->pluck('id')->sort()->values()->all());
                $existingMap[$key] = $variant;
            }

            $firstVariant = $existingVariants->first();
            $defaultPrice = $firstVariant ? $firstVariant->price_amount : 0;

            $matchedKeys = [];
            $position = 0;

            foreach ($desiredCombos as $combo) {
                $key = $this->comboKey(collect($combo)->sort()->values()->all());
                $matchedKeys[] = $key;

                if (isset($existingMap[$key])) {
                    $existingMap[$key]->update(['position' => $position]);
                    $position++;

                    continue;
                }

                $variant = ProductVariant::query()->create([
                    'product_id' => $product->id,
                    'price_amount' => $defaultPrice,
                    'is_default' => false,
                    'position' => $position,
                    'status' => VariantStatus::Active,
                ]);

                $variant->optionValues()->attach($combo);

                InventoryItem::query()->create([
                    'store_id' => $product->store_id,
                    'variant_id' => $variant->id,
                ]);

                $position++;
            }

            $hasOrderLinesTable = Schema::hasTable('order_lines');

            foreach ($existingMap as $key => $variant) {
                if (in_array((string) $key, $matchedKeys, true)) {
                    continue;
                }

                $hasOrderReferences = $hasOrderLinesTable && DB::table('order_lines')
                    ->where('variant_id', $variant->id)
                    ->exists();

                if ($hasOrderReferences) {
                    $variant->update(['status' => VariantStatus::Archived]);
                } else {
                    $variant->delete();
                }
            }
        });
    }

    /**
     * @param  array<int, array<int, int>>  $arrays
     * @return array<int, array<int, int>>
     */
    private function cartesianProduct(array $arrays): array
    {
        if (empty($arrays)) {
            return [[]];
        }

        $result = [[]];

        foreach ($arrays as $values) {
            $append = [];
            foreach ($result as $combo) {
                foreach ($values as $value) {
                    $append[] = array_merge($combo, [$value]);
                }
            }
            $result = $append;
        }

        return $result;
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function comboKey(array $ids): string
    {
        return 'v:'.implode('-', $ids);
    }

    private function ensureDefaultVariant(Product $product): void
    {
        $hasDefault = $product->variants()->where('is_default', true)->exists();

        if (! $hasDefault) {
            $variant = ProductVariant::query()->create([
                'product_id' => $product->id,
                'price_amount' => 0,
                'is_default' => true,
                'position' => 0,
                'status' => VariantStatus::Active,
            ]);

            InventoryItem::query()->create([
                'store_id' => $product->store_id,
                'variant_id' => $variant->id,
            ]);
        }
    }
}
