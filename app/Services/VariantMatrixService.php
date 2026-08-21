<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;

class VariantMatrixService
{
    public function rebuildMatrix(Product $product): void
    {
        $product->load(['options.values', 'variants.optionValues']);
        $groups = $product->options->map(fn ($option): array => $option->values->all())->filter()->values()->all();

        if ($groups === []) {
            if ($product->variants->isEmpty()) {
                $product->variants()->create(['title' => 'Default', 'price_amount' => 0, 'is_default' => true, 'position' => 0]);
            }

            return;
        }

        $combinations = $this->cartesianProduct($groups);
        $existing = $product->variants->keyBy(fn (ProductVariant $variant): string => $variant->optionValues->modelKeys()->sort()->implode('-'));
        $position = 0;

        foreach ($combinations as $combination) {
            $key = collect($combination)->pluck('id')->sort()->implode('-');
            $variant = $existing->pull($key);

            if ($variant === null) {
                $variant = $product->variants()->create([
                    'title' => collect($combination)->pluck('value')->implode(' / '),
                    'price_amount' => (int) ($product->variants->first()?->price_amount ?? 0),
                    'is_default' => $position === 0,
                    'position' => $position,
                ]);
                InventoryItem::withoutGlobalScopes()->create(['store_id' => $product->store_id, 'variant_id' => $variant->getKey(), 'quantity_on_hand' => 0, 'policy' => 'deny']);
            }

            $variant->optionValues()->sync(collect($combination)->pluck('id')->all());
            $position++;
        }

        foreach ($existing as $orphan) {
            if ($orphan->orders()->exists()) {
                $orphan->update(['status' => 'archived', 'is_default' => false]);

                continue;
            }

            $orphan->delete();
        }
    }

    /** @param array<int, array<int, object>> $groups */
    private function cartesianProduct(array $groups): array
    {
        $result = [[]];

        foreach ($groups as $group) {
            $result = collect($result)->flatMap(fn (array $prefix): array => array_map(fn ($value): array => [...$prefix, $value], $group))->all();
        }

        return $result;
    }
}
