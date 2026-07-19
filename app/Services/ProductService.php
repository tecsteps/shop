<?php

namespace App\Services;

use App\Actions\SanitizeHtml;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductStatusChanged;
use App\Events\ProductUpdated;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function __construct(
        private SanitizeHtml $sanitizeHtml,
        private VariantMatrixService $variantMatrix,
    ) {}

    /**
     * Create a product with nested options, option values, variants, and
     * inventory items in a single transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data): Product {
            $product = new Product;
            $product->store_id = $store->id;
            $product->fill($this->productAttributes($data, $store->id));
            $product->status = $data['status'] ?? ProductStatus::Draft;
            $product->handle = HandleGenerator::generate(
                $data['handle'] ?? $data['title'],
                'products',
                $store->id,
            );
            $product->save();

            $this->syncOptions($product, $data['options'] ?? []);

            if ($product->options()->exists()) {
                $this->variantMatrix->rebuildMatrix($product);
                $this->applyVariantDefaults($product, $data['variant_defaults'] ?? []);
                $this->applyVariantOverrides($product, $data['variants'] ?? []);
            } else {
                $this->createDefaultVariant($product, $data['variants'][0] ?? []);
            }

            ProductCreated::dispatch($product);

            return $product->refresh();
        });
    }

    /**
     * Update a product and its nested structure.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $product->fill($this->productAttributes($data, $product->store_id));

            if (array_key_exists('handle', $data)) {
                $product->handle = HandleGenerator::generate(
                    $data['handle'] ?: $product->title,
                    'products',
                    $product->store_id,
                    $product->id,
                );
            }

            $product->save();

            if (array_key_exists('options', $data)) {
                $this->syncOptions($product, $data['options']);

                if ($product->options()->exists()) {
                    $this->variantMatrix->rebuildMatrix($product);
                    $this->applyVariantDefaults($product, $data['variant_defaults'] ?? []);
                    $this->applyVariantOverrides($product, $data['variants'] ?? []);
                } elseif (! $product->variants()->exists()) {
                    $this->createDefaultVariant($product, $data['variants'][0] ?? []);
                }
            } elseif (array_key_exists('variants', $data)) {
                $this->applyVariantOverrides($product, $data['variants']);
            }

            ProductUpdated::dispatch($product);

            return $product->refresh();
        });
    }

    /**
     * Transition the product to a new status, enforcing the state machine.
     *
     * @throws InvalidProductTransitionException
     */
    public function transitionStatus(Product $product, ProductStatus $newStatus): void
    {
        DB::transaction(function () use ($product, $newStatus): void {
            $oldStatus = $product->status;

            if ($oldStatus === $newStatus) {
                throw InvalidProductTransitionException::transition($oldStatus->value, $newStatus->value, 'Product is already in this status.');
            }

            match ($newStatus) {
                ProductStatus::Active => $this->assertPublishable($product, $oldStatus),
                ProductStatus::Draft => $this->assertNoOrderReferences($product, $oldStatus, $newStatus),
                ProductStatus::Archived => null,
            };

            $product->status = $newStatus;

            if ($newStatus === ProductStatus::Active && $product->published_at === null) {
                $product->published_at = now();
            }

            $product->save();

            ProductStatusChanged::dispatch($product, $oldStatus, $newStatus);
        });
    }

    /**
     * Hard-delete a product. Only drafts without order references may be
     * deleted; everything else must be archived instead.
     *
     * @throws InvalidProductTransitionException
     */
    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            if ($product->status !== ProductStatus::Draft) {
                throw InvalidProductTransitionException::deletion('Only draft products can be deleted; archive it instead.');
            }

            if ($this->hasOrderReferences($product)) {
                throw InvalidProductTransitionException::deletion('Products referenced by orders cannot be deleted; archive it instead.');
            }

            $product->delete();

            ProductDeleted::dispatch($product);
        });
    }

    /**
     * Extract and sanitize the product's own attributes from the payload.
     *
     * @return array<string, mixed>
     */
    private function productAttributes(array $data, int $storeId): array
    {
        $attributes = Arr::only($data, [
            'title', 'description_html', 'vendor', 'product_type', 'tags', 'published_at',
        ]);

        if (array_key_exists('description_html', $attributes)) {
            $attributes['description_html'] = ($this->sanitizeHtml)($attributes['description_html']);
        }

        return $attributes;
    }

    /**
     * Sync the product's options and values against the payload.
     *
     * Existing records are matched by id, then by name/value, and updated in
     * place so variant option-value links survive. Anything missing from the
     * payload is deleted.
     *
     * @param  list<array<string, mixed>>  $optionsData
     */
    private function syncOptions(Product $product, array $optionsData): void
    {
        $keepOptionIds = [];

        foreach (array_values($optionsData) as $optionIndex => $optionData) {
            $option = $this->matchOption($product, $optionData);
            $option->fill(['name' => $optionData['name'], 'position' => $optionIndex + 1000])->save();

            $keepValueIds = [];

            foreach (array_values($optionData['values'] ?? []) as $valueIndex => $valueData) {
                $valueData = is_array($valueData) ? $valueData : ['value' => $valueData];

                $value = $this->matchOptionValue($option, $valueData);
                $value->fill(['value' => $valueData['value'], 'position' => $valueIndex + 1000])->save();

                $keepValueIds[] = $value->id;
            }

            $option->values()->whereNotIn('id', $keepValueIds)->delete();
            $option->values()->whereIn('id', $keepValueIds)->get()->each(
                fn ($value, $index) => $value->update(['position' => $index])
            );

            $option->update(['position' => $optionIndex]);
            $keepOptionIds[] = $option->id;
        }

        $product->options()->whereNotIn('id', $keepOptionIds)->delete();
    }

    /**
     * Find an existing option by id or name, or make a new instance.
     *
     * @param  array<string, mixed>  $optionData
     */
    private function matchOption(Product $product, array $optionData): ProductOption
    {
        $option = null;

        if (! empty($optionData['id'])) {
            $option = $product->options()->whereKey($optionData['id'])->first();
        }

        $option ??= $product->options()->where('name', $optionData['name'])->first();

        return $option ?? $product->options()->make();
    }

    /**
     * Find an existing option value by id or value string, or make a new one.
     *
     * @param  array<string, mixed>  $valueData
     */
    private function matchOptionValue(ProductOption $option, array $valueData): \App\Models\ProductOptionValue
    {
        $value = null;

        if (! empty($valueData['id'])) {
            $value = $option->values()->whereKey($valueData['id'])->first();
        }

        $value ??= $option->values()->where('value', $valueData['value'])->first();

        return $value ?? $option->values()->make();
    }

    /**
     * Create the single default variant for a product without options.
     *
     * @param  array<string, mixed>  $variantData
     */
    private function createDefaultVariant(Product $product, array $variantData): void
    {
        $sku = $variantData['sku'] ?? null;
        $this->assertSkuIsUnique($product->store_id, $sku);

        $variant = $product->variants()->create(array_merge(
            $this->variantAttributes($variantData),
            ['is_default' => true, 'position' => 0],
        ));

        $variant->inventoryItem()->create(array_merge(
            ['store_id' => $product->store_id, 'quantity_on_hand' => 0],
            $variantData['inventory'] ?? [],
        ));
    }

    /**
     * Apply pricing defaults to all variants of the product.
     *
     * @param  array<string, mixed>  $defaults
     */
    private function applyVariantDefaults(Product $product, array $defaults): void
    {
        if ($defaults === []) {
            return;
        }

        foreach ($product->variants()->get() as $variant) {
            $variant->update($this->variantAttributes($defaults));
        }
    }

    /**
     * Apply per-variant overrides, matched by id or by option value names.
     *
     * @param  list<array<string, mixed>>  $variantsData
     */
    private function applyVariantOverrides(Product $product, array $variantsData): void
    {
        foreach ($variantsData as $variantData) {
            $variant = $this->matchVariant($product, $variantData);

            if (! $variant instanceof ProductVariant) {
                continue;
            }

            $sku = $variantData['sku'] ?? null;

            if ($sku !== null && $sku !== $variant->sku) {
                $this->assertSkuIsUnique($product->store_id, $sku, $variant->id);
            }

            $variant->update($this->variantAttributes($variantData));

            if (array_key_exists('inventory', $variantData)) {
                $variant->inventoryItem()->updateOrCreate(
                    ['variant_id' => $variant->id],
                    array_merge(['store_id' => $product->store_id], $variantData['inventory']),
                );
            }
        }
    }

    /**
     * Find the variant an override payload refers to.
     *
     * @param  array<string, mixed>  $variantData
     */
    private function matchVariant(Product $product, array $variantData): ?ProductVariant
    {
        if (! empty($variantData['id'])) {
            return $product->variants()->whereKey($variantData['id'])->first();
        }

        if (! empty($variantData['option_values'])) {
            $wanted = collect($variantData['option_values'])->map(fn ($value) => mb_strtolower(trim((string) $value)))->sort()->values();

            return $product->variants()
                ->with('optionValues')
                ->where('status', VariantStatus::Active)
                ->get()
                ->first(function (ProductVariant $variant) use ($wanted): bool {
                    $actual = $variant->optionValues->pluck('value')->map(fn ($value) => mb_strtolower(trim((string) $value)))->sort()->values();

                    return $actual->all() === $wanted->all();
                });
        }

        return null;
    }

    /**
     * Extract the variant's own attributes from the payload.
     *
     * @return array<string, mixed>
     */
    private function variantAttributes(array $data): array
    {
        return Arr::only($data, [
            'sku', 'barcode', 'price_amount', 'compare_at_amount', 'currency',
            'weight_g', 'requires_shipping', 'position',
        ]);
    }

    /**
     * Ensure the SKU is unique across all variants of the store.
     * Null and empty SKUs are exempt.
     *
     * @throws ValidationException
     */
    private function assertSkuIsUnique(int $storeId, ?string $sku, ?int $excludeVariantId = null): void
    {
        if ($sku === null || trim($sku) === '') {
            return;
        }

        $exists = DB::table('product_variants')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->where('products.store_id', $storeId)
            ->where('product_variants.sku', $sku)
            ->when($excludeVariantId !== null, fn ($query) => $query->where('product_variants.id', '!=', $excludeVariantId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'sku' => ["The SKU '{$sku}' is already used by another variant in this store."],
            ]);
        }
    }

    /**
     * Ensure the product satisfies the preconditions for activation:
     * a non-empty title and at least one active variant with a price.
     *
     * @throws InvalidProductTransitionException
     */
    private function assertPublishable(Product $product, ProductStatus $from): void
    {
        if (trim($product->title) === '') {
            throw InvalidProductTransitionException::transition($from->value, ProductStatus::Active->value, 'The product title must not be empty.');
        }

        $hasPricedVariant = $product->variants()
            ->where('status', VariantStatus::Active)
            ->where('price_amount', '>', 0)
            ->exists();

        if (! $hasPricedVariant) {
            throw InvalidProductTransitionException::transition($from->value, ProductStatus::Active->value, 'At least one variant with a price greater than zero is required.');
        }
    }

    /**
     * Ensure no order lines reference the product before reverting to draft.
     *
     * @throws InvalidProductTransitionException
     */
    private function assertNoOrderReferences(Product $product, ProductStatus $from, ProductStatus $to): void
    {
        if ($this->hasOrderReferences($product)) {
            throw InvalidProductTransitionException::transition($from->value, $to->value, 'The product is referenced by existing orders.');
        }
    }

    /**
     * Whether any order line references the product or one of its variants.
     */
    private function hasOrderReferences(Product $product): bool
    {
        return DB::table('order_lines')
            ->where('product_id', $product->id)
            ->orWhereIn('variant_id', $product->variants()->select('id'))
            ->exists();
    }
}
