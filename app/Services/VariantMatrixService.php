<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Enums\VariantStatus;
use App\Models\InventoryItem;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

/**
 * Rebuilds the variant matrix from the product's options (spec 05 section 2.2).
 */
class VariantMatrixService
{
    public function rebuildMatrix(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $options = $product->productOptions()->with('productOptionValues')->orderBy('position')->get();

            foreach ($options as $option) {
                if ($option->productOptionValues->isEmpty()) {
                    return;
                }
            }

            $combos = $this->cartesian($options->map(
                fn ($option) => $option->productOptionValues->sortBy('position')->pluck('id')->all()
            )->all());

            $existing = $product->productVariants()->with('optionValues')->get();
            $matchedIds = [];

            foreach ($combos as $combo) {
                sort($combo);

                $match = $existing->first(function (ProductVariant $variant) use ($combo): bool {
                    $ids = $variant->optionValues->pluck('id')->all();
                    sort($ids);

                    return $ids === $combo;
                });

                if ($match !== null) {
                    $matchedIds[] = $match->getKey();

                    continue;
                }

                $created = $this->createVariantForCombo($product, $existing);
                $created->optionValues()->sync($combo);
                $matchedIds[] = $created->getKey();
                $existing->push($created);
            }

            foreach ($existing as $variant) {
                if (in_array($variant->getKey(), $matchedIds, true)) {
                    continue;
                }

                if ($this->hasOrderReferences($variant)) {
                    $variant->update(['status' => VariantStatus::Archived]);
                } else {
                    $variant->delete();
                }
            }

            $this->ensureSingleDefault($product);
        });
    }

    /**
     * @param  array<int, array<int, int>>  $valueSets
     * @return array<int, array<int, int>>
     */
    protected function cartesian(array $valueSets): array
    {
        if ($valueSets === []) {
            return [[]];
        }

        $combos = [[]];

        foreach ($valueSets as $set) {
            $next = [];

            foreach ($combos as $combo) {
                foreach ($set as $valueId) {
                    $next[] = [...$combo, $valueId];
                }
            }

            $combos = $next;
        }

        return $combos;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ProductVariant>  $existing
     */
    protected function createVariantForCombo(Product $product, object $existing): ProductVariant
    {
        /** @var ProductVariant|null $template */
        $template = $existing->first();

        $variant = $product->productVariants()->create([
            'sku' => null,
            'barcode' => null,
            'price_amount' => $template?->price_amount ?? 0,
            'compare_at_amount' => $template?->compare_at_amount,
            'currency' => $template?->currency ?? $product->store?->default_currency ?? 'USD',
            'weight_g' => $template?->weight_g,
            'requires_shipping' => $template?->requires_shipping ?? true,
            'is_default' => $existing->isEmpty(),
            'position' => ($existing->max('position') ?? -1) + 1,
            'status' => VariantStatus::Active,
        ]);

        InventoryItem::query()->create([
            'store_id' => $product->store_id,
            'variant_id' => $variant->getKey(),
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
            'policy' => InventoryPolicy::Deny,
        ]);

        return $variant;
    }

    protected function hasOrderReferences(ProductVariant $variant): bool
    {
        return OrderLine::query()->where('variant_id', $variant->getKey())->exists();
    }

    protected function ensureSingleDefault(Product $product): void
    {
        $variants = $product->productVariants()->orderBy('position')->orderBy('id')->get();

        if ($variants->isEmpty()) {
            return;
        }

        $default = $variants->firstWhere('is_default', true) ?? $variants->first();

        foreach ($variants as $variant) {
            $shouldBeDefault = $variant->getKey() === $default->getKey();

            if ((bool) $variant->is_default !== $shouldBeDefault) {
                $variant->update(['is_default' => $shouldBeDefault]);
            }
        }
    }
}
