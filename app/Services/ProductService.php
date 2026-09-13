<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Events\ProductStatusChanged;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Collection;
use App\Models\InventoryItem;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Product CRUD, status transitions and variant/option management
 * per spec 05 sections 2-3.
 */
class ProductService
{
    public function __construct(
        protected HandleGenerator $handles,
        protected VariantMatrixService $matrix,
        protected SearchService $search,
    ) {}

    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data): Product {
            $this->assertSkusUnique($store->id, $data['variants'] ?? [], []);

            $handle = $data['handle'] ?? $this->handles->generate($data['title'], 'products', $store->id);

            if (isset($data['handle']) && $this->handles->exists('products', $store->id, $handle)) {
                throw ValidationException::withMessages(['handle' => ['The handle has already been taken.']]);
            }

            $requestedStatus = isset($data['status']) ? ProductStatus::from($data['status']) : ProductStatus::Draft;

            $product = Product::query()->create([
                'store_id' => $store->id,
                'title' => $data['title'],
                'handle' => $handle,
                'status' => ProductStatus::Draft,
                'description_html' => $data['description_html'] ?? null,
                'vendor' => $data['vendor'] ?? null,
                'product_type' => $data['product_type'] ?? null,
                'tags' => $data['tags'] ?? [],
            ]);

            $this->syncOptions($product, $data['options'] ?? [], $data['variants'] ?? []);

            if (($data['variants'] ?? []) !== []) {
                $this->createVariants($product, $data['variants']);
            } elseif (($data['options'] ?? []) !== []) {
                $this->matrix->rebuildMatrix($product->refresh());
            } else {
                $this->createDefaultVariant($product);
            }

            $this->syncCollections($product, $data['collections'] ?? null);

            if ($requestedStatus !== ProductStatus::Draft) {
                $this->transitionStatus($product->refresh(), $requestedStatus);
            }

            $fresh = $product->fresh();
            $this->search->syncProduct($fresh);

            return $this->loadRelations($fresh);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            if (array_key_exists('variants', $data)) {
                $excludeIds = collect($data['variants'])->pluck('id')->filter()->all();
                $this->assertSkusUnique($product->store_id, $data['variants'], $excludeIds);
            }

            foreach (['title', 'description_html', 'vendor', 'product_type'] as $field) {
                if (array_key_exists($field, $data)) {
                    $product->{$field} = $data[$field];
                }
            }

            if (array_key_exists('tags', $data)) {
                $product->tags = $data['tags'] ?? [];
            }

            if (array_key_exists('handle', $data) && $data['handle'] !== null && $data['handle'] !== $product->handle) {
                if ($this->handles->exists('products', $product->store_id, $data['handle'], $product->getKey())) {
                    throw ValidationException::withMessages(['handle' => ['The handle has already been taken.']]);
                }

                $product->handle = $data['handle'];
            }

            $product->save();

            if (array_key_exists('collections', $data)) {
                $this->syncCollections($product, $data['collections']);
            }

            if (array_key_exists('options', $data)) {
                $this->syncOptions($product, $data['options'] ?? [], $data['variants'] ?? []);
            }

            if (array_key_exists('variants', $data)) {
                $this->reconcileVariants($product, $data['variants'] ?? []);
            } elseif (array_key_exists('options', $data)) {
                $this->matrix->rebuildMatrix($product->refresh());
            }

            if (array_key_exists('status', $data) && $data['status'] !== null) {
                $this->transitionStatus($product->refresh(), ProductStatus::from($data['status']));
            }

            $fresh = $product->fresh();
            $this->search->syncProduct($fresh);

            return $this->loadRelations($fresh);
        });
    }

    public function transitionStatus(Product $product, ProductStatus $newStatus): Product
    {
        $from = $product->status instanceof ProductStatus
            ? $product->status
            : ProductStatus::from((string) $product->status);

        if ($from === $newStatus) {
            return $product;
        }

        $this->assertTransitionAllowed($product, $from, $newStatus);

        return DB::transaction(function () use ($product, $from, $newStatus): Product {
            $product->status = $newStatus;

            if ($newStatus === ProductStatus::Active && $product->published_at === null) {
                $product->published_at = now();
            }

            $product->save();
            $this->search->syncProduct($product->refresh());

            event(new ProductStatusChanged($product, $from, $newStatus));

            return $product->refresh();
        });
    }

    public function archive(Product $product): Product
    {
        return $this->transitionStatus($product, ProductStatus::Archived);
    }

    public function delete(Product $product): void
    {
        $status = $product->status instanceof ProductStatus
            ? $product->status
            : ProductStatus::from((string) $product->status);

        if ($status !== ProductStatus::Draft) {
            throw new InvalidProductTransitionException('Only draft products can be deleted. Archive this product instead.');
        }

        if ($this->hasOrderReferences($product)) {
            throw new InvalidProductTransitionException('This product cannot be deleted because it has existing orders. Archive it instead.');
        }

        DB::transaction(function () use ($product): void {
            $this->search->removeProduct($product->getKey());
            $product->delete();
        });
    }

    protected function assertTransitionAllowed(Product $product, ProductStatus $from, ProductStatus $to): void
    {
        $activating = ($from === ProductStatus::Draft && $to === ProductStatus::Active)
            || ($from === ProductStatus::Archived && $to === ProductStatus::Active);

        if ($activating) {
            if (trim((string) $product->title) === '') {
                throw new InvalidProductTransitionException('A product title is required before publishing.');
            }

            $hasPricedVariant = $product->productVariants()->where('price_amount', '>', 0)->exists();

            if (! $hasPricedVariant) {
                throw new InvalidProductTransitionException('At least one variant with a price greater than zero is required before publishing.');
            }

            return;
        }

        $revertingToDraft = ($from === ProductStatus::Active && $to === ProductStatus::Draft)
            || ($from === ProductStatus::Archived && $to === ProductStatus::Draft);

        if ($revertingToDraft && $this->hasOrderReferences($product)) {
            throw new InvalidProductTransitionException('This product cannot be reverted to draft because it has existing orders.');
        }
    }

    protected function hasOrderReferences(Product $product): bool
    {
        $variantIds = $product->productVariants()->pluck('id');

        if ($variantIds->isEmpty()) {
            return false;
        }

        return OrderLine::query()->whereIn('variant_id', $variantIds)->exists();
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     * @param  array<int, int>  $excludeVariantIds
     */
    protected function assertSkusUnique(int $storeId, array $variants, array $excludeVariantIds): void
    {
        $skusByIndex = [];

        foreach ($variants as $index => $variant) {
            $sku = trim((string) ($variant['sku'] ?? ''));

            if ($sku === '') {
                continue;
            }

            $skusByIndex[$index] = $sku;
        }

        if ($skusByIndex === []) {
            return;
        }

        $duplicates = array_diff_assoc($skusByIndex, array_unique($skusByIndex));
        $errors = [];

        foreach ($duplicates as $index => $sku) {
            $errors["variants.{$index}.sku"] = ['The sku must be unique within this store.'];
        }

        $taken = ProductVariant::query()
            ->whereHas('product', fn ($query) => $query->where('store_id', $storeId))
            ->whereIn('sku', array_values(array_unique($skusByIndex)))
            ->when($excludeVariantIds !== [], fn ($query) => $query->whereNotIn('id', $excludeVariantIds))
            ->pluck('sku')
            ->all();

        foreach ($skusByIndex as $index => $sku) {
            if (in_array($sku, $taken, true)) {
                $errors["variants.{$index}.sku"] = ['The sku has already been taken.'];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     * @param  array<int, array<string, mixed>>  $variants
     */
    protected function syncOptions(Product $product, array $options, array $variants): void
    {
        $names = collect($options)->pluck('name')->map(fn ($name) => trim((string) $name))->all();

        $product->productOptions()->whereNotIn('name', $names)->delete();

        // Two-phase position update: park existing rows at unique negative
        // positions first so re-positioning never collides mid-update with the
        // UNIQUE(product_id, position) constraint (e.g. seeded options are
        // 0-based while form/API payloads are 1-based, so naive in-place
        // updates clash with rows that have not been moved yet).
        $product->productOptions()->update(['position' => DB::raw('-id - 1000000')]);

        foreach ($options as $index => $optionData) {
            $name = trim((string) $optionData['name']);

            $option = $product->productOptions()->updateOrCreate(
                ['name' => $name],
                ['position' => $optionData['position'] ?? $index + 1]
            );

            $values = collect($optionData['values'] ?? [])->map(fn ($value) => trim((string) $value))->all();

            foreach ($variants as $variantData) {
                foreach ($variantData['option_values'] ?? [] as $optionValue) {
                    if (trim((string) ($optionValue['option_name'] ?? '')) === $name) {
                        $values[] = trim((string) ($optionValue['value'] ?? ''));
                    }
                }
            }

            $values = array_values(array_unique(array_filter($values)));

            $option->productOptionValues()->whereNotIn('value', $values)->delete();

            // Park value positions as well: prepending/reordering values while
            // adding new ones can otherwise collide mid-update with the
            // UNIQUE(product_option_id, position) constraint.
            $option->productOptionValues()->update(['position' => DB::raw('-id - 1000000')]);

            foreach ($values as $position => $value) {
                $option->productOptionValues()->updateOrCreate(
                    ['value' => $value],
                    ['position' => $position + 1]
                );
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    protected function createVariants(Product $product, array $variants): void
    {
        $defaultIndex = $this->resolveDefaultIndex($variants);

        foreach ($variants as $index => $variantData) {
            $this->assertCompareAtValid($variantData, $index);

            $variant = $product->productVariants()->create([
                'sku' => $this->normalizeSku($variantData['sku'] ?? null),
                'barcode' => $variantData['barcode'] ?? null,
                'price_amount' => $variantData['price_amount'] ?? 0,
                'compare_at_amount' => $variantData['compare_at_amount'] ?? null,
                'currency' => $variantData['currency'] ?? $product->store?->default_currency ?? 'USD',
                'weight_g' => $variantData['weight_g'] ?? null,
                'requires_shipping' => $variantData['requires_shipping'] ?? true,
                'is_default' => $index === $defaultIndex,
                'position' => $variantData['position'] ?? $index,
                'status' => isset($variantData['status']) ? VariantStatus::from($variantData['status']) : VariantStatus::Active,
            ]);

            $this->linkOptionValues($product, $variant, $variantData['option_values'] ?? [], $index);
            $this->createInventoryItem($product, $variant, $variantData['inventory'] ?? []);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    protected function reconcileVariants(Product $product, array $variants): void
    {
        $keptIds = [];

        foreach ($variants as $index => $variantData) {
            $this->assertCompareAtValid($variantData, $index);

            if (isset($variantData['id'])) {
                $variant = $product->productVariants()->whereKey($variantData['id'])->first();

                if ($variant === null) {
                    throw ValidationException::withMessages(["variants.{$index}.id" => ['The selected variant does not belong to this product.']]);
                }

                foreach (['sku', 'barcode', 'price_amount', 'compare_at_amount', 'currency', 'weight_g', 'requires_shipping', 'position'] as $field) {
                    if (array_key_exists($field, $variantData)) {
                        $variant->{$field} = $field === 'sku' ? $this->normalizeSku($variantData[$field]) : $variantData[$field];
                    }
                }

                if (array_key_exists('status', $variantData) && $variantData['status'] !== null) {
                    $variant->status = VariantStatus::from($variantData['status']);
                }

                if (array_key_exists('is_default', $variantData)) {
                    $variant->is_default = (bool) $variantData['is_default'];
                }

                $variant->save();

                if (array_key_exists('option_values', $variantData)) {
                    $this->linkOptionValues($product, $variant, $variantData['option_values'] ?? [], $index, true);
                }

                if (array_key_exists('inventory', $variantData)) {
                    $this->updateInventoryItem($product, $variant, $variantData['inventory'] ?? []);
                }

                $keptIds[] = $variant->getKey();

                continue;
            }

            $variant = $product->productVariants()->create([
                'sku' => $this->normalizeSku($variantData['sku'] ?? null),
                'barcode' => $variantData['barcode'] ?? null,
                'price_amount' => $variantData['price_amount'] ?? 0,
                'compare_at_amount' => $variantData['compare_at_amount'] ?? null,
                'currency' => $variantData['currency'] ?? $product->store?->default_currency ?? 'USD',
                'weight_g' => $variantData['weight_g'] ?? null,
                'requires_shipping' => $variantData['requires_shipping'] ?? true,
                'is_default' => false,
                'position' => $variantData['position'] ?? $product->productVariants()->max('position') + 1,
                'status' => isset($variantData['status']) ? VariantStatus::from($variantData['status']) : VariantStatus::Active,
            ]);

            $this->linkOptionValues($product, $variant, $variantData['option_values'] ?? [], $index);
            $this->createInventoryItem($product, $variant, $variantData['inventory'] ?? []);
            $keptIds[] = $variant->getKey();
        }

        $orphans = $product->productVariants()->whereNotIn('id', $keptIds)->get();

        foreach ($orphans as $orphan) {
            if (OrderLine::query()->where('variant_id', $orphan->getKey())->exists()) {
                $orphan->update(['status' => VariantStatus::Archived]);
            } else {
                $orphan->delete();
            }
        }

        $this->ensureSingleDefault($product);
    }

    protected function createDefaultVariant(Product $product): void
    {
        $variant = $product->productVariants()->create([
            'sku' => null,
            'barcode' => null,
            'price_amount' => 0,
            'compare_at_amount' => null,
            'currency' => $product->store?->default_currency ?? 'USD',
            'weight_g' => null,
            'requires_shipping' => true,
            'is_default' => true,
            'position' => 0,
            'status' => VariantStatus::Active,
        ]);

        $this->createInventoryItem($product, $variant, []);
    }

    /**
     * @param  array<int, array<string, mixed>>  $optionValues
     */
    protected function linkOptionValues(Product $product, ProductVariant $variant, array $optionValues, int $variantIndex, bool $detachFirst = false): void
    {
        if ($detachFirst) {
            $variant->optionValues()->detach();
        }

        $valueIds = [];

        foreach ($optionValues as $entry) {
            $optionName = trim((string) ($entry['option_name'] ?? ''));
            $value = trim((string) ($entry['value'] ?? ''));

            if ($optionName === '' || $value === '') {
                continue;
            }

            $option = $product->productOptions()->where('name', $optionName)->first();

            if ($option === null) {
                throw ValidationException::withMessages([
                    "variants.{$variantIndex}.option_values" => ["Option [{$optionName}] is not defined for this product."],
                ]);
            }

            $optionValue = $option->productOptionValues()->firstOrCreate(
                ['value' => $value],
                ['position' => ($option->productOptionValues()->max('position') ?? 0) + 1]
            );

            $valueIds[] = $optionValue->getKey();
        }

        if ($valueIds !== []) {
            $variant->optionValues()->syncWithoutDetaching($valueIds);
        }
    }

    /**
     * @param  array<string, mixed>  $inventory
     */
    protected function createInventoryItem(Product $product, ProductVariant $variant, array $inventory): void
    {
        InventoryItem::query()->create([
            'store_id' => $product->store_id,
            'variant_id' => $variant->getKey(),
            'quantity_on_hand' => $inventory['quantity_on_hand'] ?? 0,
            'quantity_reserved' => 0,
            'policy' => isset($inventory['policy']) ? InventoryPolicy::from($inventory['policy']) : InventoryPolicy::Deny,
        ]);
    }

    /**
     * @param  array<string, mixed>  $inventory
     */
    protected function updateInventoryItem(Product $product, ProductVariant $variant, array $inventory): void
    {
        $item = InventoryItem::query()->firstOrCreate(
            ['variant_id' => $variant->getKey()],
            ['store_id' => $product->store_id, 'quantity_on_hand' => 0, 'quantity_reserved' => 0, 'policy' => InventoryPolicy::Deny]
        );

        if (array_key_exists('quantity_on_hand', $inventory) && $inventory['quantity_on_hand'] !== null) {
            $item->quantity_on_hand = $inventory['quantity_on_hand'];
        }

        if (array_key_exists('policy', $inventory) && $inventory['policy'] !== null) {
            $item->policy = InventoryPolicy::from($inventory['policy']);
        }

        $item->save();
    }

    /**
     * @param  array<int, int>|null  $collectionIds
     */
    protected function syncCollections(Product $product, ?array $collectionIds): void
    {
        if ($collectionIds === null) {
            return;
        }

        $validIds = Collection::query()
            ->where('store_id', $product->store_id)
            ->whereIn('id', $collectionIds)
            ->pluck('id')
            ->all();

        $unknown = array_diff($collectionIds, $validIds);

        if ($unknown !== []) {
            throw ValidationException::withMessages(['collections' => ['One or more collections do not belong to this store.']]);
        }

        $sync = [];

        foreach (array_values($collectionIds) as $position => $collectionId) {
            $sync[$collectionId] = ['position' => $position];
        }

        $product->collections()->sync($sync);
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    protected function resolveDefaultIndex(array $variants): int
    {
        foreach ($variants as $index => $variant) {
            if (! empty($variant['is_default'])) {
                return $index;
            }
        }

        return 0;
    }

    protected function ensureSingleDefault(Product $product): void
    {
        $variants = $product->productVariants()->orderBy('position')->orderBy('id')->get();

        if ($variants->isEmpty()) {
            return;
        }

        $default = $variants->firstWhere('is_default', true) ?? $variants->first();

        foreach ($variants as $variant) {
            $shouldBeDefault = $variant->getKey() === $default->getKey();

            if ((bool) $variant->is_default !== $shouldBeDefault) {
                $variant->update(['is_default' => $shouldBeDefault]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $variantData
     */
    protected function assertCompareAtValid(array $variantData, int $index): void
    {
        if (! array_key_exists('compare_at_amount', $variantData) || $variantData['compare_at_amount'] === null) {
            return;
        }

        if ($variantData['compare_at_amount'] <= ($variantData['price_amount'] ?? 0)) {
            throw ValidationException::withMessages([
                "variants.{$index}.compare_at_amount" => ['The compare at amount must be greater than the price.'],
            ]);
        }
    }

    protected function normalizeSku(mixed $sku): ?string
    {
        $sku = trim((string) $sku);

        return $sku === '' ? null : $sku;
    }

    protected function loadRelations(Product $product): Product
    {
        return $product->load([
            'productOptions' => fn ($query) => $query->orderBy('position'),
            'productOptions.productOptionValues' => fn ($query) => $query->orderBy('position'),
            'productVariants' => fn ($query) => $query->orderBy('position')->orderBy('id'),
            'productVariants.inventoryItem',
            'productVariants.optionValues.productOption',
            'productMedia' => fn ($query) => $query->orderBy('position')->orderBy('id'),
            'collections',
        ]);
    }

    public function findOption(Product $product, string $name): ?ProductOption
    {
        return $product->productOptions()->where('name', $name)->first();
    }
}
