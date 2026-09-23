<?php

namespace App\Services;

use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VariantMatrixService
{
    /**
     * Rebuild a product's option values and variants from the requested option matrix.
     *
     * @param  array<int, array{name: string, values: array<int, string>}>  $options
     * @return Collection<int, ProductVariant>
     */
    public function rebuild(Product $product, array $options): Collection
    {
        $options = $this->normalizeOptions($options);

        return DB::transaction(function () use ($product, $options): Collection {
            $product = Product::query()
                ->whereKey($product->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $product->load([
                'options.values',
                'variants.optionValues.option',
                'variants.inventoryItem',
            ]);

            $existingOptions = $product->options;
            $existingVariants = $product->variants;
            $variantCombinationKeys = [];
            $existingOptionPositions = $existingOptions->mapWithKeys(
                static fn (ProductOption $option): array => [$option->id => $option->position],
            );

            foreach ($existingVariants as $variant) {
                if ($variant->status === 'active') {
                    $variantCombinationKeys[$variant->id] = $this->combinationKey(
                        $variant->optionValues->map(static fn (ProductOptionValue $value): array => [
                            'option_id' => (int) $value->product_option_id,
                            'value' => $value->value,
                        ])->all(),
                    );
                }
            }

            $defaultVariant = $existingVariants->firstWhere('is_default', true) ?? $existingVariants->first();
            $defaultPricing = $this->defaultPricing($defaultVariant, (int) $product->store_id);
            $oldOptionsByName = $existingOptions->keyBy(fn (ProductOption $option): string => $this->normalizeKey($option->name));
            $desiredOptionIds = [];
            $desiredValueIdsByOption = [];

            $positionOffset = $this->positionOffset($existingOptions);

            foreach ($existingOptions as $option) {
                $option->position += $positionOffset;
                $option->save();

                foreach ($option->values as $value) {
                    $value->position += $positionOffset;
                    $value->save();
                }
            }

            $retainedOptionIds = [];

            foreach ($options as $optionPosition => $optionData) {
                $optionKey = $this->normalizeKey($optionData['name']);
                $option = $oldOptionsByName->get($optionKey);

                if ($option && in_array($option->id, $retainedOptionIds, true)) {
                    $option = null;
                }

                if (! $option) {
                    $option = $existingOptions->first(function (ProductOption $candidate) use ($optionPosition, $existingOptionPositions, $retainedOptionIds): bool {
                        return ! in_array($candidate->id, $retainedOptionIds, true)
                            && $existingOptionPositions->get($candidate->id) === $optionPosition;
                    }) ?? $existingOptions->first(
                        fn (ProductOption $candidate): bool => ! in_array($candidate->id, $retainedOptionIds, true),
                    );
                }

                if (! $option) {
                    $option = $product->options()->create([
                        'name' => $optionData['name'],
                        'position' => $optionPosition,
                    ]);
                } else {
                    $option->name = $optionData['name'];
                    $option->position = $optionPosition;
                    $option->save();
                    $retainedOptionIds[] = $option->id;
                }

                $desiredOptionIds[] = $option->id;
                $existingValuesByName = $option->values
                    ->keyBy(fn (ProductOptionValue $value): string => $this->normalizeKey($value->value));
                $desiredValueIdsByOption[$option->id] = [];

                foreach ($optionData['values'] as $valuePosition => $valueLabel) {
                    $value = $existingValuesByName->get($this->normalizeKey($valueLabel));

                    if (! $value) {
                        $value = $option->values()->create([
                            'value' => $valueLabel,
                            'position' => $valuePosition,
                        ]);
                    } else {
                        $value->value = $valueLabel;
                        $value->position = $valuePosition;
                        $value->save();
                    }

                    $desiredValueIdsByOption[$option->id][] = $value->id;
                }

                $obsoleteValueIds = $option->values()
                    ->whereNotIn('id', $desiredValueIdsByOption[$option->id])
                    ->pluck('id');

                if ($obsoleteValueIds->isNotEmpty()) {
                    ProductOptionValue::query()->whereIn('id', $obsoleteValueIds)->delete();
                }
            }

            $obsoleteOptionIds = $existingOptions
                ->whereNotIn('id', $retainedOptionIds)
                ->pluck('id');

            if ($obsoleteOptionIds->isNotEmpty()) {
                ProductOption::query()->whereIn('id', $obsoleteOptionIds)->delete();
            }

            $desiredCombinations = $this->cartesianProduct($desiredOptionIds, $desiredValueIdsByOption);
            $existingVariantsByCombination = [];

            foreach ($existingVariants as $variant) {
                if (isset($variantCombinationKeys[$variant->id])) {
                    $existingVariantsByCombination[$variantCombinationKeys[$variant->id]] = $variant;
                }
            }

            $retainedVariantIds = [];
            $rebuiltVariants = collect();

            foreach ($desiredCombinations as $position => $combination) {
                $combinationKey = $this->combinationKey($combination['labels']);
                $variant = $existingVariantsByCombination[$combinationKey] ?? null;

                if ($variant) {
                    $variant->position = $position;
                    $variant->optionValues()->sync($combination['value_ids']);
                    $variant->save();
                } else {
                    $variant = $product->variants()->create([
                        ...array_diff_key($defaultPricing, ['inventory_policy' => true]),
                        'is_default' => false,
                        'position' => $position,
                        'status' => 'active',
                    ]);

                    $variant->optionValues()->sync($combination['value_ids']);
                    $variant->inventoryItem()->create([
                        'store_id' => $product->store_id,
                        'quantity_on_hand' => 0,
                        'quantity_reserved' => 0,
                        'policy' => $defaultPricing['inventory_policy'],
                    ]);
                }

                $retainedVariantIds[] = $variant->id;
                $rebuiltVariants->push($variant);
            }

            $removedVariants = $existingVariants->reject(
                fn (ProductVariant $variant): bool => in_array($variant->id, $retainedVariantIds, true),
            );

            if ($removedVariants->isNotEmpty()) {
                $removedIds = $removedVariants->modelKeys();
                $variantsWithOrderHistory = OrderLine::query()
                    ->whereIn('variant_id', $removedIds)
                    ->pluck('variant_id')
                    ->unique()
                    ->all();
                $variantsToArchive = $removedVariants->whereIn('id', $variantsWithOrderHistory);
                $variantsToDelete = $removedVariants->whereNotIn('id', $variantsWithOrderHistory);

                foreach ($variantsToArchive as $variant) {
                    $variant->status = 'archived';
                    $variant->is_default = false;
                    $variant->save();
                }

                foreach ($variantsToDelete as $variant) {
                    $variant->delete();
                }
            }

            $defaultVariantId = null;

            if ($options === []) {
                $defaultVariantId = $rebuiltVariants->first()?->id;
            } else {
                $defaultVariantId = $rebuiltVariants
                    ->first(fn (ProductVariant $variant): bool => $variant->is_default && $variant->status === 'active')
                    ?->id ?? $rebuiltVariants->first()?->id;
            }

            ProductVariant::query()
                ->where('product_id', $product->id)
                ->update(['is_default' => false]);

            if ($defaultVariantId !== null) {
                ProductVariant::query()->whereKey($defaultVariantId)->update(['is_default' => true]);
            }

            return $product->variants()
                ->where('status', 'active')
                ->with(['inventoryItem', 'optionValues.option'])
                ->orderBy('position')
                ->get();
        });
    }

    /**
     * @param  array<int, mixed>  $options
     * @return array<int, array{name: string, values: array<int, string>}>
     */
    private function normalizeOptions(array $options): array
    {
        if (! array_is_list($options)) {
            throw ValidationException::withMessages(['options' => 'Options must be provided as a list.']);
        }

        if (count($options) > 3) {
            throw ValidationException::withMessages(['options' => 'A product can have at most three options.']);
        }

        $normalized = [];
        $seenOptionNames = [];

        foreach ($options as $optionPosition => $option) {
            if (! is_array($option) || ! is_string($option['name'] ?? null) || ! is_array($option['values'] ?? null)) {
                throw ValidationException::withMessages(["options.{$optionPosition}" => 'Each option needs a name and a list of values.']);
            }

            $name = trim($option['name']);

            if ($name === '' || mb_strlen($name) > 255) {
                throw ValidationException::withMessages(["options.{$optionPosition}.name" => 'Option names must contain between 1 and 255 characters.']);
            }

            $nameKey = $this->normalizeKey($name);

            if (isset($seenOptionNames[$nameKey])) {
                throw ValidationException::withMessages(["options.{$optionPosition}.name" => 'Option names must be unique.']);
            }

            $seenOptionNames[$nameKey] = true;

            if (! array_is_list($option['values']) || $option['values'] === []) {
                throw ValidationException::withMessages(["options.{$optionPosition}.values" => 'Each option needs at least one value.']);
            }

            $values = [];
            $seenValues = [];

            foreach ($option['values'] as $valuePosition => $value) {
                if (! is_string($value)) {
                    throw ValidationException::withMessages(["options.{$optionPosition}.values.{$valuePosition}" => 'Option values must be text.']);
                }

                $value = trim($value);

                if ($value === '' || mb_strlen($value) > 255) {
                    throw ValidationException::withMessages(["options.{$optionPosition}.values.{$valuePosition}" => 'Option values must contain between 1 and 255 characters.']);
                }

                $valueKey = $this->normalizeKey($value);

                if (isset($seenValues[$valueKey])) {
                    throw ValidationException::withMessages(["options.{$optionPosition}.values.{$valuePosition}" => 'Values for the same option must be unique.']);
                }

                $seenValues[$valueKey] = true;
                $values[] = $value;
            }

            $normalized[] = ['name' => $name, 'values' => $values];
        }

        return $normalized;
    }

    /**
     * @param  Collection<int, ProductOption>  $options
     */
    private function positionOffset(Collection $options): int
    {
        $positions = $options->flatMap(fn (ProductOption $option): array => [
            $option->position,
            ...$option->values->pluck('position')->all(),
        ]);

        return ((int) $positions->max()) + $positions->count() + 100;
    }

    /**
     * @return array{sku: ?string, barcode: ?string, price_amount: int, compare_at_amount: ?int, currency: string, weight_g: ?int, requires_shipping: bool, inventory_policy: string}
     */
    private function defaultPricing(?ProductVariant $variant, int $storeId): array
    {
        $currency = $variant?->currency ?? Store::query()->find($storeId)?->default_currency ?? 'USD';

        return [
            'sku' => null,
            'barcode' => null,
            'price_amount' => (int) ($variant?->price_amount ?? 0),
            'compare_at_amount' => $variant?->compare_at_amount,
            'currency' => $currency,
            'weight_g' => $variant?->weight_g,
            'requires_shipping' => $variant?->requires_shipping ?? true,
            'inventory_policy' => $variant?->inventoryItem?->policy ?? 'deny',
        ];
    }

    /**
     * @param  array<int, int>  $optionIds
     * @param  array<int, array<int, int>>  $valueIdsByOption
     * @return array<int, array{value_ids: array<int, int>, labels: array<int, array{option_id: int, value: string}>}>
     */
    private function cartesianProduct(array $optionIds, array $valueIdsByOption): array
    {
        $combinations = [['value_ids' => [], 'labels' => []]];

        foreach ($optionIds as $optionId) {
            $option = ProductOption::query()->with('values')->findOrFail($optionId);
            $next = [];

            foreach ($combinations as $combination) {
                foreach ($valueIdsByOption[$optionId] as $valueId) {
                    $value = $option->values->firstWhere('id', $valueId);

                    $next[] = [
                        'value_ids' => [...$combination['value_ids'], $valueId],
                        'labels' => [...$combination['labels'], ['option_id' => $option->id, 'value' => (string) $value?->value]],
                    ];
                }
            }

            $combinations = $next;
        }

        return $combinations;
    }

    /**
     * @param  array<int, array{option_id: int, value: string}>  $labels
     */
    private function combinationKey(array $labels): string
    {
        $normalizedLabels = collect($labels)
            ->map(fn (array $label): array => [(int) $label['option_id'], $this->normalizeKey($label['value'])])
            ->sortBy(static fn (array $label): string => str_pad((string) $label[0], 12, '0', STR_PAD_LEFT)."\0".$label[1])
            ->values()
            ->all();

        return json_encode($normalizedLabels, JSON_THROW_ON_ERROR);
    }

    private function normalizeKey(string $value): string
    {
        return Str::lower(trim($value));
    }
}
