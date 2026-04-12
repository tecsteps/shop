<?php

namespace App\Services;

use App\Enums\VariantStatus;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class VariantMatrixService
{
    public function rebuildMatrix(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $product->load('options.values', 'variants.optionValues');

            $options = $product->options;

            if ($options->isEmpty()) {
                return;
            }

            $combinations = $this->cartesian(
                $options->map(fn ($option) => $option->values->all())->all()
            );

            $currency = optional($product->store)->default_currency ?? 'USD';
            $existingByKey = [];

            foreach ($product->variants as $variant) {
                $key = $this->keyFor($variant->optionValues->pluck('id')->all());
                $existingByKey[$key] = $variant;
            }

            $seenKeys = [];

            foreach ($combinations as $index => $combination) {
                $valueIds = array_map(fn ($value) => $value->id, $combination);
                $key = $this->keyFor($valueIds);
                $seenKeys[] = $key;

                if (isset($existingByKey[$key])) {
                    $variant = $existingByKey[$key];

                    if ($variant->status !== VariantStatus::Active) {
                        $variant->status = VariantStatus::Active;
                        $variant->save();
                    }

                    continue;
                }

                $variant = $product->variants()->create([
                    'price_amount' => 0,
                    'currency' => $currency,
                    'is_default' => $index === 0 && ! $product->variants()->where('is_default', true)->exists(),
                    'position' => $index,
                    'status' => VariantStatus::Active->value,
                ]);

                $variant->optionValues()->sync($valueIds);
            }

            $seenKeysMap = array_flip($seenKeys);

            foreach ($existingByKey as $key => $variant) {
                if (! array_key_exists((string) $key, $seenKeysMap)) {
                    $variant->status = VariantStatus::Archived;
                    $variant->save();
                }
            }
        });
    }

    /**
     * @param  array<int, array<int, mixed>>  $groups
     * @return array<int, array<int, mixed>>
     */
    private function cartesian(array $groups): array
    {
        $result = [[]];

        foreach ($groups as $group) {
            $next = [];

            foreach ($result as $acc) {
                foreach ($group as $item) {
                    $next[] = array_merge($acc, [$item]);
                }
            }

            $result = $next;
        }

        return $result;
    }

    /**
     * @param  array<int, int>  $valueIds
     */
    private function keyFor(array $valueIds): string
    {
        sort($valueIds);

        return implode('-', $valueIds);
    }
}
