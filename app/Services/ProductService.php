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

    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data) {
            $handle = $this->handleGenerator->generate(
                $data['title'],
                'products',
                $store->id,
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

            $variant = $product->variants()->create([
                'is_default' => true,
                'position' => 0,
                'price_amount' => $data['price_amount'] ?? 0,
                'currency' => $store->default_currency,
                'status' => 'active',
            ]);

            InventoryItem::query()->create([
                'store_id' => $store->id,
                'variant_id' => $variant->id,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
                'policy' => 'deny',
            ]);

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
        $currentStatus = $product->status;

        if ($currentStatus === $newStatus) {
            return;
        }

        match (true) {
            $newStatus === ProductStatus::Active => $this->validateActivation($product),
            $newStatus === ProductStatus::Draft => $this->validateDraftTransition($product),
            $newStatus === ProductStatus::Archived => null,
        };

        $product->status = $newStatus;

        if ($newStatus === ProductStatus::Active && $product->published_at === null) {
            $product->published_at = now()->toIso8601String();
        }

        $product->save();
    }

    public function delete(Product $product): void
    {
        if ($product->status !== ProductStatus::Draft) {
            throw new InvalidProductTransitionException('Only draft products can be deleted.');
        }

        if ($this->hasOrderReferences($product)) {
            throw new InvalidProductTransitionException('Cannot delete product with order references.');
        }

        $product->delete();
    }

    private function validateActivation(Product $product): void
    {
        if (empty($product->title)) {
            throw new InvalidProductTransitionException('Product must have a title to be activated.');
        }

        $hasPricedVariant = $product->variants()
            ->where('price_amount', '>', 0)
            ->exists();

        if (! $hasPricedVariant) {
            throw new InvalidProductTransitionException('Product must have at least one priced variant to be activated.');
        }
    }

    private function validateDraftTransition(Product $product): void
    {
        if ($this->hasOrderReferences($product)) {
            throw new InvalidProductTransitionException('Cannot revert to draft when order lines reference this product.');
        }
    }

    private function hasOrderReferences(Product $product): bool
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('order_lines')) {
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
