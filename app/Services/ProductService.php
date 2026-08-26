<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Events\ProductStatusChanged;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        private readonly HandleGenerator $handleGenerator,
        private readonly VariantMatrixService $variantMatrix,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data) {
            $product = Product::create([
                'store_id' => $store->id,
                'title' => $data['title'],
                'handle' => $data['handle'] ?? $this->handleGenerator->generate($data['title'], 'products', $store->id),
                'status' => $data['status'] ?? 'draft',
                'description_html' => isset($data['description_html']) ? app(\App\Actions\SanitizeHtml::class)->sanitize($data['description_html']) : null,
                'vendor' => $data['vendor'] ?? null,
                'product_type' => $data['product_type'] ?? null,
                'tags' => $data['tags'] ?? [],
            ]);

            $this->ensureDefaultVariant($product, $store, $data);

            return $product;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        DB::transaction(function () use ($product, $data) {
            if (array_key_exists('title', $data) && ! array_key_exists('handle', $data)) {
                $data['handle'] = $this->handleGenerator->generate($data['title'], 'products', $product->store_id, $product->id);
            }

            if (array_key_exists('description_html', $data) && $data['description_html'] !== null) {
                $data['description_html'] = app(\App\Actions\SanitizeHtml::class)->sanitize($data['description_html']);
            }

            $product->update($data);
        });

        return $product->fresh();
    }

    public function transitionStatus(Product $product, ProductStatus $newStatus): void
    {
        DB::transaction(function () use ($product, $newStatus) {
            $current = ProductStatus::from($product->status);

            if ($current === $newStatus) {
                return;
            }

            $this->guardTransition($product, $current, $newStatus);

            $updates = ['status' => $newStatus->value];

            if ($newStatus === ProductStatus::Active && $product->published_at === null) {
                $updates['published_at'] = now();
            }

            $product->update($updates);

            ProductStatusChanged::dispatch($product);
        });
    }

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product) {
            if ($product->status !== ProductStatus::Draft->value) {
                throw new InvalidProductTransitionException('Only draft products can be deleted.');
            }

            if ($this->hasOrderReferences($product)) {
                throw new InvalidProductTransitionException('Products with order references cannot be deleted.');
            }

            $product->delete();
        });
    }

    private function guardTransition(Product $product, ProductStatus $current, ProductStatus $new): void
    {
        $toActive = $new === ProductStatus::Active;
        $toDraft = $new === ProductStatus::Draft;

        if ($toActive) {
            $hasTitle = trim((string) $product->title) !== '';
            $hasPricedVariant = $product->variants()->where('price_amount', '>', 0)->exists();

            if (! $hasTitle || ! $hasPricedVariant) {
                throw new InvalidProductTransitionException('Activation requires a title and a priced variant.');
            }
        }

        if ($toDraft && $this->hasOrderReferences($product)) {
            throw new InvalidProductTransitionException('Cannot revert to draft when order lines reference this product.');
        }
    }

    private function hasOrderReferences(Product $product): bool
    {
        return OrderLine::whereIn('variant_id', $product->variants()->pluck('id'))->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensureDefaultVariant(Product $product, Store $store, array $data): void
    {
        if ($product->variants()->exists()) {
            return;
        }

        $variant = $product->variants()->create([
            'sku' => $data['sku'] ?? null,
            'price_amount' => $data['price_amount'] ?? 0,
            'compare_at_amount' => $data['compare_at_amount'] ?? null,
            'currency' => $store->default_currency,
            'weight_g' => $data['weight_g'] ?? null,
            'requires_shipping' => $data['requires_shipping'] ?? true,
            'is_default' => true,
            'position' => 0,
            'status' => 'active',
        ]);

        $variant->inventoryItem()->create([
            'store_id' => $store->id,
            'quantity_on_hand' => $data['quantity_on_hand'] ?? 0,
            'quantity_reserved' => 0,
            'policy' => $data['inventory_policy'] ?? 'deny',
        ]);
    }
}
