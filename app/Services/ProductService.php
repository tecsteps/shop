<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ProductService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data): Product {
            $data['store_id'] = $store->id;
            $data['handle'] = $data['handle'] ?? HandleGenerator::generate($data['title'], 'products', $store->id);
            $data['status'] = $data['status'] ?? ProductStatus::Draft->value;

            $product = Product::create($data);

            if (empty($data['variants'])) {
                $product->variants()->create([
                    'price_amount' => $data['price_amount'] ?? 0,
                    'currency' => $store->default_currency,
                    'is_default' => true,
                    'status' => 'active',
                ]);
            }

            try {
                app(WebhookService::class)->dispatch($store, 'products/create', $product->toArray());
            } catch (\Throwable $e) {
                Log::warning('Webhook dispatch failed for products/create', ['error' => $e->getMessage()]);
            }

            return $product;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            if (isset($data['title']) && ! isset($data['handle'])) {
                $data['handle'] = HandleGenerator::generate(
                    $data['title'],
                    'products',
                    $product->store_id,
                    $product->id,
                );
            }

            $product->update($data);

            $freshProduct = $product->fresh();

            try {
                $store = Store::find($freshProduct->store_id);
                app(WebhookService::class)->dispatch($store, 'products/update', $freshProduct->toArray());
            } catch (\Throwable $e) {
                Log::warning('Webhook dispatch failed for products/update', ['error' => $e->getMessage()]);
            }

            return $freshProduct;
        });
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
            throw new InvalidArgumentException('Only draft products can be deleted.');
        }

        if ($this->hasOrderReferences($product)) {
            throw new InvalidArgumentException('Cannot delete: product has order references.');
        }

        $productData = $product->toArray();
        $storeId = $product->store_id;

        $product->delete();

        try {
            $store = Store::find($storeId);
            app(WebhookService::class)->dispatch($store, 'products/delete', $productData);
        } catch (\Throwable $e) {
            Log::warning('Webhook dispatch failed for products/delete', ['error' => $e->getMessage()]);
        }
    }

    private function validateTransition(Product $product, ProductStatus $from, ProductStatus $to): void
    {
        if ($to === ProductStatus::Active) {
            if (empty($product->title)) {
                throw new InvalidArgumentException('Product title is required for activation.');
            }

            $hasVariantWithPrice = $product->variants()->where('price_amount', '>', 0)->exists();

            if (! $hasVariantWithPrice) {
                throw new InvalidArgumentException('At least one variant with price > 0 is required.');
            }
        }

        if ($to === ProductStatus::Draft && in_array($from, [ProductStatus::Active, ProductStatus::Archived])) {
            if ($this->hasOrderReferences($product)) {
                throw new InvalidArgumentException('Cannot revert to draft: product has order references.');
            }
        }
    }

    private function hasOrderReferences(Product $product): bool
    {
        return OrderLine::whereIn(
            'variant_id',
            $product->variants()->pluck('id'),
        )->exists();
    }
}
