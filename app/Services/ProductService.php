<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductStatusChanged;
use App\Events\ProductUpdated;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function __construct(private readonly HandleGenerator $handles) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data): Product {
            $requestedStatus = ProductStatus::from($data['status'] ?? ProductStatus::Draft->value);
            $product = Product::withoutGlobalScopes()->create([
                'store_id' => $store->id,
                'title' => trim((string) $data['title']),
                'handle' => $this->handles->generate((string) ($data['handle'] ?? $data['title']), 'products', $store->id),
                'status' => ProductStatus::Draft->value,
                'description_html' => $data['description_html'] ?? null,
                'vendor' => $data['vendor'] ?? null,
                'product_type' => $data['product_type'] ?? null,
                'tags' => $data['tags'] ?? [],
            ]);

            foreach ($data['options'] ?? [] as $optionPosition => $optionData) {
                $option = $product->options()->create([
                    'name' => $optionData['name'],
                    'position' => $optionPosition,
                ]);

                foreach ($optionData['values'] ?? [] as $valuePosition => $value) {
                    $option->values()->create(['value' => $value, 'position' => $valuePosition]);
                }
            }

            $variants = $data['variants'] ?? [['price_amount' => (int) ($data['price_amount'] ?? 0), 'is_default' => true]];

            foreach ($variants as $position => $variantData) {
                $sku = $variantData['sku'] ?? null;

                if ($sku && DB::table('product_variants')
                    ->join('products', 'products.id', '=', 'product_variants.product_id')
                    ->where('products.store_id', $store->id)
                    ->where('product_variants.sku', $sku)
                    ->exists()) {
                    throw ValidationException::withMessages(['variants.'.$position.'.sku' => 'This SKU is already in use in this store.']);
                }

                $variant = $product->variants()->create([
                    'sku' => $sku,
                    'barcode' => $variantData['barcode'] ?? null,
                    'price_amount' => (int) ($variantData['price_amount'] ?? 0),
                    'compare_at_amount' => $variantData['compare_at_amount'] ?? null,
                    'currency' => $variantData['currency'] ?? $store->default_currency,
                    'weight_g' => $variantData['weight_g'] ?? null,
                    'requires_shipping' => $variantData['requires_shipping'] ?? true,
                    'is_default' => $variantData['is_default'] ?? $position === 0,
                    'position' => $position,
                ]);

                $variant->inventoryItem()->create([
                    'store_id' => $store->id,
                    'quantity_on_hand' => (int) ($variantData['quantity_on_hand'] ?? 0),
                    'policy' => $variantData['inventory_policy'] ?? 'deny',
                ]);

                if (isset($variantData['option_value_ids'])) {
                    $variant->optionValues()->sync($variantData['option_value_ids']);
                }
            }

            if ($requestedStatus !== ProductStatus::Draft) {
                $this->transitionStatus($product, $requestedStatus);
            }

            $product = $product->load(['options.values', 'variants.inventoryItem']);
            ProductCreated::dispatch($product);

            return $product;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data, bool $dispatchDomainEvent = true): Product
    {
        $product->fill(collect($data)->only(['title', 'description_html', 'vendor', 'product_type', 'tags'])->all());

        if (isset($data['handle']) && $data['handle'] !== $product->handle) {
            $product->handle = $this->handles->generate($data['handle'], 'products', $product->store_id, $product->id);
        }

        if ($product->isDirty()) {
            $product->save();

            if ($dispatchDomainEvent) {
                ProductUpdated::dispatch($product->refresh());
            }
        }

        return $product->refresh();
    }

    public function transitionStatus(Product $product, ProductStatus $newStatus, bool $dispatchDomainEvent = true): void
    {
        $current = ProductStatus::from($product->status);

        if ($current === $newStatus) {
            return;
        }

        $hasOrderHistory = OrderLine::query()
            ->whereIn('variant_id', $product->variants()->select('id'))
            ->exists();

        $allowed = match ($current) {
            ProductStatus::Draft => in_array($newStatus, [ProductStatus::Active, ProductStatus::Archived], true),
            ProductStatus::Active => $newStatus === ProductStatus::Archived || ($newStatus === ProductStatus::Draft && ! $hasOrderHistory),
            ProductStatus::Archived => $newStatus === ProductStatus::Active || ($newStatus === ProductStatus::Draft && ! $hasOrderHistory),
        };

        if (! $allowed) {
            throw new InvalidProductTransitionException("Cannot change a {$current->value} product to {$newStatus->value}.");
        }

        if ($newStatus === ProductStatus::Active) {
            if (blank($product->title) || ! $product->variants()->where('price_amount', '>', 0)->exists()) {
                throw new InvalidProductTransitionException('A product needs a title and at least one priced variant before it can be activated.');
            }

            $product->published_at ??= now();
        }

        $wasRecentlyCreated = $product->wasRecentlyCreated;
        $oldStatus = $product->status;
        $product->status = $newStatus->value;
        $product->save();

        if ($dispatchDomainEvent) {
            ProductStatusChanged::dispatch($product, $oldStatus, $newStatus->value, $wasRecentlyCreated);
        }
    }

    public function delete(Product $product): void
    {
        if ($product->status !== ProductStatus::Draft->value || OrderLine::query()->whereIn('variant_id', $product->variants()->select('id'))->exists()) {
            throw new InvalidProductTransitionException('Only draft products without order history can be deleted.');
        }

        $productSnapshot = [
            'id' => $product->getKey(),
            'store_id' => $product->store_id,
            'title' => $product->title,
            'handle' => $product->handle,
            'status' => $product->status,
            'vendor' => $product->vendor,
            'product_type' => $product->product_type,
            'tags' => $product->tags,
        ];
        $storeId = (int) $product->store_id;
        $product->delete();
        ProductDeleted::dispatch($storeId, $productSnapshot);
    }
}
