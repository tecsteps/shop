<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Events\ProductStatusChanged;
use App\Exceptions\InvalidProductDeletionException;
use App\Exceptions\InvalidProductTransitionException;
use App\Exceptions\InvalidVariantMatrixException;
use App\Models\Collection;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductService
{
    public function __construct(
        private readonly HandleGenerator $handleGenerator,
        private readonly VariantMatrixService $variantMatrixService,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data): Product {
            $attributes = Arr::except($data, ['options', 'variants', 'collection_ids']);
            $attributes['store_id'] = $store->getKey();
            $attributes['handle'] = $this->handleGenerator->generate(
                (string) ($attributes['handle'] ?? $attributes['title']),
                'products',
                $store->getKey(),
            );

            $product = Product::withoutGlobalScopes()->create($attributes);

            if (array_key_exists('options', $data)) {
                $this->syncOptions($product, $data['options']);
            }

            if (filled($data['variants'] ?? null)) {
                $this->syncVariants($product, $data['variants']);
            } else {
                $this->variantMatrixService->rebuildMatrix($product);
            }

            if (array_key_exists('collection_ids', $data)) {
                $this->syncCollections($product, $data['collection_ids']);
            }

            return $product->load(['options.values', 'variants.inventoryItem', 'collections']);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $attributes = Arr::except($data, ['options', 'variants', 'collection_ids']);

            if (array_key_exists('handle', $attributes)) {
                $attributes['handle'] = $this->handleGenerator->generate(
                    (string) $attributes['handle'],
                    'products',
                    $product->store_id,
                    $product->getKey(),
                );
            }

            $product->update($attributes);

            if (array_key_exists('options', $data)) {
                $this->syncOptions($product, $data['options']);
                $this->variantMatrixService->rebuildMatrix($product);
            }

            if (array_key_exists('variants', $data)) {
                $this->syncVariants($product, $data['variants']);
            }

            if (array_key_exists('collection_ids', $data)) {
                $this->syncCollections($product, $data['collection_ids']);
            }

            return $product->refresh()->load(['options.values', 'variants.inventoryItem', 'collections']);
        });
    }

    public function transitionStatus(Product $product, ProductStatus $newStatus): void
    {
        $currentStatus = $product->status;

        if ($currentStatus === $newStatus) {
            return;
        }

        $allowedTransitions = [
            ProductStatus::Draft->value => [ProductStatus::Active, ProductStatus::Archived],
            ProductStatus::Active->value => [ProductStatus::Archived, ProductStatus::Draft],
            ProductStatus::Archived->value => [ProductStatus::Active, ProductStatus::Draft],
        ];

        if (! in_array($newStatus, $allowedTransitions[$currentStatus->value], true)) {
            throw new InvalidProductTransitionException($product->getKey(), $currentStatus, $newStatus, 'the transition is not allowed');
        }

        if ($newStatus === ProductStatus::Active) {
            if (trim($product->title) === '' || ! $product->variants()->where('price_amount', '>', 0)->exists()) {
                throw new InvalidProductTransitionException($product->getKey(), $currentStatus, $newStatus, 'an active product requires a title and a priced variant');
            }
        }

        if ($newStatus === ProductStatus::Draft && $this->hasOrderReferences($product)) {
            throw new InvalidProductTransitionException($product->getKey(), $currentStatus, $newStatus, 'products referenced by orders cannot return to draft');
        }

        DB::transaction(function () use ($product, $currentStatus, $newStatus): void {
            $product->status = $newStatus;

            if ($newStatus === ProductStatus::Active && $product->published_at === null) {
                $product->published_at = now();
            }

            $product->save();
            ProductStatusChanged::dispatch($product, $currentStatus, $newStatus);
        });
    }

    public function delete(Product $product): void
    {
        if ($product->status !== ProductStatus::Draft) {
            throw new InvalidProductDeletionException($product->getKey(), 'only draft products may be hard deleted');
        }

        if ($this->hasOrderReferences($product)) {
            throw new InvalidProductDeletionException($product->getKey(), 'order history references this product');
        }

        DB::transaction(fn (): bool => $product->delete());
    }

    private function syncOptions(Product $product, mixed $options): void
    {
        if (! is_array($options) || count($options) > 3) {
            throw new InvalidVariantMatrixException($product->getKey(), 'Products may have at most three options.');
        }

        $product->options()->increment('position', 1000);
        $keptOptionIds = [];

        foreach (array_values($options) as $optionPosition => $optionData) {
            if (! is_array($optionData) || ! filled($optionData['name'] ?? null) || ! filled($optionData['values'] ?? null)) {
                throw new InvalidVariantMatrixException($product->getKey(), 'Every option requires a name and at least one value.');
            }

            $optionId = isset($optionData['id']) ? (int) $optionData['id'] : null;
            $option = $optionId === null
                ? $product->options()->create(['name' => $optionData['name'], 'position' => $optionPosition])
                : $product->options()->whereKey($optionId)->firstOrFail();
            $option->update(['name' => $optionData['name'], 'position' => $optionPosition]);
            $keptOptionIds[] = $option->getKey();
            $this->syncOptionValues($option, $optionData['values']);
        }

        $product->options()->whereKeyNot($keptOptionIds)->delete();
    }

    private function syncOptionValues(ProductOption $option, mixed $values): void
    {
        if (! is_array($values) || $values === []) {
            throw new InvalidVariantMatrixException($option->product_id, 'Every option requires at least one value.');
        }

        $option->values()->increment('position', 1000);
        $keptValueIds = [];

        foreach (array_values($values) as $position => $valueData) {
            $valueData = is_array($valueData) ? $valueData : ['value' => $valueData];
            $valueId = isset($valueData['id']) ? (int) $valueData['id'] : null;
            $value = $valueId === null
                ? $option->values()->create(['value' => $valueData['value'], 'position' => $position])
                : $option->values()->whereKey($valueId)->firstOrFail();
            $value->update(['value' => $valueData['value'], 'position' => $position]);
            $keptValueIds[] = $value->getKey();
        }

        $option->values()->whereKeyNot($keptValueIds)->delete();
    }

    private function syncVariants(Product $product, mixed $variants): void
    {
        if (! is_array($variants)) {
            throw new InvalidVariantMatrixException($product->getKey(), 'Variants must be an array.');
        }

        foreach (array_values($variants) as $position => $variantData) {
            if (! is_array($variantData)) {
                throw new InvalidVariantMatrixException($product->getKey(), 'Every variant must be an array.');
            }

            $inventoryData = Arr::pull($variantData, 'inventory');
            $optionValueIds = Arr::pull($variantData, 'option_value_ids', []);
            $variantId = Arr::pull($variantData, 'id');
            $variantData['position'] = $position;
            $variantData['currency'] ??= $product->store()->value('default_currency');
            $variant = $variantId === null
                ? $product->variants()->create($variantData)
                : $product->variants()->whereKey($variantId)->firstOrFail();

            if ($variantId !== null) {
                $variant->update($variantData);
            }

            if (is_array($optionValueIds)) {
                $variant->optionValues()->sync($optionValueIds);
            }

            if (is_array($inventoryData)) {
                $variant->inventoryItem()->update($inventoryData);
            }
        }
    }

    private function syncCollections(Product $product, mixed $collectionIds): void
    {
        if (! is_array($collectionIds)) {
            return;
        }

        $validIds = Collection::withoutGlobalScopes()
            ->where('store_id', $product->store_id)
            ->whereKey($collectionIds)
            ->pluck('id');
        $pivot = $validIds->mapWithKeys(fn (int $id, int $position): array => [$id => ['position' => $position]])->all();
        $product->collections()->sync($pivot);
    }

    private function hasOrderReferences(Product $product): bool
    {
        if (! Schema::hasTable('order_lines')) {
            return false;
        }

        return OrderLine::withoutGlobalScopes()
            ->whereIn('variant_id', ProductVariant::withoutGlobalScopes()->where('product_id', $product->getKey())->select('id'))
            ->exists();
    }
}
