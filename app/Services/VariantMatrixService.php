<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Enums\VariantStatus;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VariantMatrixService
{
    public function rebuildMatrix(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $product->load(['options.values', 'variants.optionValues']);

            $valueGroups = $product->options
                ->sortBy('position')
                ->map(fn ($option) => $option->values->sortBy('position')->values())
                ->filter(fn (Collection $values): bool => $values->isNotEmpty())
                ->values();

            if ($valueGroups->isEmpty()) {
                $this->ensureDefaultVariant($product);

                return;
            }

            $desiredCombinations = $this->cartesianProduct($valueGroups);
            $variantsByCombination = $product->variants->keyBy(function (ProductVariant $variant): string {
                return $this->combinationKey($variant->optionValues->pluck('id')->all());
            });

            $template = $product->variants->sortBy('position')->first();

            foreach ($desiredCombinations as $position => $combination) {
                $key = $this->combinationKey($combination);

                if ($variantsByCombination->has($key)) {
                    continue;
                }

                $variant = $this->createVariantFromTemplate($product, $template, $position);
                $variant->optionValues()->sync($combination);
            }

            $desiredKeys = collect($desiredCombinations)
                ->map(fn (array $combination): string => $this->combinationKey($combination))
                ->all();

            foreach ($product->variants as $variant) {
                $key = $this->combinationKey($variant->optionValues->pluck('id')->all());

                if (in_array($key, $desiredKeys, true)) {
                    continue;
                }

                if ($this->variantHasOrderLines($variant)) {
                    $variant->forceFill(['status' => VariantStatus::Archived])->save();
                } else {
                    $variant->delete();
                }
            }
        });
    }

    private function ensureDefaultVariant(Product $product): void
    {
        $variants = $product->variants()->with('optionValues')->get();

        if ($variants->isEmpty()) {
            $variant = $product->variants()->create([
                'price_amount' => 0,
                'currency' => $this->store($product)->default_currency,
                'requires_shipping' => true,
                'is_default' => true,
                'position' => 0,
                'status' => VariantStatus::Active,
            ]);

            InventoryItem::withoutGlobalScopes()->firstOrCreate(
                ['variant_id' => $variant->getKey()],
                [
                    'store_id' => $product->store_id,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'policy' => InventoryPolicy::Deny,
                ],
            );

            return;
        }

        $defaultVariant = $variants->sortBy('position')->first();

        $defaultVariant->optionValues()->detach();
        $defaultVariant->forceFill([
            'is_default' => true,
            'position' => 0,
            'status' => VariantStatus::Active,
        ])->save();

        InventoryItem::withoutGlobalScopes()->firstOrCreate(
            ['variant_id' => $defaultVariant->getKey()],
            [
                'store_id' => $product->store_id,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
                'policy' => InventoryPolicy::Deny,
            ],
        );

        foreach ($variants as $variant) {
            if ($variant->is($defaultVariant)) {
                continue;
            }

            if ($this->variantHasOrderLines($variant)) {
                $variant->optionValues()->detach();
                $variant->forceFill([
                    'is_default' => false,
                    'status' => VariantStatus::Archived,
                ])->save();
            } else {
                $variant->delete();
            }
        }
    }

    /**
     * @param  Collection<int, Collection<int, object>>  $groups
     * @return array<int, array<int, int>>
     */
    private function cartesianProduct(Collection $groups): array
    {
        return $groups->reduce(
            function (array $carry, Collection $group): array {
                $result = [];

                foreach ($carry as $combination) {
                    foreach ($group as $value) {
                        $result[] = [...$combination, $value->getKey()];
                    }
                }

                return $result;
            },
            [[]],
        );
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    private function combinationKey(array $ids): string
    {
        sort($ids);

        return implode(':', $ids);
    }

    private function createVariantFromTemplate(Product $product, ?ProductVariant $template, int $position): ProductVariant
    {
        $variant = $product->variants()->create([
            'price_amount' => $template?->price_amount ?? 0,
            'compare_at_amount' => $template?->compare_at_amount,
            'currency' => $template?->currency ?? $this->store($product)->default_currency,
            'weight_g' => $template?->weight_g,
            'requires_shipping' => $template?->requires_shipping ?? true,
            'is_default' => false,
            'position' => $position,
            'status' => VariantStatus::Active,
        ]);

        InventoryItem::withoutGlobalScopes()->updateOrCreate(
            ['variant_id' => $variant->getKey()],
            [
                'store_id' => $product->store_id,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
                'policy' => InventoryPolicy::Deny,
            ],
        );

        return $variant;
    }

    private function variantHasOrderLines(ProductVariant $variant): bool
    {
        return Schema::hasTable('order_lines')
            && DB::table('order_lines')->where('variant_id', $variant->getKey())->exists();
    }

    private function store(Product $product): Store
    {
        return Store::query()->findOrFail($product->store_id);
    }
}
