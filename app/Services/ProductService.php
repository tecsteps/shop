<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductService
{
    public function __construct(
        private HandleGenerator $handleGenerator
    ) {}

    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data) {
            $handle = $data['handle'] ?? $this->handleGenerator->generate(
                $data['title'],
                'products',
                $store->id
            );

            $product = Product::create([
                'store_id' => $store->id,
                'title' => $data['title'],
                'handle' => $handle,
                'status' => ProductStatus::Draft,
                'description_html' => $data['description_html'] ?? null,
                'vendor' => $data['vendor'] ?? null,
                'product_type' => $data['product_type'] ?? null,
                'tags' => $data['tags'] ?? [],
            ]);

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'price_amount' => $data['price_amount'] ?? 0,
                'currency' => $store->default_currency,
                'is_default' => true,
                'position' => 0,
            ]);

            InventoryItem::create([
                'store_id' => $store->id,
                'variant_id' => $variant->id,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
            ]);

            return $product->load('variants.inventoryItem');
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            if (isset($data['title']) && ! isset($data['handle'])) {
                $data['handle'] = $this->handleGenerator->generate(
                    $data['title'],
                    'products',
                    $product->store_id,
                    $product->id
                );
            }

            $product->update($data);

            return $product->fresh();
        });
    }

    public function transitionStatus(Product $product, ProductStatus $newStatus): void
    {
        $currentStatus = $product->status;

        $this->validateTransition($product, $currentStatus, $newStatus);

        $product->update(['status' => $newStatus]);

        if ($newStatus === ProductStatus::Active && $product->published_at === null) {
            $product->update(['published_at' => now()]);
        }
    }

    public function delete(Product $product): void
    {
        if ($product->status !== ProductStatus::Draft) {
            throw new InvalidProductTransitionException(
                'Only draft products can be deleted.'
            );
        }

        if ($this->hasOrderReferences($product)) {
            throw new InvalidProductTransitionException(
                'Cannot delete product with existing order references.'
            );
        }

        $product->delete();
    }

    private function validateTransition(Product $product, ProductStatus $from, ProductStatus $to): void
    {
        $allowed = match ($from) {
            ProductStatus::Draft => [ProductStatus::Active, ProductStatus::Archived],
            ProductStatus::Active => [ProductStatus::Archived, ProductStatus::Draft],
            ProductStatus::Archived => [ProductStatus::Active, ProductStatus::Draft],
        };

        if (! in_array($to, $allowed)) {
            throw new InvalidProductTransitionException(
                "Cannot transition from {$from->value} to {$to->value}."
            );
        }

        if ($to === ProductStatus::Active) {
            $hasVariantWithPrice = $product->variants()
                ->where('price_amount', '>', 0)
                ->exists();

            if (! $hasVariantWithPrice) {
                throw new InvalidProductTransitionException(
                    'Product must have at least one variant with a price greater than zero to be activated.'
                );
            }

            if (empty($product->title)) {
                throw new InvalidProductTransitionException(
                    'Product title must not be empty to be activated.'
                );
            }
        }

        if ($to === ProductStatus::Draft && in_array($from, [ProductStatus::Active, ProductStatus::Archived])) {
            if ($this->hasOrderReferences($product)) {
                throw new InvalidProductTransitionException(
                    'Cannot revert to draft because order lines reference this product.'
                );
            }
        }
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
