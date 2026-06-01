<?php

namespace App\Services;

use App\Enums\VariantStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Concerns\ChecksOrderReferences;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Keeps a product's variant set in sync with its option matrix.
 *
 * Variants represent the cartesian product of the product's option values. When
 * options or their values change, {@see self::rebuildMatrix()} reconciles the
 * existing variants against the desired combinations:
 *
 *   - combinations that already have a matching variant are preserved intact
 *     (prices, SKUs, and inventory untouched);
 *   - missing combinations get a new variant, seeded with default pricing from
 *     the first existing variant;
 *   - orphaned variants are archived when order lines reference them (to
 *     preserve order history) and hard-deleted otherwise.
 *
 * A product with no options always resolves to a single default variant.
 */
class VariantMatrixService
{
    use ChecksOrderReferences;

    public function __construct(private readonly InventoryService $inventory) {}

    /**
     * Reconcile a product's variants with its current option matrix.
     */
    public function rebuildMatrix(Product $product): void
    {
        $product->load(['options.values', 'variants.optionValues']);

        $options = $product->options;

        // No options: collapse to exactly one default variant.
        if ($options->isEmpty() || $options->every(fn ($option) => $option->values->isEmpty())) {
            $this->ensureDefaultVariant($product);

            return;
        }

        DB::transaction(function () use ($product, $options): void {
            $desiredCombos = $this->cartesianProduct(
                $options->map(fn ($option) => $option->values->pluck('id')->all())->all(),
            );

            $existingVariants = $product->variants;
            $matchedVariantIds = [];

            $template = $existingVariants->first();

            foreach ($desiredCombos as $combo) {
                $match = $this->findVariantForCombo($existingVariants, $combo);

                if ($match !== null) {
                    $matchedVariantIds[] = $match->id;

                    continue;
                }

                $created = $this->createVariantForCombo($product, $combo, $template);
                $matchedVariantIds[] = $created->id;
            }

            // Reconcile orphans: archive if referenced by orders, else delete.
            foreach ($existingVariants as $variant) {
                if (in_array($variant->id, $matchedVariantIds, true)) {
                    continue;
                }

                $this->disposeOrphan($variant);
            }
        });
    }

    /**
     * Ensure a product without options has a single default variant.
     */
    private function ensureDefaultVariant(Product $product): void
    {
        if ($product->variants->isNotEmpty()) {
            return;
        }

        $this->createVariant($product, [
            'is_default' => true,
            'position' => 0,
        ]);
    }

    /**
     * Find an existing variant whose option-value set exactly equals the combo.
     *
     * @param  Collection<int, ProductVariant>  $variants
     * @param  list<int>  $combo  Sorted option-value ids for the combination.
     */
    private function findVariantForCombo(Collection $variants, array $combo): ?ProductVariant
    {
        $target = $combo;
        sort($target);

        foreach ($variants as $variant) {
            $variantValues = $variant->optionValues->pluck('id')->all();
            sort($variantValues);

            if ($variantValues === $target) {
                return $variant;
            }
        }

        return null;
    }

    /**
     * Create a new variant for a desired combination, seeding pricing from a
     * template variant when one exists.
     *
     * @param  list<int>  $combo  Option-value ids that define the combination.
     */
    private function createVariantForCombo(Product $product, array $combo, ?ProductVariant $template): ProductVariant
    {
        $variant = $this->createVariant($product, [
            'price_amount' => $template?->price_amount ?? 0,
            'currency' => $template?->currency ?? $product->store?->default_currency ?? 'USD',
            'position' => $product->variants()->max('position') + 1,
        ]);

        $variant->optionValues()->sync($combo);

        return $variant;
    }

    /**
     * Create a variant plus its auto-provisioned inventory item.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createVariant(Product $product, array $attributes): ProductVariant
    {
        return $product->variants()->create(array_merge([
            'price_amount' => 0,
            'currency' => $product->store?->default_currency ?? 'USD',
            'requires_shipping' => true,
            'is_default' => false,
            'position' => 0,
            'status' => VariantStatus::Active->value,
        ], $attributes));
    }

    /**
     * Archive an orphaned variant when orders reference it; delete it otherwise.
     */
    private function disposeOrphan(ProductVariant $variant): void
    {
        if ($this->variantHasOrderReferences($variant)) {
            $variant->update(['status' => VariantStatus::Archived->value]);

            return;
        }

        $variant->delete();
    }

    /**
     * Compute the cartesian product of option-value id sets.
     *
     * @param  list<list<int>>  $sets
     * @return list<list<int>>
     */
    private function cartesianProduct(array $sets): array
    {
        $result = [[]];

        foreach ($sets as $set) {
            $next = [];

            foreach ($result as $combo) {
                foreach ($set as $value) {
                    $next[] = array_merge($combo, [$value]);
                }
            }

            $result = $next;
        }

        return $result;
    }
}
