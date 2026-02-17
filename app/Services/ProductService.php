<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Store;
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
                (string) $data['title'],
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
                'price_amount' => $data['price_amount'] ?? 0,
                'is_default' => true,
                'position' => 0,
                'status' => 'active',
            ]);

            InventoryItem::query()->create([
                'store_id' => $store->id,
                'variant_id' => $variant->id,
            ]);

            return $product;
        });
    }

    public function update(Product $product, array $data): Product
    {
        if (isset($data['title']) && ! isset($data['handle'])) {
            $data['handle'] = $this->handleGenerator->generate(
                (string) $data['title'],
                'products',
                $product->store_id,
                $product->id,
            );
        }

        $product->update($data);

        return $product->refresh();
    }

    public function transitionStatus(Product $product, ProductStatus $newStatus): void
    {
        $currentStatus = $product->status;

        $this->validateTransition($product, $currentStatus, $newStatus);

        $product->status = $newStatus;

        if ($newStatus === ProductStatus::Active && $product->published_at === null) {
            $product->published_at = now();
        }

        $product->save();
    }

    public function delete(Product $product): void
    {
        if ($product->status !== ProductStatus::Draft) {
            throw new InvalidProductTransitionException('Only draft products can be deleted.');
        }

        if ($this->hasOrderReferences($product)) {
            throw new InvalidProductTransitionException('Cannot delete product with existing order references.');
        }

        $product->delete();
    }

    private function validateTransition(Product $product, ProductStatus $from, ProductStatus $to): void
    {
        $allowedTransitions = [
            'draft' => ['active', 'archived'],
            'active' => ['archived', 'draft'],
            'archived' => ['active', 'draft'],
        ];

        if (! in_array($to->value, $allowedTransitions[$from->value] ?? [], true)) {
            throw new InvalidProductTransitionException(
                "Cannot transition from {$from->value} to {$to->value}."
            );
        }

        if ($to === ProductStatus::Active) {
            $hasPricedVariant = $product->variants()
                ->where('price_amount', '>', 0)
                ->where('status', 'active')
                ->exists();

            if (! $hasPricedVariant) {
                throw new InvalidProductTransitionException(
                    'Cannot activate product without a variant with price > 0.'
                );
            }

            if (empty($product->title)) {
                throw new InvalidProductTransitionException(
                    'Cannot activate product without a title.'
                );
            }
        }

        if ($to === ProductStatus::Draft && $this->hasOrderReferences($product)) {
            throw new InvalidProductTransitionException(
                'Cannot revert to draft when order references exist.'
            );
        }
    }

    private function hasOrderReferences(Product $product): bool
    {
        if (! Schema::hasTable('order_lines')) {
            return false;
        }

        return DB::table('order_lines')
            ->where('product_id', $product->id)
            ->exists();
    }
}
