<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class VariantMatrixService
{
    public function rebuildMatrix(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $product->load(['options.values', 'variants.optionValues']);

            $options = $product->options;

            if ($options->isEmpty()) {
                $this->ensureDefaultVariant($product);

                return;
            }

            $valueSets = $options->map(fn ($option) => $option->values->pluck('id')->all())->all();

            $combinations = $this->cartesianProduct($valueSets);

            $existingVariants = $product->variants()
                ->with('optionValues')
                ->where('is_default', false)
                ->get();

            $firstExistingVariant = $existingVariants->first()
                ?? $product->variants()->where('is_default', true)->first();

            $defaultPrice = $firstExistingVariant?->price_amount ?? 0;
            $defaultCurrency = $firstExistingVariant?->currency ?? 'USD';

            $matchedVariantIds = [];

            foreach ($combinations as $position => $combo) {
                $comboSet = collect($combo)->sort()->values()->all();

                $matchedVariant = $existingVariants->first(function ($variant) use ($comboSet) {
                    $variantValueIds = $variant->optionValues->pluck('id')->sort()->values()->all();

                    return $variantValueIds === $comboSet;
                });

                if ($matchedVariant) {
                    $matchedVariantIds[] = $matchedVariant->id;
                } else {
                    $variant = $product->variants()->create([
                        'is_default' => false,
                        'position' => $position,
                        'price_amount' => $defaultPrice,
                        'currency' => $defaultCurrency,
                        'status' => 'active',
                    ]);

                    $variant->optionValues()->sync($combo);

                    InventoryItem::query()->create([
                        'store_id' => $product->store_id,
                        'variant_id' => $variant->id,
                        'quantity_on_hand' => 0,
                        'quantity_reserved' => 0,
                        'policy' => 'deny',
                    ]);

                    $matchedVariantIds[] = $variant->id;
                }
            }

            $defaultVariant = $product->variants()->where('is_default', true)->first();
            if ($defaultVariant && $options->isNotEmpty()) {
                $this->handleOrphanedVariant($defaultVariant);
            }

            $orphanedVariants = $existingVariants->filter(
                fn ($v) => ! in_array($v->id, $matchedVariantIds)
            );

            foreach ($orphanedVariants as $orphan) {
                $this->handleOrphanedVariant($orphan);
            }
        });
    }

    private function ensureDefaultVariant(Product $product): void
    {
        $defaultVariant = $product->variants()->where('is_default', true)->first();

        if (! $defaultVariant) {
            $variant = $product->variants()->create([
                'is_default' => true,
                'position' => 0,
                'price_amount' => 0,
                'currency' => 'USD',
                'status' => 'active',
            ]);

            InventoryItem::query()->create([
                'store_id' => $product->store_id,
                'variant_id' => $variant->id,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
                'policy' => 'deny',
            ]);
        }
    }

    private function handleOrphanedVariant(ProductVariant $variant): void
    {
        if ($this->hasOrderReferences($variant)) {
            $variant->update(['status' => 'archived']);
        } else {
            $variant->delete();
        }
    }

    private function hasOrderReferences(ProductVariant $variant): bool
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('order_lines')) {
            return false;
        }

        return DB::table('order_lines')
            ->where('variant_id', $variant->id)
            ->exists();
    }

    /**
     * @param  array<int, array<int, int>>  $sets
     * @return array<int, array<int, int>>
     */
    private function cartesianProduct(array $sets): array
    {
        if (empty($sets)) {
            return [[]];
        }

        $result = [[]];

        foreach ($sets as $set) {
            $append = [];

            foreach ($result as $existing) {
                foreach ($set as $item) {
                    $append[] = array_merge($existing, [$item]);
                }
            }

            $result = $append;
        }

        return $result;
    }
}
