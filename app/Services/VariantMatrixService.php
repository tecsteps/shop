<?php

namespace App\Services;

use App\Enums\VariantStatus;
use App\Exceptions\InvalidVariantMatrixException;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VariantMatrixService
{
    public function rebuildMatrix(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $options = $product->options()->with('values')->get();

            if ($options->count() > 3) {
                throw new InvalidVariantMatrixException($product->getKey(), 'Products may have at most three options.');
            }

            if ($options->contains(fn ($option): bool => $option->values->isEmpty())) {
                throw new InvalidVariantMatrixException($product->getKey(), 'Every option must contain at least one value.');
            }

            $variants = $product->variants()->with('optionValues')->get();

            if ($options->isEmpty()) {
                $this->ensureDefaultVariant($product, $variants);

                return;
            }

            $template = $variants->first();
            $existingByCombination = $variants->keyBy(
                fn (ProductVariant $variant): string => $this->combinationKey($variant->optionValues->modelKeys()),
            );
            $desiredKeys = [];

            foreach ($this->cartesianProduct($options->map->values->all()) as $position => $combination) {
                $valueIds = collect($combination)->map(fn ($value): int => $value->getKey())->all();
                $key = $this->combinationKey($valueIds);
                $desiredKeys[] = $key;
                $variant = $existingByCombination->get($key);

                if ($variant === null) {
                    $variant = $product->variants()->create([
                        'price_amount' => $template?->price_amount ?? 0,
                        'compare_at_amount' => $template?->compare_at_amount,
                        'currency' => $template?->currency ?? $product->store()->value('default_currency'),
                        'weight_g' => $template?->weight_g,
                        'requires_shipping' => $template?->requires_shipping ?? true,
                        'is_default' => $position === 0,
                        'position' => $position,
                        'status' => VariantStatus::Active,
                    ]);
                    $variant->optionValues()->attach($valueIds);
                } else {
                    $variant->update([
                        'position' => $position,
                        'is_default' => $position === 0,
                    ]);
                }
            }

            foreach ($variants as $variant) {
                if (in_array($this->combinationKey($variant->optionValues->modelKeys()), $desiredKeys, true)) {
                    continue;
                }

                $this->removeOrArchive($variant);
            }
        });
    }

    /** @param Collection<int, ProductVariant> $variants */
    private function ensureDefaultVariant(Product $product, Collection $variants): void
    {
        $default = $variants->firstWhere('is_default', true) ?? $variants->first();

        if ($default === null) {
            $product->variants()->create([
                'price_amount' => 0,
                'currency' => $product->store()->value('default_currency'),
                'requires_shipping' => true,
                'is_default' => true,
                'position' => 0,
                'status' => VariantStatus::Active,
            ]);

            return;
        }

        $default->update(['is_default' => true, 'position' => 0]);

        foreach ($variants->where('id', '!=', $default->getKey()) as $variant) {
            $this->removeOrArchive($variant);
        }
    }

    private function removeOrArchive(ProductVariant $variant): void
    {
        if ($this->hasOrderReferences($variant)) {
            $variant->update(['status' => VariantStatus::Archived, 'is_default' => false]);

            return;
        }

        $variant->delete();
    }

    private function hasOrderReferences(ProductVariant $variant): bool
    {
        return Schema::hasTable('order_lines')
            && OrderLine::withoutGlobalScopes()->where('variant_id', $variant->getKey())->exists();
    }

    /**
     * @param  array<int, Collection<int, \App\Models\ProductOptionValue>>  $valueGroups
     * @return list<list<\App\Models\ProductOptionValue>>
     */
    private function cartesianProduct(array $valueGroups): array
    {
        $combinations = [[]];

        foreach ($valueGroups as $values) {
            $next = [];

            foreach ($combinations as $combination) {
                foreach ($values as $value) {
                    $next[] = [...$combination, $value];
                }
            }

            $combinations = $next;
        }

        return $combinations;
    }

    /** @param array<int, int|string> $valueIds */
    private function combinationKey(array $valueIds): string
    {
        sort($valueIds, SORT_NUMERIC);

        return implode(':', $valueIds);
    }
}
