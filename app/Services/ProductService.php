<?php

namespace App\Services;

use App\Enums\ProductStatus;
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
     * Create a product with a default variant.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data) {
            $handle = $this->handleGenerator->generate(
                $data['title'],
                'products',
                $store->id
            );

            $product = Product::query()->withoutGlobalScopes()->create([
                'store_id' => $store->id,
                'title' => $data['title'],
                'handle' => $handle,
                'status' => $data['status'] ?? ProductStatus::Draft,
                'description_html' => $data['description_html'] ?? null,
                'vendor' => $data['vendor'] ?? null,
                'product_type' => $data['product_type'] ?? null,
                'tags' => $data['tags'] ?? [],
                'published_at' => $data['published_at'] ?? null,
            ]);

            $product->variants()->create([
                'sku' => $data['sku'] ?? null,
                'price_amount' => $data['price_amount'] ?? 0,
                'is_default' => true,
                'position' => 0,
            ]);

            return $product;
        });
    }

    /**
     * Update a product.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            if (isset($data['title']) && $data['title'] !== $product->title) {
                $data['handle'] = $this->handleGenerator->generate(
                    $data['title'],
                    'products',
                    $product->store_id,
                    $product->id
                );
            }

            $product->update($data);

            return $product->refresh();
        });
    }

    /**
     * Transition product status following state machine rules.
     *
     * Allowed transitions:
     * - Draft -> Active
     * - Active -> Archived
     * - Archived -> Active
     * - Active -> Draft
     *
     * @throws \InvalidArgumentException
     */
    public function transitionStatus(Product $product, ProductStatus $newStatus): void
    {
        $allowedTransitions = [
            ProductStatus::Draft->value => [ProductStatus::Active],
            ProductStatus::Active->value => [ProductStatus::Archived, ProductStatus::Draft],
            ProductStatus::Archived->value => [ProductStatus::Active],
        ];

        $allowed = $allowedTransitions[$product->status->value] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$product->status->value} to {$newStatus->value}."
            );
        }

        $product->update([
            'status' => $newStatus,
            'published_at' => $newStatus === ProductStatus::Active ? ($product->published_at ?? now()) : $product->published_at,
        ]);
    }

    /**
     * Hard delete a product only if it is draft and has no order references.
     *
     * @throws \LogicException
     */
    public function delete(Product $product): void
    {
        if ($product->status !== ProductStatus::Draft) {
            throw new \LogicException('Only draft products can be deleted.');
        }

        $product->delete();
    }
}
