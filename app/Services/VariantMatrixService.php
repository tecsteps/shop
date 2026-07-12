<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class VariantMatrixService
{
    public function rebuildMatrix(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $product->load(['options.values', 'variants.optionValues', 'variants.inventoryItem']);
            $groups = $product->options->sortBy('position')->map(fn ($option) => $option->values->sortBy('position')->pluck('id')->all())->all();
            $combinations = $groups === [] ? [[]] : $this->cartesian(array_values($groups));
            $existing = $product->variants->keyBy(fn (ProductVariant $variant): string => $this->key($variant->optionValues->modelKeys()));
            $template = $product->variants->first();
            $desiredKeys = [];

            foreach ($combinations as $position => $combination) {
                $key = $this->key($combination);
                $desiredKeys[] = $key;
                if ($existing->has($key)) {
                    $existing[$key]->update(['position' => $position, 'status' => 'active', 'is_default' => $groups === []]);
                    continue;
                }

                $variant = $product->variants()->create([
                    'sku' => null,
                    'barcode' => null,
                    'price_amount' => (int) ($template?->price_amount ?? 0),
                    'compare_at_amount' => $template?->compare_at_amount,
                    'currency' => $template?->currency ?? $product->store?->default_currency ?? 'USD',
                    'weight_g' => $template?->weight_g,
                    'requires_shipping' => $template?->requires_shipping ?? true,
                    'is_default' => $groups === [],
                    'position' => $position,
                    'status' => 'active',
                ]);
                if ($combination !== []) {
                    $variant->optionValues()->sync($combination);
                }
                InventoryItem::withoutGlobalScopes()->create([
                    'store_id' => $product->store_id,
                    'variant_id' => $variant->id,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'policy' => 'deny',
                ]);
            }

            foreach ($existing as $key => $variant) {
                if (in_array($key, $desiredKeys, true)) {
                    continue;
                }
                if (OrderLine::query()->where('variant_id', $variant->id)->exists()) {
                    $variant->update(['status' => 'archived']);
                } else {
                    $variant->delete();
                }
            }
        });
    }

    /** @param list<list<int>> $groups @return list<list<int>> */
    private function cartesian(array $groups): array
    {
        $result = [[]];
        foreach ($groups as $group) {
            $next = [];
            foreach ($result as $prefix) {
                foreach ($group as $value) {
                    $next[] = [...$prefix, $value];
                }
            }
            $result = $next;
        }

        return $result;
    }

    /** @param array<int, int|string> $ids */
    private function key(array $ids): string
    {
        sort($ids);

        return implode(':', $ids);
    }
}
