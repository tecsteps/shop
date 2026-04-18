<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Support\Facades\DB;

class ProductService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data): Product {
            $handle = $data['handle'] ?? null;
            $title = $data['title'];

            if (empty($handle)) {
                $handle = HandleGenerator::generate($title, 'products', $store->id);
            }

            $product = Product::query()->create([
                'store_id' => $store->id,
                'title' => $title,
                'handle' => $handle,
                'status' => $data['status'] ?? ProductStatus::Draft,
                'description_html' => $data['description_html'] ?? null,
                'vendor' => $data['vendor'] ?? null,
                'product_type' => $data['product_type'] ?? null,
                'tags' => $data['tags'] ?? [],
            ]);

            $variant = ProductVariant::query()->create([
                'product_id' => $product->id,
                'price_amount' => $data['price_amount'] ?? 0,
                'currency' => $data['currency'] ?? $store->default_currency,
                'is_default' => true,
                'position' => 0,
                'status' => VariantStatus::Active,
            ]);

            InventoryItem::query()->create([
                'store_id' => $store->id,
                'variant_id' => $variant->id,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
                'policy' => InventoryPolicy::Deny,
            ]);

            return $product->fresh(['variants']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            if (isset($data['title']) && $data['title'] !== $product->title && ! isset($data['handle'])) {
                $data['handle'] = HandleGenerator::generate($data['title'], 'products', $product->store_id, $product->id);
            }

            $product->update($data);

            return $product->fresh();
        });
    }

    public function transitionStatus(Product $product, ProductStatus $newStatus): void
    {
        $current = $product->status;

        if ($current === $newStatus) {
            return;
        }

        if ($newStatus === ProductStatus::Active) {
            if (empty($product->title)) {
                throw new InvalidProductTransitionException('Product title is required before activating.');
            }

            $hasPricedVariant = $product->variants()->where('price_amount', '>', 0)->exists();

            if (! $hasPricedVariant) {
                throw new InvalidProductTransitionException('Product requires at least one variant with price > 0 before activating.');
            }
        }

        if ($newStatus === ProductStatus::Draft) {
            if ($this->productHasOrderReferences($product)) {
                throw new InvalidProductTransitionException('Cannot revert to draft: order lines reference this product.');
            }
        }

        $attributes = ['status' => $newStatus];

        if ($newStatus === ProductStatus::Active && $product->published_at === null) {
            $attributes['published_at'] = now();
        }

        $product->update($attributes);
    }

    public function delete(Product $product): void
    {
        if ($product->status !== ProductStatus::Draft) {
            throw new InvalidProductTransitionException('Only draft products may be deleted.');
        }

        if ($this->productHasOrderReferences($product)) {
            throw new InvalidProductTransitionException('Cannot delete product with order line references.');
        }

        $product->delete();
    }

    protected function productHasOrderReferences(Product $product): bool
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('order_lines')) {
            return false;
        }

        $variantIds = $product->variants()->pluck('id');

        return DB::table('order_lines')->whereIn('variant_id', $variantIds)->exists();
    }
}
