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
            $product->load('options.values');
            $options = $product->options;

            if ($options->isEmpty()) {
                $this->ensureDefaultVariant($product);

                return;
            }

            $optionValues = $options->map(fn ($option) => $option->values->pluck('id')->all())->all();
            $desiredCombos = $this->cartesianProduct($optionValues);

            $existingVariants = $product->variants()->with('optionValues')->get();

            $firstVariant = $existingVariants->first();
            $defaultPrice = $firstVariant ? $firstVariant->price_amount : 0;
            $defaultCurrency = $firstVariant ? $firstVariant->currency : $product->store->default_currency ?? 'USD';

            $matched = [];

            foreach ($desiredCombos as $position => $combo) {
                $comboSet = collect($combo)->sort()->values()->all();

                $existing = $existingVariants->first(function ($variant) use ($comboSet) {
                    $variantSet = $variant->optionValues->pluck('id')->sort()->values()->all();

                    return $variantSet === $comboSet;
                });

                if ($existing) {
                    $matched[] = $existing->id;
                    $existing->update(['position' => $position]);
                } else {
                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'price_amount' => $defaultPrice,
                        'currency' => $defaultCurrency,
                        'is_default' => false,
                        'position' => $position,
                        'status' => VariantStatus::Active,
                    ]);

                    $variant->optionValues()->sync($combo);

                    InventoryItem::create([
                        'store_id' => $product->store_id,
                        'variant_id' => $variant->id,
                        'quantity_on_hand' => 0,
                        'quantity_reserved' => 0,
                    ]);

                    $matched[] = $variant->id;
                }
            }

            $orphaned = $existingVariants->whereNotIn('id', $matched);

            foreach ($orphaned as $variant) {
                if ($this->variantHasOrderReferences($variant)) {
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

    private function ensureDefaultVariant(Product $product): void
    {
        $hasDefault = $product->variants()->where('is_default', true)->exists();

        if (! $hasDefault) {
            $firstVariant = $product->variants()->first();
            $defaultPrice = $firstVariant ? $firstVariant->price_amount : 0;
            $defaultCurrency = $firstVariant ? $firstVariant->currency : 'USD';

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'price_amount' => $defaultPrice,
                'currency' => $defaultCurrency,
                'is_default' => true,
                'position' => 0,
            ]);

            InventoryItem::create([
                'store_id' => $product->store_id,
                'variant_id' => $variant->id,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
            ]);
        }
    }

    private function variantHasOrderReferences(ProductVariant $variant): bool
    {
        if (! Schema::hasTable('order_lines')) {
            return false;
        }

        return DB::table('order_lines')
            ->where('variant_id', $variant->id)
            ->exists();
    }
}
