<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Events\ProductStatusChanged;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Support\HandleGenerator;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use LogicException;

class ProductService
{
    public function __construct(private readonly HandleGenerator $handles, private readonly HtmlSanitizer $sanitizer, private readonly AuditLogger $audit) {}

    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data): Product {
            $data['variants'] ??= [['title' => 'Default', 'price_amount' => 0, 'is_default' => true]];
            $status = $data['status'] ?? ProductStatus::Draft;
            $status = $status instanceof ProductStatus ? $status : ProductStatus::from($status);
            $product = Product::withoutGlobalScopes()->create([
                'store_id' => $store->getKey(),
                'title' => $data['title'],
                'handle' => $data['handle'] ?? $this->handles->generate($data['title'], 'products', $store->getKey()),
                'description' => $this->sanitizer->sanitize($data['description'] ?? null),
                'description_html' => $this->sanitizer->sanitize($data['description_html'] ?? $data['description'] ?? null),
                'vendor' => $data['vendor'] ?? null,
                'product_type' => $data['product_type'] ?? null,
                'tags' => $data['tags'] ?? [],
                'status' => $status,
                'published_at' => $status === ProductStatus::Active ? now() : null,
            ]);

            $this->syncCatalogDetails($product, $store, $data, true);

            if ($status === ProductStatus::Active && (trim((string) $product->title) === '' || ! $product->variants()->where('price_amount', '>', 0)->exists())) {
                throw new InvalidProductTransitionException('An active product requires a title and a priced variant.');
            }

            $this->audit->record('product.created', $product, ['store_id' => $store->getKey()]);

            return $product->load(['variants.inventory', 'options.values', 'variants.optionValues', 'media', 'collections']);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $updates = array_intersect_key($data, array_flip(['title', 'handle', 'description', 'description_html', 'vendor', 'product_type', 'tags', 'published_at']));

            $newStatus = null;

            if (array_key_exists('status', $data)) {
                $newStatus = $data['status'] instanceof ProductStatus ? $data['status'] : ProductStatus::from($data['status']);
            }

            if (array_key_exists('description', $data)) {
                $updates['description'] = $this->sanitizer->sanitize($data['description']);
                $updates['description_html'] = $this->sanitizer->sanitize($data['description']);
            } elseif (array_key_exists('description_html', $data)) {
                $updates['description_html'] = $this->sanitizer->sanitize($data['description_html']);
            }

            $product->update($updates);

            if ($newStatus !== null) {
                $this->transitionStatus($product->refresh(), $newStatus);
            }

            if (array_key_exists('options', $data) || array_key_exists('variants', $data) || array_key_exists('remove_variant_ids', $data) || array_key_exists('collections', $data)) {
                $this->syncCatalogDetails($product->refresh(), $product->store, $data, array_key_exists('options', $data));
            }

            $product = $product->refresh()->load(['variants.inventory', 'options.values', 'variants.optionValues', 'media', 'collections']);
            $this->audit->record('product.updated', $product, ['store_id' => $product->store_id]);

            return $product;
        });
    }

    public function transitionStatus(Product $product, ProductStatus $newStatus): void
    {
        $from = $product->status instanceof ProductStatus ? $product->status : ProductStatus::from($product->status);

        if ($from === $newStatus) {
            return;
        }

        if ($newStatus === ProductStatus::Active && (! $product->variants()->where('price_amount', '>', 0)->exists() || trim((string) $product->title) === '')) {
            throw new InvalidProductTransitionException('An active product requires a title and a priced variant.');
        }

        if ($newStatus === ProductStatus::Draft && in_array($from, [ProductStatus::Active, ProductStatus::Archived], true) && $product->orders()->exists()) {
            throw new InvalidProductTransitionException('Products with order history cannot be reverted to draft.');
        }

        $product->update(['status' => $newStatus, 'published_at' => $newStatus === ProductStatus::Active ? ($product->published_at ?? now()) : null]);
        ProductStatusChanged::dispatch($product->refresh(), $from, $newStatus);
        $this->audit->record('product.updated', $product, ['from_status' => $from->value, 'to_status' => $newStatus->value, 'store_id' => $product->store_id]);
    }

    public function delete(Product $product): void
    {
        if ($product->status !== ProductStatus::Draft || $product->orders()->exists()) {
            throw new LogicException('Only draft products with no order history can be deleted.');
        }

        $product->delete();
        $this->audit->record('product.deleted', $product, ['store_id' => $product->store_id]);
    }

    /**
     * Persist product options, variants, inventory, option-value links, and collections.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncCatalogDetails(Product $product, Store $store, array $data, bool $replaceOptions): void
    {
        $optionsByName = $this->optionMap($product, $data['options'] ?? [], $replaceOptions);
        $variants = $data['variants'] ?? [];

        foreach ($variants as $position => $variantData) {
            $optionValues = $variantData['option_values'] ?? [];
            $inventoryData = $variantData['inventory'] ?? null;
            $variantId = $variantData['id'] ?? null;
            $attributes = Arr::except($variantData, ['id', 'option_values', 'inventory', 'quantity_on_hand', 'policy']);
            $weight = $attributes['weight_g'] ?? $attributes['weight_grams'] ?? 0;
            $attributes['weight_g'] = $weight;
            $attributes['weight_grams'] = $weight;
            $attributes['currency'] ??= $store->default_currency;
            $attributes['position'] ??= $position + 1;
            $attributes['is_default'] ??= $position === 0;
            $attributes['title'] ??= $this->variantTitle($optionValues);

            if (($attributes['compare_at_amount'] ?? null) !== null && (int) $attributes['compare_at_amount'] <= (int) ($attributes['price_amount'] ?? 0)) {
                throw new \InvalidArgumentException('A compare-at price must be greater than the variant price.');
            }

            if (($attributes['sku'] ?? null) !== null) {
                $skuQuery = ProductVariant::query()->where('sku', $attributes['sku'])->whereHas('product', fn ($query) => $query->where('store_id', $store->getKey()));
                if ($variantId !== null) {
                    $skuQuery->where('id', '!=', $variantId);
                }
                if ($skuQuery->exists()) {
                    throw new \InvalidArgumentException('Variant SKUs must be unique within a store.');
                }
            }

            if ($variantId !== null) {
                $variant = $product->variants()->whereKey($variantId)->first();
                if ($variant === null) {
                    throw new \InvalidArgumentException('The variant does not belong to this product.');
                }
                $variant->update($attributes);
            } else {
                $variant = $product->variants()->create($attributes);
            }

            if ($inventoryData !== null || $variantId === null) {
                $inventoryData ??= [];
                InventoryItem::withoutGlobalScopes()->updateOrCreate(
                    ['variant_id' => $variant->getKey()],
                    ['store_id' => $store->getKey(), 'quantity_on_hand' => (int) ($inventoryData['quantity_on_hand'] ?? 0), 'policy' => $inventoryData['policy'] ?? 'deny'],
                );
            }

            if ($optionValues !== []) {
                $variant->optionValues()->sync($this->resolveOptionValueIds($optionsByName, $optionValues));
            }
        }

        if ($data['remove_variant_ids'] ?? false) {
            $product->variants()->whereIn('id', $data['remove_variant_ids'])->delete();
        }

        if (array_key_exists('variants', $data)) {
            $this->ensureSingleDefaultVariant($product);
        }

        if (array_key_exists('collections', $data)) {
            $collectionIds = $data['collections'] ?? [];
            $product->collections()->sync(array_fill_keys($collectionIds, ['position' => 0]));
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $optionData
     * @return array<string, ProductOption>
     */
    private function optionMap(Product $product, array $optionData, bool $replace): array
    {
        if ($replace) {
            $product->options()->delete();
        }

        $options = $replace ? collect() : $product->options()->with('values')->get();

        foreach ($optionData as $position => $optionDataItem) {
            $option = $product->options()->updateOrCreate(
                ['position' => $optionDataItem['position'] ?? $position + 1],
                ['name' => $optionDataItem['name']],
            );
            $options = $options->push($option->load('values'));

            foreach ($optionDataItem['values'] ?? [] as $valuePosition => $valueData) {
                $option->values()->updateOrCreate(
                    ['value' => $valueData['value']],
                    ['position' => $valueData['position'] ?? $valuePosition + 1],
                );
            }
        }

        return $options->keyBy('name')->all();
    }

    /**
     * @param  array<string, ProductOption>  $optionsByName
     * @param  array<int, array<string, string>>  $optionValues
     * @return array<int, int>
     */
    private function resolveOptionValueIds(array $optionsByName, array $optionValues): array
    {
        $ids = [];

        foreach ($optionValues as $optionValue) {
            $option = $optionsByName[$optionValue['option_name']] ?? null;
            if ($option === null) {
                throw new \InvalidArgumentException('Variant option values must match the product options.');
            }

            $value = $option->values()->firstOrCreate(['value' => $optionValue['value']], ['position' => $option->values()->count() + 1]);
            $ids[] = $value->getKey();
        }

        return $ids;
    }

    /** @param array<int, array<string, string>> $optionValues */
    private function variantTitle(array $optionValues): string
    {
        return $optionValues === [] ? 'Default' : implode(' / ', array_column($optionValues, 'value'));
    }

    private function ensureSingleDefaultVariant(Product $product): void
    {
        $variants = $product->variants()->get();
        $default = $variants->firstWhere('is_default', true) ?? $variants->first();

        if ($default === null) {
            throw new \InvalidArgumentException('A product must have at least one variant.');
        }

        $product->variants()->where('id', '!=', $default->getKey())->update(['is_default' => false]);
        $default->update(['is_default' => true]);
    }
}
