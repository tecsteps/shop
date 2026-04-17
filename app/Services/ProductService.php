<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class ProductService
{
    public function __construct(
        private HandleGenerator $handleGenerator,
    ) {}

    /**
     * Create a new product with an auto-generated handle.
     * Creates a default variant if no options are provided.
     */
    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data) {
            $handle = $this->handleGenerator->generate(
                $data['title'],
                'products',
                $store->id,
            );

            $product = Product::create([
                'store_id' => $store->id,
                'title' => $data['title'],
                'handle' => $handle,
                'description_html' => $data['description_html'] ?? null,
                'vendor' => $data['vendor'] ?? null,
                'product_type' => $data['product_type'] ?? null,
                'status' => $data['status'] ?? ProductStatus::Draft,
                'tags' => $data['tags'] ?? null,
            ]);

            if (empty($data['options'])) {
                $variant = $product->variants()->create([
                    'title' => 'Default',
                    'price_amount' => $data['price_amount'] ?? 0,
                    'compare_at_price_amount' => $data['compare_at_price_amount'] ?? null,
                    'sku' => $data['sku'] ?? null,
                    'barcode' => $data['barcode'] ?? null,
                    'position' => 1,
                    'is_default' => true,
                ]);

                $variant->inventoryItem()->create([
                    'sku' => $variant->sku,
                    'quantity_on_hand' => $data['quantity_on_hand'] ?? 0,
                    'quantity_reserved' => 0,
                ]);
            }

            return $product;
        });
    }

    /**
     * Update an existing product. Regenerates handle if title changed.
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            if (isset($data['title']) && $data['title'] !== $product->title) {
                $data['handle'] = $this->handleGenerator->generate(
                    $data['title'],
                    'products',
                    $product->store_id,
                    $product->id,
                );
            }

            $product->update($data);

            return $product->fresh();
        });
    }

    /**
     * Transition a product's status with validation of allowed transitions.
     *
     * @throws InvalidArgumentException
     */
    public function transitionStatus(Product $product, ProductStatus $newStatus): void
    {
        $current = $product->status;

        if ($current === $newStatus) {
            return;
        }

        match (true) {
            $current === ProductStatus::Draft && $newStatus === ProductStatus::Active => $this->activateProduct($product),
            $current === ProductStatus::Active && $newStatus === ProductStatus::Archived => $product->update(['status' => ProductStatus::Archived]),
            $current === ProductStatus::Active && $newStatus === ProductStatus::Draft => $this->deactivateProduct($product),
            $current === ProductStatus::Archived && $newStatus === ProductStatus::Draft => $product->update(['status' => ProductStatus::Draft]),
            $current === ProductStatus::Archived && $newStatus === ProductStatus::Active => $this->activateProduct($product),
            default => throw new InvalidArgumentException(
                "Invalid status transition from {$current->value} to {$newStatus->value}."
            ),
        };
    }

    /**
     * Delete a product. Only allowed if status is Draft and no order references.
     *
     * @throws InvalidArgumentException
     */
    public function delete(Product $product): void
    {
        if ($product->status !== ProductStatus::Draft) {
            throw new InvalidArgumentException('Only draft products can be deleted.');
        }

        if ($this->hasOrderReferences($product)) {
            throw new InvalidArgumentException('Cannot delete a product with existing order references.');
        }

        DB::transaction(function () use ($product) {
            $product->variants()->each(function ($variant) {
                $variant->inventoryItem()?->delete();
                $variant->optionValues()->detach();
                $variant->delete();
            });

            $product->options()->each(function ($option) {
                $option->values()->delete();
                $option->delete();
            });

            $product->media()->delete();
            $product->delete();
        });
    }

    private function activateProduct(Product $product): void
    {
        $hasPricedVariant = $product->variants()
            ->where('price_amount', '>', 0)
            ->exists();

        if (! $hasPricedVariant) {
            throw new InvalidArgumentException('Cannot activate a product without at least one variant with a price.');
        }

        $product->update([
            'status' => ProductStatus::Active,
            'published_at' => now(),
        ]);
    }

    private function deactivateProduct(Product $product): void
    {
        if ($this->hasOrderReferences($product)) {
            throw new InvalidArgumentException('Cannot revert to draft: product has existing order references.');
        }

        $product->update(['status' => ProductStatus::Draft]);
    }

    private function hasOrderReferences(Product $product): bool
    {
        if (! Schema::hasTable('order_lines')) {
            return false;
        }

        $variantIds = $product->variants()->pluck('id');

        return DB::table('order_lines')
            ->whereIn('variant_id', $variantIds)
            ->exists();
    }
}
