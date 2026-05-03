<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Events\ProductStatusChanged;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductService
{
    public function __construct(
        private readonly HandleGenerator $handleGenerator,
        private readonly VariantMatrixService $variantMatrixService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data): Product {
            $product = Product::query()->create([
                ...Arr::only($data, [
                    'title',
                    'status',
                    'description_html',
                    'vendor',
                    'product_type',
                    'tags',
                    'published_at',
                ]),
                'store_id' => $store->id,
                'handle' => $data['handle'] ?? $this->handleGenerator->generate($data['title'], (new Product)->getTable(), $store->id),
            ]);

            $this->createOptions($product, $data['options'] ?? []);
            $this->createVariants($product, $data['variants'] ?? []);

            if ($product->variants()->count() === 0) {
                ProductVariant::query()->create([
                    'product_id' => $product->id,
                    'price_amount' => (int) ($data['price_amount'] ?? 0),
                    'currency' => $store->default_currency,
                    'is_default' => true,
                ]);
            }

            if ($product->options()->exists()) {
                $this->variantMatrixService->rebuildMatrix($product);
            }

            return $product->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $payload = Arr::only($data, [
                'title',
                'status',
                'description_html',
                'vendor',
                'product_type',
                'tags',
                'published_at',
            ]);

            if (array_key_exists('handle', $data)) {
                $payload['handle'] = $data['handle'] ?: $this->handleGenerator->generate(
                    $data['title'] ?? $product->title,
                    $product->getTable(),
                    $product->store_id,
                    $product->id,
                );
            }

            $product->update($payload);

            return $product->refresh();
        });
    }

    public function transitionStatus(Product $product, ProductStatus $newStatus): void
    {
        $from = $product->status;

        if ($from === $newStatus) {
            return;
        }

        $this->assertTransitionAllowed($product, $newStatus);

        $product->forceFill([
            'status' => $newStatus,
            'published_at' => $newStatus === ProductStatus::Active && ! $product->published_at
                ? now()
                : $product->published_at,
        ])->save();

        ProductStatusChanged::dispatch($product->refresh(), $from, $newStatus);
    }

    public function delete(Product $product): void
    {
        if ($product->status !== ProductStatus::Draft || $this->hasOrderReferences($product)) {
            throw new InvalidProductTransitionException('Only draft products without order references can be deleted.');
        }

        $product->delete();
    }

    private function assertTransitionAllowed(Product $product, ProductStatus $newStatus): void
    {
        if ($newStatus === ProductStatus::Active && ! $this->canActivate($product)) {
            throw new InvalidProductTransitionException('Products need a title and a priced variant before activation.');
        }

        if ($newStatus === ProductStatus::Draft && $this->hasOrderReferences($product)) {
            throw new InvalidProductTransitionException('Products with order references cannot be reverted to draft.');
        }
    }

    private function canActivate(Product $product): bool
    {
        return trim($product->title) !== ''
            && $product->variants()
                ->where('status', VariantStatus::Active->value)
                ->where('price_amount', '>', 0)
                ->exists();
    }

    private function hasOrderReferences(Product $product): bool
    {
        if (! Schema::hasTable('order_lines')) {
            return false;
        }

        return DB::table('order_lines')
            ->whereIn('variant_id', $product->variants()->withoutGlobalScopes()->pluck('id'))
            ->orWhere('product_id', $product->id)
            ->exists();
    }

    /**
     * @param  array<int, array{name: string, values?: array<int, string>}>  $options
     */
    private function createOptions(Product $product, array $options): void
    {
        foreach (array_values($options) as $optionIndex => $optionData) {
            $option = $product->options()->create([
                'name' => $optionData['name'],
                'position' => $optionIndex,
            ]);

            foreach (array_values($optionData['values'] ?? []) as $valueIndex => $value) {
                $option->values()->create([
                    'value' => $value,
                    'position' => $valueIndex,
                ]);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function createVariants(Product $product, array $variants): void
    {
        foreach (array_values($variants) as $index => $variantData) {
            $this->assertSkuIsUnique($product, $variantData['sku'] ?? null);

            $product->variants()->create([
                ...Arr::only($variantData, [
                    'sku',
                    'barcode',
                    'price_amount',
                    'compare_at_amount',
                    'currency',
                    'weight_g',
                    'requires_shipping',
                    'is_default',
                    'status',
                ]),
                'position' => $variantData['position'] ?? $index,
                'currency' => $variantData['currency'] ?? $product->store->default_currency,
            ]);
        }
    }

    private function assertSkuIsUnique(Product $product, ?string $sku): void
    {
        if (! $sku) {
            return;
        }

        $exists = ProductVariant::query()
            ->where('sku', $sku)
            ->whereHas('product', fn ($query) => $query->where('store_id', $product->store_id))
            ->exists();

        if ($exists) {
            throw new InvalidProductTransitionException('SKU already exists for this store.');
        }
    }
}
