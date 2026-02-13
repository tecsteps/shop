<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        private HandleGenerator $handleGenerator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data): Product {
            $handle = $this->handleGenerator->generate(
                $data['title'],
                'products',
                $store->id,
            );

            $product = Product::withoutGlobalScopes()->create([
                'store_id' => $store->id,
                'title' => $data['title'],
                'handle' => $data['handle'] ?? $handle,
                'status' => $data['status'] ?? ProductStatus::Draft,
                'description_html' => $data['description_html'] ?? null,
                'vendor' => $data['vendor'] ?? null,
                'product_type' => $data['product_type'] ?? null,
                'tags' => $data['tags'] ?? [],
                'published_at' => $data['published_at'] ?? null,
            ]);

            if (isset($data['options'])) {
                $this->createOptions($product, $data['options']);
            }

            if (isset($data['variants'])) {
                $this->createVariants($product, $store, $data['variants']);
            } else {
                $this->createDefaultVariant($product, $store, $data);
            }

            return $product->load(['variants', 'options.values']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $updates = [];

            if (isset($data['title'])) {
                $updates['title'] = $data['title'];

                if (! isset($data['handle'])) {
                    $updates['handle'] = $this->handleGenerator->generate(
                        $data['title'],
                        'products',
                        $product->store_id,
                        $product->id,
                    );
                }
            }

            if (isset($data['handle'])) {
                $updates['handle'] = $data['handle'];
            }

            foreach (['description_html', 'vendor', 'product_type', 'tags', 'published_at', 'status'] as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[$field] = $data[$field];
                }
            }

            $product->update($updates);

            return $product->fresh(['variants', 'options.values']);
        });
    }

    public function transitionStatus(Product $product, ProductStatus $newStatus): void
    {
        $allowedTransitions = [
            ProductStatus::Draft->value => [ProductStatus::Active],
            ProductStatus::Active->value => [ProductStatus::Archived],
            ProductStatus::Archived->value => [ProductStatus::Active],
        ];

        $allowed = $allowedTransitions[$product->status->value] ?? [];

        if (! in_array($newStatus, $allowed)) {
            throw new InvalidProductTransitionException($product->status, $newStatus);
        }

        if ($newStatus === ProductStatus::Active) {
            $hasPricedVariant = $product->variants()
                ->where('status', 'active')
                ->where('price_amount', '>', 0)
                ->exists();

            if (! $hasPricedVariant) {
                throw new InvalidProductTransitionException(
                    $product->status,
                    $newStatus,
                    'Cannot activate product without at least one active variant with a price.',
                );
            }
        }

        $product->update([
            'status' => $newStatus,
            'published_at' => $newStatus === ProductStatus::Active ? ($product->published_at ?? now()) : $product->published_at,
        ]);
    }

    public function delete(Product $product): void
    {
        if ($product->status !== ProductStatus::Draft) {
            throw new InvalidProductTransitionException(
                $product->status,
                ProductStatus::Draft,
                'Only draft products can be deleted.',
            );
        }

        $product->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $optionsData
     */
    private function createOptions(Product $product, array $optionsData): void
    {
        foreach ($optionsData as $position => $optionData) {
            $option = $product->options()->create([
                'name' => $optionData['name'],
                'position' => $position,
            ]);

            if (isset($optionData['values'])) {
                foreach ($optionData['values'] as $valuePosition => $value) {
                    $option->values()->create([
                        'value' => $value,
                        'position' => $valuePosition,
                    ]);
                }
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $variantsData
     */
    private function createVariants(Product $product, Store $store, array $variantsData): void
    {
        foreach ($variantsData as $position => $variantData) {
            $variant = $product->variants()->create([
                'sku' => $variantData['sku'] ?? null,
                'barcode' => $variantData['barcode'] ?? null,
                'price_amount' => $variantData['price_amount'] ?? 0,
                'compare_at_amount' => $variantData['compare_at_amount'] ?? null,
                'currency' => $variantData['currency'] ?? 'USD',
                'weight_g' => $variantData['weight_g'] ?? null,
                'requires_shipping' => $variantData['requires_shipping'] ?? true,
                'is_default' => $variantData['is_default'] ?? ($position === 0),
                'position' => $position,
            ]);

            InventoryItem::withoutGlobalScopes()->create([
                'store_id' => $store->id,
                'variant_id' => $variant->id,
                'quantity_on_hand' => $variantData['quantity_on_hand'] ?? 0,
                'quantity_reserved' => 0,
            ]);

            if (isset($variantData['option_value_ids'])) {
                $variant->optionValues()->attach($variantData['option_value_ids']);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createDefaultVariant(Product $product, Store $store, array $data): void
    {
        $variant = $product->variants()->create([
            'sku' => $data['sku'] ?? null,
            'price_amount' => $data['price_amount'] ?? 0,
            'currency' => $data['currency'] ?? 'USD',
            'is_default' => true,
            'position' => 0,
        ]);

        InventoryItem::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'variant_id' => $variant->id,
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
        ]);
    }
}
