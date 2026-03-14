<?php

namespace App\Services;

use App\Enums\VariantStatus;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VariantMatrixService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    /**
     * Rebuild the variant matrix for a product based on its current options and values.
     */
    public function rebuildMatrix(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $options = $product->options()->with('values')->orderBy('position')->get();

            if ($options->isEmpty()) {
                $this->ensureDefaultVariant($product);

                return;
            }

            $optionValueSets = $options->map(fn ($option) => $option->values->pluck('id')->all())->all();

            $combinations = $this->cartesianProduct($optionValueSets);

            $matchedVariantIds = [];
            $position = 1;

            foreach ($combinations as $valueIds) {
                $existingVariant = $this->findVariantByOptionValues($product, $valueIds);

                if ($existingVariant) {
                    $existingVariant->update(['position' => $position]);
                    $matchedVariantIds[] = $existingVariant->id;
                } else {
                    $title = $this->buildVariantTitle($options, $valueIds);

                    $variant = $product->variants()->create([
                        'title' => $title,
                        'price_amount' => 0,
                        'position' => $position,
                        'is_default' => false,
                    ]);

                    $variant->optionValues()->sync($valueIds);

                    $variant->inventoryItem()->create([
                        'sku' => null,
                        'quantity_on_hand' => 0,
                        'quantity_reserved' => 0,
                    ]);

                    $matchedVariantIds[] = $variant->id;
                }

                $position++;
            }

            $this->removeOrphanedVariants($product, $matchedVariantIds);
        });
    }

    /**
     * Ensure a single default variant exists when no options are defined.
     */
    private function ensureDefaultVariant(Product $product): void
    {
        if ($product->variants()->where('is_default', true)->exists()) {
            return;
        }

        if ($product->variants()->count() === 0) {
            $variant = $product->variants()->create([
                'title' => 'Default',
                'price_amount' => 0,
                'position' => 1,
                'is_default' => true,
            ]);

            $variant->inventoryItem()->create([
                'sku' => null,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
            ]);
        }
    }

    /**
     * Find an existing variant that has exactly the given set of option value IDs.
     */
    private function findVariantByOptionValues(Product $product, array $valueIds): mixed
    {
        return $product->variants()
            ->whereHas('optionValues', function ($query) use ($valueIds) {
                $query->whereIn('product_option_values.id', $valueIds);
            }, '=', count($valueIds))
            ->get()
            ->first(function ($variant) use ($valueIds) {
                $existingIds = $variant->optionValues()->pluck('product_option_values.id')->sort()->values()->all();

                return $existingIds === collect($valueIds)->sort()->values()->all();
            });
    }

    /**
     * Build a variant title from option values (e.g., "S / Red").
     */
    private function buildVariantTitle($options, array $valueIds): string
    {
        $labels = [];

        foreach ($options as $option) {
            foreach ($option->values as $value) {
                if (in_array($value->id, $valueIds)) {
                    $labels[] = $value->label;
                    break;
                }
            }
        }

        return implode(' / ', $labels);
    }

    /**
     * Remove or archive variants not present in the new matrix.
     */
    private function removeOrphanedVariants(Product $product, array $keepVariantIds): void
    {
        $orphans = $product->variants()
            ->whereNotIn('id', $keepVariantIds)
            ->where('is_default', false)
            ->get();

        foreach ($orphans as $variant) {
            if ($this->hasOrderReferences($variant->id)) {
                $variant->update(['status' => VariantStatus::Archived]);
            } else {
                $variant->optionValues()->detach();
                $variant->inventoryItem()?->delete();
                $variant->delete();
            }
        }
    }

    /**
     * Check if a variant has order line references.
     */
    private function hasOrderReferences(int $variantId): bool
    {
        if (! Schema::hasTable('order_lines')) {
            return false;
        }

        return DB::table('order_lines')
            ->where('variant_id', $variantId)
            ->exists();
    }

    /**
     * Compute the cartesian product of multiple arrays.
     *
     * @param  array<int, array<int, mixed>>  $arrays
     * @return array<int, array<int, mixed>>
     */
    private function cartesianProduct(array $arrays): array
    {
        $result = [[]];

        foreach ($arrays as $array) {
            $tmp = [];

            foreach ($result as $existing) {
                foreach ($array as $item) {
                    $tmp[] = array_merge($existing, [$item]);
                }
            }

            $result = $tmp;
        }

        return $result;
    }
}
