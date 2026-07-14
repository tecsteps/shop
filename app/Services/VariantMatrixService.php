<?php

namespace App\Services;

use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

final class VariantMatrixService
{
    public function rebuildMatrix(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $product->load(['options.values', 'variants.optionValues', 'variants.inventoryItem']);
            $groups = $product->options->sortBy('position')->map(fn ($option) => $option->values->sortBy('position')->pluck('id')->all())->all();
            $combinationCount = array_reduce($groups, fn (int $count, array $group): int => $count * count($group), 1);
            if ($combinationCount > 100) {
                throw new \InvalidArgumentException('Product options may generate at most 100 variants.');
            }
            $combinations = $groups === [] ? [[]] : $this->cartesian(array_values($groups));
            $existing = $product->variants;
            $template = $product->variants->first();
            $matchedVariantIds = [];

            foreach ($combinations as $position => $combination) {
                $key = $this->key($combination);
                $matchingVariant = $existing->first(fn (ProductVariant $variant): bool => ! in_array($variant->id, $matchedVariantIds, true)
                    && $this->key($variant->optionValues->modelKeys()) === $key);
                if ($matchingVariant !== null) {
                    $matchingVariant->update(['position' => $position, 'status' => 'active', 'is_default' => $position === 0]);
                    $matchedVariantIds[] = $matchingVariant->id;

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
                    'is_default' => $position === 0,
                    'position' => $position,
                    'status' => 'active',
                ]);
                if ($combination !== []) {
                    $variant->optionValues()->sync($combination);
                }
                $matchedVariantIds[] = $variant->id;
            }

            foreach ($existing as $variant) {
                if (in_array($variant->id, $matchedVariantIds, true)) {
                    continue;
                }
                if (OrderLine::query()->where('variant_id', $variant->id)->exists()) {
                    $variant->update(['status' => 'archived', 'is_default' => false]);
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
