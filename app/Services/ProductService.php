<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Events\ProductStatusChanged;
use App\Exceptions\InvalidProductTransitionException;
use App\Exceptions\ProductDeletionException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Support\HandleGenerator;
use App\Support\OrderReferenceChecker;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function __construct(
        private HandleGenerator $handleGenerator,
        private VariantMatrixService $variantMatrixService,
        private OrderReferenceChecker $orderReferences,
    ) {}

    /**
     * Create a product with optional nested options and values. Variants are
     * built from the option matrix; products without options receive an
     * auto-created default variant. Every variant gets an inventory item.
     *
     * @param array{
     *     title: string,
     *     handle?: string,
     *     status?: \App\Enums\ProductStatus|string,
     *     description_html?: string|null,
     *     vendor?: string|null,
     *     product_type?: string|null,
     *     tags?: list<string>,
     *     price_amount?: int,
     *     options?: list<array{name: string, values: list<string>}>
     * } $data
     */
    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data): Product {
            $product = new Product([
                'title' => $data['title'],
                'handle' => $data['handle']
                    ?? $this->handleGenerator->generate($data['title'], 'products', $store->getKey()),
                'status' => $data['status'] ?? ProductStatus::Draft,
                'description_html' => $data['description_html'] ?? null,
                'vendor' => $data['vendor'] ?? null,
                'product_type' => $data['product_type'] ?? null,
                'tags' => $data['tags'] ?? [],
            ]);
            $product->store_id = $store->getKey();

            if ($product->status === ProductStatus::Active) {
                $product->published_at = now();
            }

            $product->save();

            foreach ($data['options'] ?? [] as $optionPosition => $option) {
                $productOption = $product->options()->create([
                    'name' => $option['name'],
                    'position' => $optionPosition,
                ]);

                foreach ($option['values'] as $valuePosition => $value) {
                    $productOption->values()->create([
                        'value' => $value,
                        'position' => $valuePosition,
                    ]);
                }
            }

            $this->variantMatrixService->rebuildMatrix($product);

            if (isset($data['price_amount'])) {
                $product->variants()->update(['price_amount' => $data['price_amount']]);
            }

            return $product->load(['options.values', 'variants.inventoryItem']);
        });
    }

    /**
     * Update product attributes. A supplied handle is re-validated for
     * store-scoped uniqueness; the existing handle is kept otherwise.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        if (array_key_exists('handle', $data)) {
            $data['handle'] = $this->handleGenerator->generate(
                $data['handle'] ?? $data['title'] ?? $product->title,
                'products',
                $product->store_id,
                $product->getKey(),
            );
        }

        $product->fill($data);
        $product->save();

        return $product;
    }

    /**
     * Transition the product through its status state machine.
     *
     * @throws InvalidProductTransitionException
     */
    public function transitionStatus(Product $product, ProductStatus $newStatus): void
    {
        $currentStatus = $product->status;

        if ($currentStatus === $newStatus) {
            return;
        }

        if ($newStatus === ProductStatus::Active) {
            $this->assertCanActivate($product);
        }

        if ($newStatus === ProductStatus::Draft && $this->orderReferences->productHasOrderReferences($product)) {
            throw InvalidProductTransitionException::between(
                $currentStatus,
                $newStatus,
                'order lines reference this product.',
            );
        }

        $product->status = $newStatus;

        if ($newStatus === ProductStatus::Active && $product->published_at === null) {
            $product->published_at = now();
        }

        $product->save();

        event(new ProductStatusChanged($product, $currentStatus, $newStatus));
    }

    /**
     * Hard delete a product. Only draft products without order references may
     * be deleted; anything else must be archived to preserve order history.
     *
     * @throws ProductDeletionException
     */
    public function delete(Product $product): void
    {
        if ($product->status !== ProductStatus::Draft) {
            throw new ProductDeletionException('Only draft products may be deleted. Archive the product instead.');
        }

        if ($this->orderReferences->productHasOrderReferences($product)) {
            throw new ProductDeletionException('Products referenced by orders cannot be deleted. Archive the product instead.');
        }

        $product->delete();
    }

    /**
     * Create a single variant with its inventory item, enforcing store-scoped
     * SKU uniqueness (null or empty SKUs are exempt).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function createVariant(Product $product, array $data): ProductVariant
    {
        $this->assertSkuIsUnique($product, $data['sku'] ?? null);

        return DB::transaction(function () use ($product, $data): ProductVariant {
            $variant = $product->variants()->create($data + [
                'currency' => $product->store->default_currency,
                'position' => (int) $product->variants()->max('position') + ($product->variants()->exists() ? 1 : 0),
            ]);

            $variant->inventoryItem()->create([
                'store_id' => $product->store_id,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
                'policy' => InventoryPolicy::Deny,
            ]);

            return $variant;
        });
    }

    /**
     * @throws InvalidProductTransitionException
     */
    private function assertCanActivate(Product $product): void
    {
        if (trim($product->title) === '') {
            throw InvalidProductTransitionException::between(
                $product->status,
                ProductStatus::Active,
                'the product title must not be empty.',
            );
        }

        if (! $product->variants()->where('price_amount', '>', 0)->exists()) {
            throw InvalidProductTransitionException::between(
                $product->status,
                ProductStatus::Active,
                'at least one variant with a price greater than zero is required.',
            );
        }
    }

    /**
     * @throws ValidationException
     */
    private function assertSkuIsUnique(Product $product, ?string $sku, ?int $excludeVariantId = null): void
    {
        if ($sku === null || $sku === '') {
            return;
        }

        $exists = ProductVariant::query()
            ->where('sku', $sku)
            ->whereHas('product', fn ($query) => $query->where('store_id', $product->store_id))
            ->when($excludeVariantId !== null, fn ($query) => $query->where('id', '!=', $excludeVariantId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'sku' => "The SKU \"{$sku}\" is already in use in this store.",
            ]);
        }
    }
}
