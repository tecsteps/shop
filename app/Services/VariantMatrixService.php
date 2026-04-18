<?php

namespace App\Services;

use App\Enums\VariantStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VariantMatrixService
{
    public function rebuildMatrix(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $product->loadMissing(['options.values', 'variants.optionValues']);

            $options = $product->options;

            if ($options->isEmpty()) {
                $this->ensureDefaultVariant($product);

                return;
            }

            $valueGroups = $options->map(fn ($option) => $option->values->all())->all();

            $combos = $this->cartesianProduct($valueGroups);

            $existingVariants = $product->variants;
            $existingCombos = [];

            foreach ($existingVariants as $variant) {
                $key = $this->keyForValues($variant->optionValues->pluck('id')->all());
                $existingCombos[$key] = $variant;
            }

            $desiredKeys = [];
            $defaultPrice = $existingVariants->first()?->price_amount ?? 0;
            $defaultCurrency = $existingVariants->first()?->currency ?? 'USD';

            $position = 0;
            foreach ($combos as $combo) {
                $valueIds = array_map(fn ($v) => $v->id, $combo);
                $key = $this->keyForValues($valueIds);
                $desiredKeys[$key] = true;

                if (isset($existingCombos[$key])) {
                    continue;
                }

                $variant = ProductVariant::query()->create([
                    'product_id' => $product->id,
                    'price_amount' => $defaultPrice,
                    'currency' => $defaultCurrency,
                    'is_default' => false,
                    'position' => $position++,
                    'status' => VariantStatus::Active,
                ]);

                $variant->optionValues()->sync($valueIds);

                \App\Models\InventoryItem::query()->create([
                    'store_id' => $product->store_id,
                    'variant_id' => $variant->id,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'policy' => \App\Enums\InventoryPolicy::Deny,
                ]);
            }

            foreach ($existingCombos as $key => $variant) {
                if (isset($desiredKeys[$key])) {
                    continue;
                }

                if ($this->variantHasOrderReferences($variant)) {
                    $variant->update(['status' => VariantStatus::Archived]);
                } else {
                    $variant->delete();
                }
            }
        });
    }

    protected function ensureDefaultVariant(Product $product): void
    {
        if ($product->variants->isNotEmpty()) {
            return;
        }

        ProductVariant::query()->create([
            'product_id' => $product->id,
            'is_default' => true,
            'position' => 0,
            'status' => VariantStatus::Active,
            'currency' => 'USD',
        ]);
    }

    /**
     * @param  array<int, array<int, mixed>>  $groups
     * @return array<int, array<int, mixed>>
     */
    protected function cartesianProduct(array $groups): array
    {
        $result = [[]];

        foreach ($groups as $group) {
            $next = [];
            foreach ($result as $current) {
                foreach ($group as $item) {
                    $next[] = array_merge($current, [$item]);
                }
            }
            $result = $next;
        }

        return $result;
    }

    /**
     * @param  array<int, int>  $valueIds
     */
    protected function keyForValues(array $valueIds): string
    {
        sort($valueIds);

        return implode('-', $valueIds);
    }

    protected function variantHasOrderReferences(ProductVariant $variant): bool
    {
        if (! Schema::hasTable('order_lines')) {
            return false;
        }

        return DB::table('order_lines')->where('variant_id', $variant->id)->exists();
    }
}
