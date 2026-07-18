<?php

namespace App\Services;

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
    public function __construct(
        private HandleGenerator $handleGenerator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data): Product {
            $title = (string) ($data['title'] ?? 'Untitled');
            $handle = (string) ($data['handle'] ?? $this->handleGenerator->generate($title, 'products', $store->id));

            $product = Product::query()->create([
                'store_id' => $store->id,
                'title' => $title,
                'handle' => $handle,
                'status' => $data['status'] ?? ProductStatus::Draft,
                'description_html' => $data['description_html'] ?? null,
                'vendor' => $data['vendor'] ?? null,
                'product_type' => $data['product_type'] ?? null,
                'tags' => $data['tags'] ?? [],
                'published_at' => ($data['status'] ?? null) === ProductStatus::Active ? now() : null,
            ]);

            $variant = ProductVariant::query()->create([
                'product_id' => $product->id,
                'sku' => $data['sku'] ?? null,
                'price_amount' => (int) ($data['price_amount'] ?? 0),
                'currency' => $store->default_currency,
                'is_default' => true,
                'position' => 0,
                'status' => VariantStatus::Active,
            ]);

            InventoryItem::query()->create([
                'store_id' => $store->id,
                'variant_id' => $variant->id,
                'quantity_on_hand' => (int) ($data['quantity_on_hand'] ?? 0),
                'quantity_reserved' => 0,
            ]);

            return $product->fresh(['variants.inventoryItem']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        if (isset($data['title']) && ! isset($data['handle'])) {
            $data['handle'] = $this->handleGenerator->generate(
                (string) $data['title'],
                'products',
                (int) $product->store_id,
                $product->id,
            );
        }

        $product->fill($data);
        $product->save();

        return $product->fresh();
    }

    public function transitionStatus(Product $product, ProductStatus $newStatus): void
    {
        $current = $product->status;

        if ($current === $newStatus) {
            return;
        }

        if ($newStatus === ProductStatus::Active) {
            if (trim($product->title) === '') {
                throw new InvalidProductTransitionException('Product title is required to publish.');
            }

            $hasPricedVariant = $product->variants()
                ->where('price_amount', '>', 0)
                ->exists();

            if (! $hasPricedVariant) {
                throw new InvalidProductTransitionException('A variant with price greater than zero is required to publish.');
            }

            if ($product->published_at === null) {
                $product->published_at = now();
            }
        }

        if ($newStatus === ProductStatus::Draft && in_array($current, [ProductStatus::Active, ProductStatus::Archived], true)) {
            throw new InvalidProductTransitionException('Cannot revert to draft when product has been published or archived.');
        }

        $product->status = $newStatus;
        $product->save();
    }

    public function delete(Product $product): void
    {
        if ($product->status !== ProductStatus::Draft) {
            throw new InvalidProductTransitionException('Only draft products can be deleted.');
        }

        $product->delete();
    }
}
