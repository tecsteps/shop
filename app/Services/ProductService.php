<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductService
{
    public function __construct(
        private HandleGenerator $handleGenerator,
    ) {}

    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data) {
            $handle = $this->handleGenerator->generate(
                $data['title'],
                'products',
                $store->id,
            );

            $product = Product::withoutGlobalScopes()->create([
                'store_id' => $store->id,
                'title' => $data['title'],
                'handle' => $handle,
                'status' => $data['status'] ?? ProductStatus::Draft,
                'description_html' => $data['description_html'] ?? null,
                'vendor' => $data['vendor'] ?? null,
                'product_type' => $data['product_type'] ?? null,
                'tags' => $data['tags'] ?? [],
            ]);

            if (empty($data['options'])) {
                $variant = $product->variants()->create([
                    'price_amount' => $data['price_amount'] ?? 0,
                    'currency' => $store->default_currency ?? 'EUR',
                    'is_default' => true,
                    'position' => 0,
                ]);

                InventoryItem::withoutGlobalScopes()->create([
                    'store_id' => $store->id,
                    'variant_id' => $variant->id,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'policy' => InventoryPolicy::Deny,
                ]);
            }

            return $product;
        });
    }

    public function update(Product $product, array $data): Product
    {
        if (isset($data['title']) && ! isset($data['handle'])) {
            $data['handle'] = $this->handleGenerator->generate(
                $data['title'],
                'products',
                $product->store_id,
                $product->id,
            );
        }

        $product->update($data);

        return $product->fresh();
    }

    public function transitionStatus(Product $product, ProductStatus $newStatus): void
    {
        $current = $product->status;

        $this->validateTransition($product, $current, $newStatus);

        $product->status = $newStatus;

        if ($newStatus === ProductStatus::Active && ! $product->published_at) {
            $product->published_at = now();
        }

        $product->save();
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
                'Cannot delete product with order references.'
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
            $hasPricedVariant = $product->variants()
                ->where('price_amount', '>', 0)
                ->exists();

            if (! $hasPricedVariant) {
                throw new InvalidProductTransitionException(
                    'Product must have at least one variant with a price greater than zero.'
                );
            }

            if (empty($product->title)) {
                throw new InvalidProductTransitionException(
                    'Product title must not be empty.'
                );
            }
        }

        if ($to === ProductStatus::Draft && in_array($from, [ProductStatus::Active, ProductStatus::Archived])) {
            if ($this->hasOrderReferences($product)) {
                throw new InvalidProductTransitionException(
                    'Cannot revert to draft: product has order references.'
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

        if ($variantIds->isEmpty()) {
            return false;
        }

        return DB::table('order_lines')
            ->whereIn('variant_id', $variantIds)
            ->exists();
    }
}
