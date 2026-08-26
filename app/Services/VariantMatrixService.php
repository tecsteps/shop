<?php

namespace App\Services;

use App\Models\OrderLine;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class VariantMatrixService
{
    public function rebuildMatrix(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $options = $product->options()->with('values')->get();

            if ($options->isEmpty()) {
                $this->ensureDefaultVariant($product);

                return;
            }

            $valueSets = $options->map(fn ($option) => $option->values->pluck('id')->all())->all();
            $combos = $this->cartesianProduct($valueSets);

            $existing = $product->variants()->with('optionValues')->get();
            $existingByKey = [];

            foreach ($existing as $variant) {
                $key = $this->signature($variant->optionValues->pluck('id')->sort()->values()->all());
                $existingByKey[$key] = $variant;
            }

            $firstVariant = $existing->first();
            $seen = [];

            foreach ($combos as $combo) {
                $key = $this->signature($combo);
                $seen[$key] = true;

                if (isset($existingByKey[$key])) {
                    continue;
                }

                $variant = $product->variants()->create([
                    'price_amount' => $firstVariant?->price_amount ?? 0,
                    'compare_at_amount' => $firstVariant?->compare_at_amount ?? null,
                    'currency' => $firstVariant?->currency ?? 'USD',
                    'weight_g' => $firstVariant?->weight_g ?? null,
                    'requires_shipping' => $firstVariant?->requires_shipping ?? true,
                    'is_default' => false,
                    'position' => $product->variants()->count(),
                    'status' => 'active',
                ]);

                $variant->optionValues()->sync($combo);

                $variant->inventoryItem()->create([
                    'store_id' => $product->store_id,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'policy' => 'deny',
                ]);
            }

            $this->ensureDefaultVariant($product);

            foreach ($existing as $variant) {
                $key = $this->signature($variant->optionValues->pluck('id')->sort()->values()->all());

                if (isset($seen[$key])) {
                    continue;
                }

                if (OrderLine::where('variant_id', $variant->id)->exists()) {
                    $variant->update(['status' => 'archived']);
                } else {
                    $variant->delete();
                }
            }
        });
    }

    private function ensureDefaultVariant(Product $product): void
    {
        if ($product->variants()->where('is_default', true)->exists()) {
            return;
        }

        $first = $product->variants()->first();

        if ($first) {
            $first->update(['is_default' => true]);

            return;
        }

        $variant = $product->variants()->create([
            'price_amount' => 0,
            'currency' => 'USD',
            'requires_shipping' => true,
            'is_default' => true,
            'position' => 0,
            'status' => 'active',
        ]);

        $variant->inventoryItem()->create([
            'store_id' => $product->store_id,
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
            'policy' => 'deny',
        ]);
    }

    /**
     * @param  list<list<int>>  $sets
     * @return list<list<int>>
     */
    private function cartesianProduct(array $sets): array
    {
        if ($sets === []) {
            return [];
        }

        $result = [[]];

        foreach ($sets as $set) {
            if ($set === []) {
                continue;
            }

            $append = [];

            foreach ($result as $product) {
                foreach ($set as $value) {
                    $append[] = [...$product, $value];
                }
            }

            $result = $append;
        }

        return $result;
    }

    /**
     * @param  list<int>  $ids
     */
    private function signature(array $ids): string
    {
        sort($ids);

        return implode(',', $ids);
    }
}
