<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Support\Facades\DB;
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

            return $product->fresh();
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

        $product->delete();
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
