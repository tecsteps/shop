<?php

namespace App\Services;

use App\Enums\VariantStatus;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class VariantMatrixService
{
    public function rebuildMatrix(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $product->load(['options.values', 'variants']);

            $options = $product->options->sortBy('position');

            if ($options->isEmpty()) {
                return;
            }

            $valueSets = $options->map(fn ($option) => $option->values->sortBy('position')->pluck('id')->all())->values()->all();

            $combinations = $this->cartesianProduct($valueSets);

            $existingVariants = $product->variants->keyBy(function ($variant) {
                return $variant->optionValues->pluck('id')->sort()->implode('-');
            });

            $matchedVariantIds = [];

            foreach ($combinations as $position => $combination) {
                $key = collect($combination)->sort()->implode('-');

                if ($existingVariants->has($key)) {
                    $matchedVariantIds[] = $existingVariants[$key]->id;
                } else {
                    $variant = $product->variants()->create([
                        'price_amount' => 0,
                        'position' => $position,
                        'status' => VariantStatus::Active,
                    ]);

                    $variant->optionValues()->attach($combination);

                    $variant->inventoryItem()->create([
                        'store_id' => $product->store_id,
                    ]);

                    $matchedVariantIds[] = $variant->id;
                }
            }

            $orphanedVariants = $product->variants()->whereNotIn('id', $matchedVariantIds)->get();

            foreach ($orphanedVariants as $variant) {
                $hasOrders = DB::table('order_lines')
                    ->where('variant_id', $variant->id)
                    ->exists();

                if ($hasOrders) {
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
            return [];
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
