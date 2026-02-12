<?php

namespace App\Services;

use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class VariantMatrixService
{
    public function rebuild(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $product->load('options.values');
            $optionValues = $product->options
                ->sortBy('position')
                ->map(fn ($option) => $option->values->sortBy('position')->pluck('id')->all())
                ->filter(fn ($values) => ! empty($values))
                ->values()
                ->all();

            if (empty($optionValues)) {
                $this->ensureDefaultVariant($product);

                return;
            }

            $desiredCombos = $this->cartesianProduct($optionValues);
            $existingVariants = $product->variants()->with('optionValues')->get();

            $matchedVariantIds = [];
            $firstVariant = $existingVariants->first();

            foreach ($desiredCombos as $combo) {
                $comboSet = collect($combo)->sort()->values()->all();

                $matched = $existingVariants->first(function (ProductVariant $variant) use ($comboSet) {
                    $variantSet = $variant->optionValues->pluck('id')->sort()->values()->all();

                    return $variantSet === $comboSet;
                });

                if ($matched) {
                    $matchedVariantIds[] = $matched->id;
                } else {
                    $newVariant = $product->variants()->create([
                        'price_amount' => $firstVariant?->price_amount ?? 0,
                        'currency' => $firstVariant?->currency ?? $product->store?->default_currency ?? 'EUR',
                        'is_default' => false,
                        'status' => 'active',
                    ]);

                    $newVariant->optionValues()->sync($combo);
                    $matchedVariantIds[] = $newVariant->id;
                }
            }

            $orphanVariants = $existingVariants->whereNotIn('id', $matchedVariantIds);

            foreach ($orphanVariants as $variant) {
                $hasOrderLines = OrderLine::where('variant_id', $variant->id)->exists();

                if ($hasOrderLines) {
                    $variant->update(['status' => 'archived']);
                } else {
                    $variant->optionValues()->detach();
                    $variant->delete();
                }
            }
        });
    }

    /**
     * @param  list<list<int>>  $arrays
     * @return list<list<int>>
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

            $product->variants()->create([
                'price_amount' => $firstVariant?->price_amount ?? 0,
                'currency' => $firstVariant?->currency ?? $product->store?->default_currency ?? 'EUR',
                'is_default' => true,
                'status' => 'active',
            ]);
        }

        // Remove non-default variants with no order references
        $nonDefaults = $product->variants()->where('is_default', false)->get();

        foreach ($nonDefaults as $variant) {
            $hasOrderLines = OrderLine::where('variant_id', $variant->id)->exists();

            if ($hasOrderLines) {
                $variant->update(['status' => 'archived']);
            } else {
                $variant->optionValues()->detach();
                $variant->delete();
            }
        }
    }
}
