<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data) {
            $handle = $data['handle'] ?? HandleGenerator::generate($data['title'], 'products', $store->id);

            $product = Product::query()->create([
                'store_id' => $store->id,
                'title' => $data['title'],
                'handle' => $handle,
                'description_html' => $data['description_html'] ?? null,
                'status' => $data['status'] ?? ProductStatus::Draft,
                'vendor' => $data['vendor'] ?? null,
                'product_type' => $data['product_type'] ?? null,
                'tags' => $data['tags'] ?? [],
                'published_at' => $data['published_at'] ?? null,
            ]);

            if (! empty($data['options'])) {
                foreach ($data['options'] as $position => $optionData) {
                    $option = $product->options()->create([
                        'name' => $optionData['name'],
                        'position' => $position,
                    ]);

                    foreach ($optionData['values'] as $valuePosition => $value) {
                        $option->values()->create([
                            'value' => $value,
                            'position' => $valuePosition,
                        ]);
                    }
                }

                if (! empty($data['variants'])) {
                    foreach ($data['variants'] as $variantPosition => $variantData) {
                        $variant = $product->variants()->create([
                            'sku' => $variantData['sku'] ?? null,
                            'barcode' => $variantData['barcode'] ?? null,
                            'price_amount' => $variantData['price_amount'] ?? 0,
                            'compare_at_amount' => $variantData['compare_at_amount'] ?? null,
                            'weight_g' => $variantData['weight_g'] ?? null,
                            'position' => $variantPosition,
                            'is_default' => $variantPosition === 0,
                        ]);

                        $variant->inventoryItem()->create([
                            'store_id' => $store->id,
                            'quantity_on_hand' => $variantData['quantity_on_hand'] ?? 0,
                        ]);
                    }
                }
            } else {
                $variant = $product->variants()->create([
                    'price_amount' => $data['price_amount'] ?? 0,
                    'compare_at_amount' => $data['compare_at_amount'] ?? null,
                    'weight_g' => $data['weight_g'] ?? null,
                    'is_default' => true,
                    'position' => 0,
                ]);

                $variant->inventoryItem()->create([
                    'store_id' => $store->id,
                    'quantity_on_hand' => $data['quantity_on_hand'] ?? 0,
                ]);
            }

            return $product->load(['variants.inventoryItem', 'options.values']);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            if (isset($data['title']) && ! isset($data['handle'])) {
                $data['handle'] = HandleGenerator::generate($data['title'], 'products', $product->store_id, $product->id);
            }

            $product->update(array_intersect_key($data, array_flip([
                'title', 'handle', 'description_html', 'status', 'vendor',
                'product_type', 'tags', 'published_at',
            ])));

            return $product->fresh(['variants.inventoryItem', 'options.values']);
        });
    }

    public function transitionStatus(Product $product, ProductStatus $newStatus): void
    {
        if ($product->status === ProductStatus::Draft && $newStatus === ProductStatus::Active) {
            $hasPricedVariant = $product->variants()
                ->where('status', VariantStatus::Active)
                ->where('price_amount', '>', 0)
                ->exists();

            if (! $hasPricedVariant) {
                throw new \InvalidArgumentException('Product must have at least one active variant with a price to be activated.');
            }
        }

        if ($product->status === ProductStatus::Active && $newStatus === ProductStatus::Draft) {
            $hasOrders = DB::table('order_lines')
                ->where('product_id', $product->id)
                ->exists();

            if ($hasOrders) {
                throw new \InvalidArgumentException('Cannot revert an active product with orders back to draft.');
            }
        }

        $product->update(['status' => $newStatus]);
    }

    public function delete(Product $product): void
    {
        if ($product->status !== ProductStatus::Draft) {
            throw new \InvalidArgumentException('Only draft products can be deleted.');
        }

        $hasOrders = DB::table('order_lines')
            ->where('product_id', $product->id)
            ->exists();

        if ($hasOrders) {
            throw new \InvalidArgumentException('Cannot delete a product that has order references.');
        }

        $product->delete();
    }
}
