<?php

namespace App\Services;

use App\Enums\VariantStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class VariantMatrixService
{
    /**
     * Rebuild the variant matrix from the product's current options.
     *
     * Computes the cartesian product of all option values, creates variants
     * for missing combinations (copying default pricing from the first
     * existing variant), and removes orphaned variants: archived when they
     * are referenced by order lines, hard-deleted otherwise.
     */
    public function rebuildMatrix(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $options = $product->options()->with('values')->get();

            if ($options->isEmpty()) {
                return;
            }

            $valueSets = $options
                ->map(fn ($option) => $option->values->pluck('id')->all())
                ->all();

            $desiredCombos = $this->cartesianProduct($valueSets);
            $desiredKeys = array_map(fn (array $combo) => $this->comboKey($combo), $desiredCombos);

            $variants = $product->variants()->with('optionValues')->get();
            $activeVariants = $variants->where('status', VariantStatus::Active)->values();

            $defaults = $this->defaultAttributes($variants->first());

            $existingKeys = $activeVariants
                ->map(fn (ProductVariant $variant) => $this->comboKey($variant->optionValues->pluck('id')->all()))
                ->all();

            // Create variants for combinations that do not exist yet.
            $position = $activeVariants->count();
            $hasDefault = $variants->contains('is_default', true);

            foreach ($desiredCombos as $index => $combo) {
                if (in_array($desiredKeys[$index], $existingKeys, true)) {
                    continue;
                }

                $variant = $product->variants()->create(array_merge($defaults, [
                    'position' => $position++,
                    'is_default' => ! $hasDefault,
                ]));
                $hasDefault = true;

                $variant->optionValues()->sync($combo);
                $variant->inventoryItem()->create([
                    'store_id' => $product->store_id,
                    'quantity_on_hand' => 0,
                ]);
            }

            // Remove variants that no longer match any desired combination.
            foreach ($activeVariants as $variant) {
                $key = $this->comboKey($variant->optionValues->pluck('id')->all());

                if (in_array($key, $desiredKeys, true)) {
                    continue;
                }

                if ($this->hasOrderLineReferences($variant)) {
                    $variant->update(['status' => VariantStatus::Archived]);
                } else {
                    $variant->delete();
                }
            }
        });
    }

    /**
     * Compute the cartesian product of the given sets of option value IDs.
     *
     * @param  list<list<int>>  $sets
     * @return list<list<int>>
     */
    private function cartesianProduct(array $sets): array
    {
        $result = [[]];

        foreach ($sets as $set) {
            $next = [];

            foreach ($result as $combination) {
                foreach ($set as $valueId) {
                    $next[] = array_merge($combination, [$valueId]);
                }
            }

            $result = $next;
        }

        return $result;
    }

    /**
     * Build a normalized comparison key for a combination of value IDs.
     *
     * @param  list<int>  $valueIds
     */
    private function comboKey(array $valueIds): string
    {
        sort($valueIds);

        return implode('-', $valueIds);
    }

    /**
     * Pricing/shipping defaults copied from the first existing variant.
     *
     * @return array<string, mixed>
     */
    private function defaultAttributes(?ProductVariant $reference): array
    {
        return [
            'price_amount' => $reference?->price_amount ?? 0,
            'compare_at_amount' => $reference?->compare_at_amount,
            'currency' => $reference?->currency ?? 'USD',
            'weight_g' => $reference?->weight_g,
            'requires_shipping' => $reference?->requires_shipping ?? true,
        ];
    }

    /**
     * Whether any order line references the variant.
     */
    private function hasOrderLineReferences(ProductVariant $variant): bool
    {
        return DB::table('order_lines')->where('variant_id', $variant->id)->exists();
    }
}
