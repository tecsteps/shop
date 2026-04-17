<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\InvalidProductTransitionException;
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

    /**
     * Create a new product with a default variant and inventory item.
     */
    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data) {
            $handle = $data['handle'] ?? $this->handleGenerator->generate(
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
                'sku' => $data['sku'] ?? null,
                'price_amount' => $data['price_amount'] ?? 0,
                'currency' => $store->default_currency,
                'is_default' => true,
                'position' => 0,
                'status' => VariantStatus::Active,
            ]);

            $variant->inventoryItem()->create([
                'store_id' => $store->id,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
            ]);

            return $product->load('variants.inventoryItem');
        });
    }

    /**
     * Update an existing product.
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $updateData = [];

            if (isset($data['title'])) {
                $updateData['title'] = $data['title'];
            }

            if (isset($data['handle'])) {
                $updateData['handle'] = $data['handle'];
            } elseif (isset($data['title'])) {
                $updateData['handle'] = $this->handleGenerator->generate(
                    $data['title'],
                    'products',
                    $product->store_id,
                    $product->id,
                );
            }

            if (array_key_exists('description_html', $data)) {
                $updateData['description_html'] = $data['description_html'];
            }

            if (array_key_exists('vendor', $data)) {
                $updateData['vendor'] = $data['vendor'];
            }

            if (array_key_exists('product_type', $data)) {
                $updateData['product_type'] = $data['product_type'];
            }

            if (array_key_exists('tags', $data)) {
                $updateData['tags'] = $data['tags'];
            }

            $product->update($updateData);

            return $product->fresh();
        });
    }

    /**
     * Transition a product's status according to the state machine rules.
     *
     * @throws InvalidProductTransitionException
     */
    public function transitionStatus(Product $product, ProductStatus $newStatus): void
    {
        $currentStatus = $product->status;

        if ($currentStatus === $newStatus) {
            return;
        }

        $this->validateTransition($product, $currentStatus, $newStatus);

        DB::transaction(function () use ($product, $newStatus) {
            $product->status = $newStatus;

            if ($newStatus === ProductStatus::Active && $product->published_at === null) {
                $product->published_at = now();
            }

            $product->save();
        });
    }

    /**
     * Delete a product (only allowed for drafts with no order references).
     *
     * @throws InvalidProductTransitionException
     */
    public function delete(Product $product): void
    {
        if ($product->status !== ProductStatus::Draft) {
            throw new InvalidProductTransitionException(
                $product->status,
                ProductStatus::Draft,
                'Only draft products can be deleted'
            );
        }

        if ($this->hasOrderReferences($product)) {
            throw new InvalidProductTransitionException(
                $product->status,
                ProductStatus::Draft,
                'Cannot delete product with existing order references'
            );
        }

        $product->delete();
    }

    /**
     * Validate a status transition according to the state machine rules.
     *
     * @throws InvalidProductTransitionException
     */
    private function validateTransition(Product $product, ProductStatus $from, ProductStatus $to): void
    {
        $allowedTransitions = [
            ProductStatus::Draft->value => [ProductStatus::Active->value, ProductStatus::Archived->value],
            ProductStatus::Active->value => [ProductStatus::Archived->value, ProductStatus::Draft->value],
            ProductStatus::Archived->value => [ProductStatus::Active->value, ProductStatus::Draft->value],
        ];

        if (! in_array($to->value, $allowedTransitions[$from->value] ?? [])) {
            throw new InvalidProductTransitionException($from, $to, 'Transition not allowed');
        }

        if ($to === ProductStatus::Active) {
            $this->validateActivationPreconditions($product, $from);
        }

        if ($to === ProductStatus::Draft && in_array($from, [ProductStatus::Active, ProductStatus::Archived])) {
            if ($this->hasOrderReferences($product)) {
                throw new InvalidProductTransitionException(
                    $from,
                    $to,
                    'Cannot revert to draft when order lines reference this product'
                );
            }
        }
    }

    /**
     * Validate that the product meets the preconditions for activation.
     *
     * @throws InvalidProductTransitionException
     */
    private function validateActivationPreconditions(Product $product, ProductStatus $from): void
    {
        $hasVariantWithPrice = $product->variants()
            ->where('price_amount', '>', 0)
            ->exists();

        if (! $hasVariantWithPrice) {
            throw new InvalidProductTransitionException(
                $from,
                ProductStatus::Active,
                'Product must have at least one variant with a price greater than zero'
            );
        }
    }

    /**
     * Check if any order lines reference variants of this product.
     */
    private function hasOrderReferences(Product $product): bool
    {
        if (! Schema::hasTable('order_lines')) {
            return false;
        }

        return DB::table('order_lines')
            ->whereIn('variant_id', $product->variants()->select('id'))
            ->exists();
    }
}
