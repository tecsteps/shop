<?php

namespace App\Services;

use App\Enums\VariantStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VariantMatrixService
{
    public function rebuildMatrix(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $product->loadMissing('options.values', 'variants.optionValues', 'store');

            $optionValueGroups = $product->options
                ->sortBy('position')
                ->map(fn ($option) => $option->values->sortBy('position')->values())
                ->values();

            if ($optionValueGroups->isEmpty()) {
                $this->ensureDefaultVariant($product);

                return;
            }

            if ($optionValueGroups->contains(fn (Collection $values): bool => $values->isEmpty())) {
                return;
            }

            $desiredCombinations = $this->cartesian($optionValueGroups);
            $desiredKeys = collect($desiredCombinations)
                ->map(fn (array $combination): string => $this->keyFor($combination))
                ->all();

            $existingVariants = $product->variants()
                ->with('optionValues')
                ->orderBy('position')
                ->get();

            $existingByKey = $existingVariants->keyBy(
                fn (ProductVariant $variant): string => $this->keyFor($variant->optionValues->pluck('id')->all())
            );

            foreach ($desiredCombinations as $index => $combination) {
                $key = $this->keyFor($combination);

                if ($existingByKey->has($key)) {
                    continue;
                }

                $variant = $this->createVariantForCombination($product, $existingVariants->first(), $index);
                $variant->optionValues()->attach($combination);
            }

            foreach ($existingVariants as $variant) {
                $key = $this->keyFor($variant->optionValues->pluck('id')->all());

                if (in_array($key, $desiredKeys, true)) {
                    continue;
                }

                if ($this->hasOrderReferences($variant)) {
                    $variant->forceFill(['status' => VariantStatus::Archived])->save();

                    continue;
                }

                $variant->delete();
            }
        });
    }

    private function ensureDefaultVariant(Product $product): void
    {
        if ($product->variants()->exists()) {
            return;
        }

        $product->variants()->create([
            'price_amount' => 0,
            'currency' => $product->store->default_currency,
            'is_default' => true,
        ]);
    }

    /**
     * @param  Collection<int, Collection<int, mixed>>  $groups
     * @return array<int, array<int, int>>
     */
    private function cartesian(Collection $groups): array
    {
        $result = [[]];

        foreach ($groups as $group) {
            $append = [];

            foreach ($result as $product) {
                foreach ($group as $item) {
                    $append[] = [...$product, $item->id];
                }
            }

            $result = $append;
        }

        return $result;
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function keyFor(array $ids): string
    {
        sort($ids);

        return implode('-', $ids);
    }

    private function createVariantForCombination(Product $product, ?ProductVariant $template, int $position): ProductVariant
    {
        return $product->variants()->create([
            'sku' => null,
            'barcode' => null,
            'price_amount' => $template?->price_amount ?? 0,
            'compare_at_amount' => $template?->compare_at_amount,
            'currency' => $template?->currency ?? $product->store->default_currency,
            'weight_g' => $template?->weight_g,
            'requires_shipping' => $template?->requires_shipping ?? true,
            'is_default' => false,
            'position' => $position,
            'status' => VariantStatus::Active,
        ]);
    }

    private function hasOrderReferences(ProductVariant $variant): bool
    {
        if (! Schema::hasTable('order_lines')) {
            return false;
        }

        return DB::table('order_lines')->where('variant_id', $variant->id)->exists();
    }
}
