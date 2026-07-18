<?php

namespace App\Services;

use App\Enums\VariantStatus;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VariantMatrixService
{
    public function rebuildMatrix(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $product->load(['options.values', 'variants.optionValues']);

            $valueGroups = $product->options
                ->sortBy('position')
                ->map(fn ($option) => $option->values->sortBy('position')->values())
                ->values();

            if ($valueGroups->isEmpty()) {
                return;
            }

            $combinations = $this->cartesian($valueGroups);
            $existingKeys = [];

            foreach ($combinations as $index => $valueIds) {
                $key = collect($valueIds)->sort()->implode('-');
                $existingKeys[] = $key;

                $variant = $product->variants->first(function (ProductVariant $variant) use ($valueIds): bool {
                    $current = $variant->optionValues->pluck('id')->sort()->values()->all();
                    $expected = collect($valueIds)->sort()->values()->all();

                    return $current === $expected;
                });

                if ($variant === null) {
                    $variant = ProductVariant::query()->create([
                        'product_id' => $product->id,
                        'price_amount' => 0,
                        'currency' => $product->store->default_currency,
                        'is_default' => $index === 0,
                        'position' => $index,
                        'status' => VariantStatus::Active,
                    ]);

                    $variant->optionValues()->sync($valueIds);

                    InventoryItem::query()->create([
                        'store_id' => $product->store_id,
                        'variant_id' => $variant->id,
                        'quantity_on_hand' => 0,
                        'quantity_reserved' => 0,
                    ]);
                } else {
                    $variant->update([
                        'position' => $index,
                        'status' => VariantStatus::Active,
                        'is_default' => $index === 0,
                    ]);
                }
            }

            foreach ($product->variants as $variant) {
                $key = $variant->optionValues->pluck('id')->sort()->implode('-');

                if ($key !== '' && ! in_array($key, $existingKeys, true)) {
                    $variant->update(['status' => VariantStatus::Archived]);
                }
            }
        });
    }

    /**
     * @param  Collection<int, Collection<int, mixed>>  $groups
     * @return list<list<int>>
     */
    private function cartesian(Collection $groups): array
    {
        $result = [[]];

        foreach ($groups as $group) {
            $append = [];

            foreach ($result as $product) {
                foreach ($group as $item) {
                    $append[] = array_merge($product, [$item->id]);
                }
            }

            $result = $append;
        }

        return $result;
    }
}
