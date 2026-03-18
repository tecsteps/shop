<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Enums\VariantStatus;
use App\Models\InventoryItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VariantMatrixService
{
    public function rebuildMatrix(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $product->load('options.values', 'variants.optionValues');

            $options = $product->options->sortBy('position');

            if ($options->isEmpty()) {
                $this->ensureDefaultVariant($product);

                return;
            }

            $valueGroups = $options->map(fn ($option) => $option->values->sortBy('position')->pluck('id')->all())->values()->all();

            $desiredCombos = $this->cartesianProduct($valueGroups);

            $existingVariants = $product->variants;
            $matchedVariantIds = [];

            foreach ($desiredCombos as $index => $combo) {
                $matched = $existingVariants->first(function ($variant) use ($combo) {
                    $variantValueIds = $variant->optionValues->pluck('id')->sort()->values()->all();

                    return $variantValueIds === collect($combo)->sort()->values()->all();
                });

                if ($matched) {
                    $matchedVariantIds[] = $matched->id;
                } else {
                    $referenceVariant = $existingVariants->first();
                    $variant = $product->variants()->create([
                        'price_amount' => $referenceVariant?->price_amount ?? 0,
                        'currency' => $referenceVariant?->currency ?? 'EUR',
                        'is_default' => false,
                        'position' => $index,
                        'status' => VariantStatus::Active,
                    ]);

                    $variant->optionValues()->attach($combo);

                    InventoryItem::withoutGlobalScopes()->create([
                        'store_id' => $product->store_id,
                        'variant_id' => $variant->id,
                        'quantity_on_hand' => 0,
                        'quantity_reserved' => 0,
                        'policy' => InventoryPolicy::Deny,
                    ]);

                    $matchedVariantIds[] = $variant->id;
                }
            }

            $orphanedVariants = $existingVariants->whereNotIn('id', $matchedVariantIds);

            foreach ($orphanedVariants as $variant) {
                if ($this->hasOrderReferences($variant->id)) {
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
    private function cartesianProduct(array $sets): array
    {
        if (empty($sets)) {
            return [[]];
        }

        $result = [[]];

        foreach ($sets as $set) {
            $newResult = [];
            foreach ($result as $existing) {
                foreach ($set as $value) {
                    $newResult[] = array_merge($existing, [$value]);
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
            $variant = $product->variants()->create([
                'price_amount' => 0,
                'currency' => 'EUR',
                'is_default' => true,
                'position' => 0,
            ]);

            InventoryItem::withoutGlobalScopes()->create([
                'store_id' => $product->store_id,
                'variant_id' => $variant->id,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
                'policy' => InventoryPolicy::Deny,
            ]);
        }
    }

    private function hasOrderReferences(int $variantId): bool
    {
        if (! Schema::hasTable('order_lines')) {
            return false;
        }

        return DB::table('order_lines')
            ->where('variant_id', $variantId)
            ->exists();
    }
}
