<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\HandleGenerator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProductService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(int $storeId, array $attributes): Product
    {
        return DB::transaction(function () use ($storeId, $attributes): Product {
            $title = (string) ($attributes['title'] ?? '');

            if (trim($title) === '') {
                throw new \InvalidArgumentException('Product title is required.');
            }

            $handle = $attributes['handle'] ?? null;
            $handle = HandleGenerator::unique(
                Product::class,
                $storeId,
                $handle !== null && $handle !== '' ? $handle : $title,
            );

            $product = new Product([
                'title' => $title,
                'handle' => $handle,
                'status' => ProductStatus::Draft->value,
                'description_html' => $attributes['description_html'] ?? null,
                'vendor' => $attributes['vendor'] ?? null,
                'product_type' => $attributes['product_type'] ?? null,
                'tags' => $attributes['tags'] ?? [],
            ]);
            $product->store_id = $storeId;
            $product->save();

            $this->ensureDefaultVariant($product, $attributes);

            return $product->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Product $product, array $attributes): Product
    {
        return DB::transaction(function () use ($product, $attributes): Product {
            if (array_key_exists('title', $attributes) && trim((string) $attributes['title']) === '') {
                throw new \InvalidArgumentException('Product title cannot be empty.');
            }

            if (array_key_exists('handle', $attributes) && $attributes['handle'] !== null && $attributes['handle'] !== '') {
                $attributes['handle'] = HandleGenerator::unique(
                    Product::class,
                    (int) $product->store_id,
                    (string) $attributes['handle'],
                    $product->getKey(),
                );
            } else {
                unset($attributes['handle']);
            }

            $product->fill($attributes);
            $product->save();

            return $product->refresh();
        });
    }

    public function transitionStatus(Product $product, ProductStatus $newStatus): Product
    {
        return DB::transaction(function () use ($product, $newStatus): Product {
            $current = $product->status instanceof ProductStatus
                ? $product->status
                : ProductStatus::from((string) $product->status);

            if ($current === $newStatus) {
                return $product;
            }

            $this->assertValidTransition($product, $current, $newStatus);

            $product->status = $newStatus;

            if ($newStatus === ProductStatus::Active && $product->published_at === null) {
                $product->published_at = now();
            }

            $product->save();

            return $product->refresh();
        });
    }

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $current = $product->status instanceof ProductStatus
                ? $product->status
                : ProductStatus::from((string) $product->status);

            if ($current !== ProductStatus::Draft) {
                throw new RuntimeException('Only draft products may be deleted. Archive instead.');
            }

            if ($this->hasOrderReferences($product)) {
                throw new RuntimeException('Product with order history may not be deleted.');
            }

            $product->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function ensureDefaultVariant(Product $product, array $attributes): void
    {
        $variant = new ProductVariant([
            'sku' => $attributes['sku'] ?? null,
            'price_amount' => (int) ($attributes['price_amount'] ?? 0),
            'compare_at_amount' => $attributes['compare_at_amount'] ?? null,
            'currency' => $attributes['currency'] ?? 'USD',
            'weight_g' => $attributes['weight_g'] ?? null,
            'requires_shipping' => (int) ($attributes['requires_shipping'] ?? 1),
            'is_default' => 1,
            'position' => 0,
            'status' => VariantStatus::Active->value,
        ]);
        $variant->product_id = $product->getKey();
        $variant->save();

        $inventory = new InventoryItem([
            'quantity_on_hand' => (int) ($attributes['quantity_on_hand'] ?? 0),
            'quantity_reserved' => 0,
            'policy' => InventoryPolicy::Deny->value,
        ]);
        $inventory->store_id = $product->store_id;
        $inventory->variant_id = $variant->getKey();
        $inventory->save();
    }

    protected function assertValidTransition(Product $product, ProductStatus $from, ProductStatus $to): void
    {
        $valid = match ($from) {
            ProductStatus::Draft => in_array($to, [ProductStatus::Active, ProductStatus::Archived], true),
            ProductStatus::Active => in_array($to, [ProductStatus::Archived, ProductStatus::Draft], true),
            ProductStatus::Archived => in_array($to, [ProductStatus::Active, ProductStatus::Draft], true),
        };

        if (! $valid) {
            throw new InvalidProductTransitionException($from, $to);
        }

        if ($to === ProductStatus::Active) {
            if (trim((string) $product->title) === '') {
                throw new InvalidProductTransitionException($from, $to, 'Cannot activate product without a title.');
            }

            $hasPricedVariant = $product->variants()
                ->where('status', VariantStatus::Active->value)
                ->where('price_amount', '>', 0)
                ->exists();

            if (! $hasPricedVariant) {
                throw new InvalidProductTransitionException($from, $to, 'Cannot activate product without at least one priced active variant.');
            }
        }

        if ($to === ProductStatus::Draft && $this->hasOrderReferences($product)) {
            throw new InvalidProductTransitionException($from, $to, 'Cannot revert product to draft once sold.');
        }
    }

    protected function hasOrderReferences(Product $product): bool
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
