<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Events\ProductStatusChanged;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;

class ProductService
{
    public function __construct(
        private readonly HandleGenerator $handles,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data): Product {
            $requestedStatus = $this->requestedProductStatus($data) ?? ProductStatus::Draft;

            $product = Product::query()->create([
                ...Arr::only($data, [
                    'title',
                    'description_html',
                    'vendor',
                    'product_type',
                    'tags',
                ]),
                'store_id' => $store->getKey(),
                'handle' => $data['handle'] ?? $this->handles->generate($data['title'], 'products', $store->getKey()),
                'status' => ProductStatus::Draft,
                'published_at' => null,
            ]);

            foreach ($data['options'] ?? [] as $optionData) {
                $option = $product->options()->create(Arr::only($optionData, ['name', 'position']));

                foreach ($optionData['values'] ?? [] as $valueData) {
                    $option->values()->create(Arr::only($valueData, ['value', 'position']));
                }
            }

            $variants = $data['variants'] ?? [];

            if ($variants === []) {
                $variants = [[
                    'sku' => $data['sku'] ?? null,
                    'price_amount' => $data['price_amount'] ?? 0,
                    'currency' => $store->default_currency,
                    'is_default' => true,
                    'position' => 0,
                ]];
            }

            foreach ($variants as $variantData) {
                $this->createVariant($product, $store, $variantData);
            }

            if ($requestedStatus !== ProductStatus::Draft) {
                $this->transitionStatus($product->refresh(), $requestedStatus);
            }

            return $product->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $storeId = $product->store_id;
            $requestedStatus = $this->requestedProductStatus($data);

            $product->fill(Arr::only($data, [
                'title',
                'description_html',
                'vendor',
                'product_type',
                'tags',
            ]));

            if (array_key_exists('handle', $data)) {
                $product->handle = $data['handle'];
            } elseif (array_key_exists('title', $data)) {
                $product->handle = $this->handles->generate($data['title'], 'products', $storeId, $product->getKey());
            }

            $product->save();
            $product = $product->refresh();

            if ($requestedStatus !== null && $requestedStatus !== $this->productStatus($product)) {
                $this->transitionStatus($product, $requestedStatus);
            }

            return $product->refresh();
        });
    }

    public function transitionStatus(Product $product, ProductStatus $newStatus): void
    {
        $currentStatus = $this->productStatus($product);

        if ($currentStatus === $newStatus) {
            return;
        }

        if ($newStatus === ProductStatus::Active) {
            $this->assertCanActivate($product);
        }

        if ($newStatus === ProductStatus::Draft && $this->hasOrderLineReferences($product)) {
            throw InvalidProductTransitionException::because('Products with order history cannot be reverted to draft.');
        }

        if (! $this->isAllowedTransition($currentStatus, $newStatus)) {
            throw InvalidProductTransitionException::because("Cannot transition product from {$currentStatus->value} to {$newStatus->value}.");
        }

        $product->forceFill([
            'status' => $newStatus,
            'published_at' => $newStatus === ProductStatus::Active
                ? ($product->published_at ?? now())
                : $product->published_at,
        ])->save();

        ProductStatusChanged::dispatch($product->refresh(), $currentStatus, $newStatus);
    }

    public function delete(Product $product): void
    {
        if ($this->productStatus($product) !== ProductStatus::Draft || $this->hasOrderLineReferences($product)) {
            throw InvalidProductTransitionException::because('Only draft products with no order history can be deleted.');
        }

        $product->delete();
    }

    /**
     * @param  array<string, mixed>  $variantData
     */
    private function createVariant(Product $product, Store $store, array $variantData): ProductVariant
    {
        $this->assertSkuIsUnique($store, $variantData['sku'] ?? null);

        $variant = $product->variants()->create([
            ...Arr::only($variantData, [
                'sku',
                'barcode',
                'price_amount',
                'compare_at_amount',
                'currency',
                'weight_g',
                'requires_shipping',
                'is_default',
                'position',
                'status',
            ]),
            'currency' => $variantData['currency'] ?? $store->default_currency,
            'status' => $variantData['status'] ?? VariantStatus::Active,
        ]);

        InventoryItem::withoutGlobalScopes()->updateOrCreate(
            ['variant_id' => $variant->getKey()],
            [
                'store_id' => $store->getKey(),
                'quantity_on_hand' => $variantData['quantity_on_hand'] ?? 0,
                'quantity_reserved' => 0,
                'policy' => $variantData['inventory_policy'] ?? 'deny',
            ],
        );

        $this->syncVariantOptionValues($product, $variant, $variantData);

        return $variant;
    }

    /**
     * @param  array<string, mixed>  $variantData
     */
    private function syncVariantOptionValues(Product $product, ProductVariant $variant, array $variantData): void
    {
        if (isset($variantData['option_value_ids'])) {
            $valueIds = collect($variantData['option_value_ids'])
                ->map(fn (mixed $valueId): int => (int) $valueId)
                ->values()
                ->all();

            $validCount = ProductOptionValue::query()
                ->whereIn('id', $valueIds)
                ->whereHas('option', fn ($query) => $query->where('product_id', $product->getKey()))
                ->count();

            if ($validCount !== count($valueIds)) {
                throw new InvalidArgumentException('Variant option values must belong to the product being created.');
            }

            $variant->optionValues()->sync($valueIds);

            return;
        }

        if (! isset($variantData['options']) || ! is_array($variantData['options'])) {
            return;
        }

        $valueIds = [];

        foreach ($variantData['options'] as $optionName => $optionValue) {
            $valueId = ProductOptionValue::query()
                ->where('value', (string) $optionValue)
                ->whereHas('option', function ($query) use ($product, $optionName): void {
                    $query
                        ->where('product_id', $product->getKey())
                        ->where('name', (string) $optionName);
                })
                ->value('id');

            if ($valueId === null) {
                throw new InvalidArgumentException("Variant option selection [{$optionName}: {$optionValue}] is invalid for this product.");
            }

            $valueIds[] = (int) $valueId;
        }

        $variant->optionValues()->sync($valueIds);
    }

    private function assertCanActivate(Product $product): void
    {
        if (trim((string) $product->title) === '') {
            throw InvalidProductTransitionException::because('A product title is required before activation.');
        }

        if (! $product->variants()->where('price_amount', '>', 0)->exists()) {
            throw InvalidProductTransitionException::because('At least one priced variant is required before activation.');
        }
    }

    private function isAllowedTransition(ProductStatus $from, ProductStatus $to): bool
    {
        return match ($from) {
            ProductStatus::Draft => in_array($to, [ProductStatus::Active, ProductStatus::Archived], true),
            ProductStatus::Active => in_array($to, [ProductStatus::Archived, ProductStatus::Draft], true),
            ProductStatus::Archived => in_array($to, [ProductStatus::Active, ProductStatus::Draft], true),
        };
    }

    private function hasOrderLineReferences(Product $product): bool
    {
        if (! Schema::hasTable('order_lines')) {
            return false;
        }

        $variantIds = $product->variants()->pluck('id');

        if ($variantIds->isEmpty()) {
            return false;
        }

        return DB::table('order_lines')
            ->whereIn('variant_id', $variantIds)
            ->exists();
    }

    private function assertSkuIsUnique(Store $store, ?string $sku, ?int $excludeVariantId = null): void
    {
        if ($sku === null || trim($sku) === '') {
            return;
        }

        $query = ProductVariant::query()
            ->where('sku', $sku)
            ->whereHas('product', fn ($query) => $query->where('store_id', $store->getKey()));

        if ($excludeVariantId !== null) {
            $query->whereKeyNot($excludeVariantId);
        }

        if ($query->exists()) {
            throw new RuntimeException("The SKU [{$sku}] is already used in this store.");
        }
    }

    private function productStatus(Product $product): ProductStatus
    {
        return $product->status instanceof ProductStatus
            ? $product->status
            : ProductStatus::from($product->status);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function requestedProductStatus(array $data): ?ProductStatus
    {
        if (! array_key_exists('status', $data)) {
            return null;
        }

        if ($data['status'] instanceof ProductStatus) {
            return $data['status'];
        }

        return ProductStatus::from((string) $data['status']);
    }
}
