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
        protected HandleGenerator $handleGenerator
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data) {
            $handle = $data['handle'] ?? $this->handleGenerator->generate(
                $data['title'],
                'products',
                $store->id
            );

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

            if (empty($data['options'])) {
                $variant = ProductVariant::query()->create([
                    'product_id' => $product->id,
                    'price_amount' => $data['price_amount'] ?? 0,
                    'currency' => $store->default_currency,
                    'is_default' => true,
                    'position' => 0,
                ]);

                InventoryItem::query()->create([
                    'store_id' => $store->id,
                    'variant_id' => $variant->id,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                ]);
            }

            return $product->fresh(['variants', 'options']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            if (isset($data['title']) && $data['title'] !== $product->title && ! isset($data['handle'])) {
                $data['handle'] = $this->handleGenerator->generate(
                    $data['title'],
                    'products',
                    $product->store_id,
                    $product->id
                );
            }

            $product->update($data);

            return $product->fresh(['variants', 'options']);
        });
    }

    public function transitionStatus(Product $product, ProductStatus $newStatus): void
    {
        $currentStatus = $product->status;

        $this->validateTransition($product, $currentStatus, $newStatus);

        $product->update(['status' => $newStatus]);

        if ($newStatus === ProductStatus::Active && ! $product->published_at) {
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

        $product->delete();
    }

    protected function validateTransition(Product $product, ProductStatus $from, ProductStatus $to): void
    {
        $allowedTransitions = [
            ProductStatus::Draft->value => [ProductStatus::Active, ProductStatus::Archived],
            ProductStatus::Active->value => [ProductStatus::Archived, ProductStatus::Draft],
            ProductStatus::Archived->value => [ProductStatus::Active, ProductStatus::Draft],
        ];

        $allowed = $allowedTransitions[$from->value] ?? [];

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
                    'Product must have at least one variant with a price greater than 0 to be activated.'
                );
            }

            if (empty($product->title)) {
                throw new InvalidProductTransitionException(
                    'Product must have a title to be activated.'
                );
            }
        }

        if (($from === ProductStatus::Active || $from === ProductStatus::Archived) && $to === ProductStatus::Draft) {
            $hasOrderReferences = $this->hasOrderLineReferences($product);

            if ($hasOrderReferences) {
                throw new InvalidProductTransitionException(
                    'Cannot revert to draft: product has existing order references.'
                );
            }
        }
    }

    protected function hasOrderLineReferences(Product $product): bool
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
