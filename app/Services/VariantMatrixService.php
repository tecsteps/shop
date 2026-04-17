<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Enums\VariantStatus;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VariantMatrixService
{
    public function rebuildMatrix(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $options = $product->options()->with('values')->get();

            $valueGroups = $options->map(function ($option) {
                return $option->values->map(fn ($value): int => (int) $value->getKey())->all();
            })->all();

            $desiredCombos = $this->cartesianProduct($valueGroups);

            $existingVariants = $product->variants()->with('optionValues')->get();
            $existingSets = [];

            foreach ($existingVariants as $variant) {
                $ids = $variant->optionValues->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values()->all();
                $key = implode('|', $ids);
                $existingSets[$key] = $variant;
            }

            $defaultPrice = (int) ($existingVariants->first()->price_amount ?? 0);
            $defaultCurrency = $existingVariants->first()->currency ?? 'USD';

            $matchedKeys = [];
            $position = 0;

            foreach ($desiredCombos as $combo) {
                sort($combo);
                $key = implode('|', $combo);

                if (isset($existingSets[$key])) {
                    $matchedKeys[$key] = true;
                    $existingSets[$key]->update(['position' => $position]);
                    $position++;

                    continue;
                }

                $variant = new ProductVariant([
                    'price_amount' => $defaultPrice,
                    'currency' => $defaultCurrency,
                    'requires_shipping' => 1,
                    'is_default' => 0,
                    'position' => $position,
                    'status' => VariantStatus::Active->value,
                ]);
                $variant->product_id = $product->getKey();
                $variant->save();

                if (! empty($combo)) {
                    $variant->optionValues()->sync($combo);
                }

                $inventory = new InventoryItem([
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'policy' => InventoryPolicy::Deny->value,
                ]);
                $inventory->store_id = $product->store_id;
                $inventory->variant_id = $variant->getKey();
                $inventory->save();

                $position++;
            }

            foreach ($existingSets as $key => $variant) {
                if (isset($matchedKeys[$key])) {
                    continue;
                }

                if ($this->hasOrderReferences((int) $variant->getKey())) {
                    $variant->update(['status' => VariantStatus::Archived->value]);
                } else {
                    $variant->delete();
                }
            }
        });
    }

    /**
     * @param  array<int, array<int, int>>  $groups
     * @return array<int, array<int, int>>
     */
    protected function cartesianProduct(array $groups): array
    {
        if ($groups === []) {
            return [];
        }

        $groups = array_values(array_filter($groups, fn (array $g): bool => $g !== []));

        if ($groups === []) {
            return [];
        }

        $result = [[]];

        foreach ($groups as $group) {
            $next = [];

            foreach ($result as $partial) {
                foreach ($group as $value) {
                    $next[] = array_merge($partial, [$value]);
                }
            }

            $result = $next;
        }

        return $result;
    }

    protected function hasOrderReferences(int $variantId): bool
    {
        if (! Schema::hasTable('order_lines')) {
            return false;
        }

        return DB::table('order_lines')->where('variant_id', $variantId)->exists();
    }
}
