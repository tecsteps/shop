<?php

namespace App\Services;

use App\Enums\VariantStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class VariantMatrixService
{
    /**
     * Rebuild variant matrix from cartesian product of option values.
     * Creates missing variants and archives orphaned ones.
     */
    public function rebuildMatrix(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $product->load('options.values');

            $options = $product->options->sortBy('position');

            if ($options->isEmpty()) {
                return;
            }

            $valueGroups = $options->map(fn ($option) => $option->values->sortBy('position')->pluck('id')->all())->all();

            $combinations = $this->cartesianProduct($valueGroups);

            $existingVariants = $product->variants()
                ->with('optionValues')
                ->where('status', VariantStatus::Active)
                ->get();

            $existingCombinations = $existingVariants->map(function (ProductVariant $variant) {
                return $variant->optionValues->pluck('id')->sort()->values()->all();
            });

            $defaultVariant = $product->variants()->where('is_default', true)->first();
            $defaultPrice = $defaultVariant?->price_amount ?? 0;

            $position = $product->variants()->max('position') ?? 0;

            foreach ($combinations as $combination) {
                $sorted = collect($combination)->sort()->values()->all();

                $alreadyExists = $existingCombinations->contains(function ($existing) use ($sorted) {
                    return $existing === $sorted;
                });

                if (! $alreadyExists) {
                    $position++;

                    $variant = $product->variants()->create([
                        'price_amount' => $defaultPrice,
                        'is_default' => false,
                        'position' => $position,
                        'status' => VariantStatus::Active,
                    ]);

                    $variant->optionValues()->attach($combination);
                }
            }

            $validCombinations = collect($combinations)->map(fn ($c) => collect($c)->sort()->values()->all());

            foreach ($existingVariants as $variant) {
                if ($variant->is_default && $variant->optionValues->isEmpty()) {
                    continue;
                }

                $variantCombo = $variant->optionValues->pluck('id')->sort()->values()->all();

                $isValid = $validCombinations->contains(fn ($valid) => $valid === $variantCombo);

                if (! $isValid) {
                    $variant->update(['status' => VariantStatus::Archived]);
                }
            }
        });
    }

    /**
     * Compute cartesian product of multiple arrays.
     *
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
}
