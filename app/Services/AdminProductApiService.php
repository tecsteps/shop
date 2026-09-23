<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminProductApiService
{
    public function __construct(
        private readonly ProductService $products,
        private readonly VariantMatrixService $variants,
    ) {}

    /** @param array<string, mixed> $filters
     * @return array{data: list<array<string, mixed>>, meta: array{current_page: int, per_page: int, total: int, last_page: int}}
     */
    public function list(Store $store, array $filters): array
    {
        $query = Product::query()
            ->where('store_id', $store->getKey())
            ->with(['media', 'variants.inventoryItem'])
            ->withCount('variants')
            ->addSelect(['total_inventory' => DB::table('inventory_items')
                ->join('product_variants', 'product_variants.id', '=', 'inventory_items.variant_id')
                ->whereColumn('product_variants.product_id', 'products.id')
                ->where('product_variants.status', 'active')
                ->selectRaw('COALESCE(SUM(inventory_items.quantity_on_hand), 0)')]);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['query'])) {
            $search = addcslashes($filters['query'], '%_\\');
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('vendor', 'like', "%{$search}%")
                    ->orWhereHas('variants', fn (Builder $variants) => $variants->where('sku', 'like', "%{$search}%"));
            });
        }

        if (isset($filters['collection_id'])) {
            $query->whereHas('collections', fn (Builder $collections) => $collections->whereKey($filters['collection_id']));
        }

        $query->orderBy(match ($filters['sort'] ?? 'updated_at_desc') {
            'title_asc', 'title_desc' => 'title',
            'created_at_asc', 'created_at_desc' => 'created_at',
            default => 'updated_at',
        }, str_ends_with($filters['sort'] ?? 'updated_at_desc', '_asc') ? 'asc' : 'desc');

        $page = $filters['page'] ?? 1;
        $perPage = $filters['per_page'] ?? 25;
        $products = $query->paginate($perPage, $query->getQuery()->columns ?? ['*'], 'page', $page);

        return [
            'data' => $products->getCollection()->map(fn (Product $product): array => $this->listResource($product))->all(),
            'meta' => $this->paginationMeta($products),
        ];
    }

    /** @param array<string, mixed> $data */
    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data): Product {
            $submittedVariants = $data['variants'];
            $options = $this->normalizeOptions($data['options'] ?? [], $submittedVariants);
            $requestedStatus = ProductStatus::from($data['status'] ?? ProductStatus::Draft->value);
            $creationData = [
                ...$data,
                'status' => ProductStatus::Draft->value,
                'options' => $options,
                'variants' => array_map(fn (array $variant): array => $this->variantAttributes($variant, $store), $submittedVariants),
            ];
            $product = $this->products->create($store, $creationData);
            $product->load(['options.values', 'variants.inventoryItem']);
            $variantsByInitialPosition = $product->variants->keyBy('position');

            foreach ($submittedVariants as $position => $submittedVariant) {
                $variant = $variantsByInitialPosition->get($position);

                if (! $variant) {
                    throw ValidationException::withMessages(["variants.{$position}" => 'The variant could not be created.']);
                }

                $variant->optionValues()->sync($this->optionValueIds($product->options, $submittedVariant['option_values'] ?? []));

                if (isset($submittedVariant['status'])) {
                    $variant->update(['status' => $submittedVariant['status']]);
                }

                if (isset($submittedVariant['position'])) {
                    $variant->update(['position' => $submittedVariant['position'] - 1]);
                }
            }

            if (array_key_exists('collections', $data)) {
                $product->collections()->sync($data['collections']);
            }

            if ($requestedStatus !== ProductStatus::Draft) {
                $this->products->transitionStatus($product, $requestedStatus);
            }

            return $this->loadProduct($product);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Product $product, Store $store, array $data): Product
    {
        return DB::transaction(function () use ($product, $store, $data): Product {
            $requestedStatus = isset($data['status']) ? ProductStatus::from($data['status']) : null;
            $productData = collect($data)->only(['title', 'handle', 'description_html', 'vendor', 'product_type', 'tags'])->all();
            $this->products->update($product, $productData);

            if (array_key_exists('options', $data)) {
                $product->load(['options.values', 'variants.optionValues.option', 'variants.inventoryItem']);
                $options = $this->normalizeOptions($data['options'], $data['variants'] ?? [], $product);
                $this->variants->rebuild($product, $options);
                $product->load(['options.values', 'variants.optionValues.option', 'variants.inventoryItem']);

                foreach ($data['variants'] ?? [] as $position => $submittedVariant) {
                    $variant = $this->matchVariant($product, $submittedVariant, $position);
                    $this->applyVariantUpdate($variant, $submittedVariant, $product, $store);
                }
            } elseif (isset($data['variants'])) {
                $product->load(['options.values', 'variants.optionValues.option', 'variants.inventoryItem']);

                foreach ($data['variants'] as $position => $submittedVariant) {
                    $variant = isset($submittedVariant['id'])
                        ? $product->variants->firstWhere('id', $submittedVariant['id'])
                        : $this->createVariant($product, $store, $submittedVariant, $position);

                    if (! $variant) {
                        throw ValidationException::withMessages(["variants.{$position}.id" => 'The variant does not belong to this product.']);
                    }

                    $this->applyVariantUpdate($variant, $submittedVariant, $product, $store);
                }
            }

            if (isset($data['delete_variant_ids'])) {
                $this->deleteVariants($product, $data['delete_variant_ids']);
            }

            $this->normalizeDefaultVariant($product);

            if (array_key_exists('collections', $data)) {
                $product->collections()->sync($data['collections']);
            }

            $product->refresh();

            if ($requestedStatus !== null && $product->status !== $requestedStatus->value) {
                $this->products->transitionStatus($product, $requestedStatus);
            }

            return $this->loadProduct($product);
        });
    }

    /** @return array<string, mixed> */
    public function resource(Product $product): array
    {
        $product = $this->loadProduct($product);

        return [
            'id' => $product->getKey(),
            'store_id' => $product->store_id,
            'title' => $product->title,
            'handle' => $product->handle,
            'description_html' => $product->description_html,
            'vendor' => $product->vendor,
            'product_type' => $product->product_type,
            'status' => $product->status,
            'tags' => $product->tags ?? [],
            'published_at' => $product->published_at?->toISOString(),
            'created_at' => $product->created_at?->toISOString(),
            'updated_at' => $product->updated_at?->toISOString(),
            'options' => $product->options->map(fn (ProductOption $option): array => [
                'id' => $option->getKey(),
                'name' => $option->name,
                'position' => $option->position + 1,
                'values' => $option->values->map(fn (ProductOptionValue $value): array => [
                    'id' => $value->getKey(),
                    'value' => $value->value,
                    'position' => $value->position + 1,
                ])->all(),
            ])->all(),
            'variants' => $product->variants->map(fn (ProductVariant $variant): array => [
                'id' => $variant->getKey(),
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,
                'price_amount' => (int) $variant->price_amount,
                'compare_at_amount' => $variant->compare_at_amount === null ? null : (int) $variant->compare_at_amount,
                'currency' => $variant->currency,
                'weight_g' => $variant->weight_g === null ? null : (int) $variant->weight_g,
                'requires_shipping' => (bool) $variant->requires_shipping,
                'is_default' => (bool) $variant->is_default,
                'position' => $variant->position + 1,
                'status' => $variant->status,
                'option_values' => $variant->optionValues->map(fn (ProductOptionValue $value): array => [
                    'option_name' => $value->option?->name,
                    'value' => $value->value,
                ])->all(),
                'inventory' => [
                    'quantity_on_hand' => (int) ($variant->inventoryItem?->quantity_on_hand ?? 0),
                    'quantity_reserved' => (int) ($variant->inventoryItem?->quantity_reserved ?? 0),
                    'policy' => $variant->inventoryItem?->policy ?? 'deny',
                ],
            ])->all(),
            'media' => $product->media->map(fn (ProductMedia $media): array => [
                'id' => $media->getKey(),
                'type' => $media->type,
                'storage_key' => $media->storage_key,
                'url' => $media->url,
                'alt_text' => $media->alt_text,
                'width' => $media->width,
                'height' => $media->height,
                'mime_type' => $media->mime_type,
                'byte_size' => $media->byte_size,
                'position' => $media->position + 1,
                'status' => $media->status,
            ])->all(),
            'collections' => $product->collections->map(static fn (Collection $collection): array => [
                'id' => $collection->getKey(),
                'title' => $collection->title,
                'handle' => $collection->handle,
            ])->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function listResource(Product $product): array
    {
        $image = $product->media->firstWhere('type', 'image');

        return [
            'id' => $product->getKey(),
            'store_id' => $product->store_id,
            'title' => $product->title,
            'handle' => $product->handle,
            'status' => $product->status,
            'vendor' => $product->vendor,
            'product_type' => $product->product_type,
            'tags' => $product->tags ?? [],
            'variants_count' => (int) $product->variants_count,
            'total_inventory' => (int) $product->total_inventory,
            'published_at' => $product->published_at?->toISOString(),
            'created_at' => $product->created_at?->toISOString(),
            'updated_at' => $product->updated_at?->toISOString(),
            'featured_image' => $image === null ? null : ['url' => $image->url, 'alt_text' => $image->alt_text],
        ];
    }

    /** @param list<array<string, mixed>> $optionData
     * @param  list<array<string, mixed>>  $submittedVariants
     * @return list<array{name: string, values: list<string>}>
     */
    private function normalizeOptions(array $optionData, array $submittedVariants, ?Product $product = null): array
    {
        usort($optionData, static fn (array $left, array $right): int => ($left['position'] ?? 0) <=> ($right['position'] ?? 0));
        $options = [];

        foreach ($optionData as $position => $option) {
            $name = trim((string) ($option['name'] ?? ''));
            $existing = $product?->options->first(fn (ProductOption $candidate): bool => mb_strtolower($candidate->name) === mb_strtolower($name));
            $values = array_map('strval', $option['values'] ?? $existing?->values->pluck('value')->all() ?? []);

            foreach ($submittedVariants as $variant) {
                foreach ($variant['option_values'] ?? [] as $optionValue) {
                    if (mb_strtolower((string) ($optionValue['option_name'] ?? '')) === mb_strtolower($name)) {
                        $values[] = (string) $optionValue['value'];
                    }
                }
            }

            $options[] = ['name' => $name, 'values' => array_values(array_unique($values))];
        }

        return $options;
    }

    /** @param EloquentCollection<int, ProductOption> $options
     * @param  list<array{option_name: string, value: string}>  $submittedValues
     * @return list<int>
     */
    private function optionValueIds(EloquentCollection $options, array $submittedValues): array
    {
        if ($options->isEmpty()) {
            if ($submittedValues !== []) {
                throw ValidationException::withMessages(['variants.option_values' => 'Option values require product options.']);
            }

            return [];
        }

        $submitted = collect($submittedValues)->keyBy(fn (array $value): string => mb_strtolower($value['option_name']));
        $ids = [];

        foreach ($options as $option) {
            $choice = $submitted->get(mb_strtolower($option->name));

            if (! $choice) {
                throw ValidationException::withMessages(['variants.option_values' => "Every variant must specify a value for {$option->name}."]);
            }

            $value = $option->values->first(fn (ProductOptionValue $candidate): bool => mb_strtolower($candidate->value) === mb_strtolower($choice['value']));

            if (! $value) {
                throw ValidationException::withMessages(['variants.option_values' => "The value {$choice['value']} does not exist for {$option->name}."]);
            }

            $ids[] = (int) $value->getKey();
        }

        if ($submitted->count() !== $options->count()) {
            throw ValidationException::withMessages(['variants.option_values' => 'Each option may appear only once per variant.']);
        }

        return $ids;
    }

    /** @param array<string, mixed> $submittedVariant */
    private function matchVariant(Product $product, array $submittedVariant, int $position): ProductVariant
    {
        if (isset($submittedVariant['option_values'])) {
            $key = $this->submittedCombinationKey($submittedVariant['option_values']);
            $variant = $product->variants->first(fn (ProductVariant $candidate): bool => $candidate->status === 'active' && $this->variantCombinationKey($candidate) === $key);

            if ($variant) {
                return $variant;
            }
        }

        if (isset($submittedVariant['id'])) {
            $variant = $product->variants->firstWhere('id', $submittedVariant['id']);

            if ($variant) {
                return $variant;
            }

            throw ValidationException::withMessages(["variants.{$position}.id" => 'The variant does not belong to this product.']);
        }

        return $product->variants->firstWhere('position', $position)
            ?? throw ValidationException::withMessages(["variants.{$position}" => 'The variant does not match an option combination.']);
    }

    /** @param array<string, mixed> $submittedVariant */
    private function applyVariantUpdate(ProductVariant $variant, array $submittedVariant, Product $product, Store $store): void
    {
        $attributes = [];

        $priceAmount = (int) ($submittedVariant['price_amount'] ?? $variant->price_amount);
        $compareAtAmount = array_key_exists('compare_at_amount', $submittedVariant)
            ? $submittedVariant['compare_at_amount']
            : $variant->compare_at_amount;

        if ($compareAtAmount !== null && (int) $compareAtAmount <= $priceAmount) {
            throw ValidationException::withMessages(['variants.compare_at_amount' => 'The compare-at price must be greater than the variant price.']);
        }

        foreach (['sku', 'barcode', 'price_amount', 'compare_at_amount', 'currency', 'weight_g', 'requires_shipping', 'is_default', 'status'] as $field) {
            if (array_key_exists($field, $submittedVariant)) {
                $attributes[$field] = $submittedVariant[$field];
            }
        }

        if (isset($submittedVariant['position'])) {
            $attributes['position'] = $submittedVariant['position'] - 1;
        }

        if ($attributes !== []) {
            $variant->update($attributes);
        }

        if (array_key_exists('option_values', $submittedVariant)) {
            $product->loadMissing('options.values');
            $variant->optionValues()->sync($this->optionValueIds($product->options, $submittedVariant['option_values']));
        }

        if (! array_key_exists('option_values', $submittedVariant) && $product->options()->exists() && ! $variant->optionValues()->exists()) {
            throw ValidationException::withMessages(['variants.option_values' => 'A variant must include a value for every product option.']);
        }

        if (($attributes['is_default'] ?? false) === true) {
            $product->variants()->whereKeyNot($variant->getKey())->update(['is_default' => false]);
        }

        if (isset($submittedVariant['inventory'])) {
            $inventory = $variant->inventoryItem;
            $quantity = (int) ($submittedVariant['inventory']['quantity_on_hand'] ?? $inventory?->quantity_on_hand ?? 0);
            $reserved = (int) ($inventory?->quantity_reserved ?? 0);

            if ($quantity < $reserved) {
                throw ValidationException::withMessages(['variants.inventory.quantity_on_hand' => 'Available quantity cannot be lower than quantity reserved for active checkouts.']);
            }

            $variant->inventoryItem()->updateOrCreate([], [
                'store_id' => $store->getKey(),
                'quantity_on_hand' => $quantity,
                'policy' => $submittedVariant['inventory']['policy'] ?? $inventory?->policy ?? 'deny',
            ]);
        }
    }

    /** @param array<string, mixed> $submittedVariant */
    private function createVariant(Product $product, Store $store, array $submittedVariant, int $position): ProductVariant
    {
        $variant = $product->variants()->create([
            ...$this->variantAttributes($submittedVariant, $store),
            'position' => ($submittedVariant['position'] ?? $position + 1) - 1,
            'is_default' => $submittedVariant['is_default'] ?? false,
        ]);
        $variant->inventoryItem()->create([
            'store_id' => $store->getKey(),
            'quantity_on_hand' => $submittedVariant['inventory']['quantity_on_hand'] ?? 0,
            'policy' => $submittedVariant['inventory']['policy'] ?? 'deny',
        ]);

        return $variant->load(['inventoryItem', 'optionValues.option']);
    }

    /** @param array<string, mixed> $submittedVariant
     * @return array<string, mixed>
     */
    private function variantAttributes(array $submittedVariant, Store $store): array
    {
        return [
            'sku' => $submittedVariant['sku'] ?? null,
            'barcode' => $submittedVariant['barcode'] ?? null,
            'price_amount' => $submittedVariant['price_amount'],
            'compare_at_amount' => $submittedVariant['compare_at_amount'] ?? null,
            'currency' => strtoupper($submittedVariant['currency'] ?? $store->default_currency),
            'weight_g' => $submittedVariant['weight_g'] ?? null,
            'requires_shipping' => $submittedVariant['requires_shipping'] ?? true,
            'is_default' => $submittedVariant['is_default'] ?? false,
            'quantity_on_hand' => $submittedVariant['inventory']['quantity_on_hand'] ?? 0,
            'inventory_policy' => $submittedVariant['inventory']['policy'] ?? 'deny',
            'status' => $submittedVariant['status'] ?? 'active',
        ];
    }

    /** @param list<int> $variantIds */
    private function deleteVariants(Product $product, array $variantIds): void
    {
        $variants = $product->variants()->whereIn('id', $variantIds)->get();

        if ($variants->count() !== count(array_unique($variantIds))) {
            throw ValidationException::withMessages(['delete_variant_ids' => 'One or more variants do not belong to this product.']);
        }

        foreach ($variants as $variant) {
            $hasOrderHistory = OrderLine::query()->where('variant_id', $variant->getKey())->exists();

            if ($hasOrderHistory) {
                $variant->update(['status' => 'archived', 'is_default' => false]);
            } else {
                $variant->delete();
            }
        }

    }

    private function normalizeDefaultVariant(Product $product): void
    {
        $activeVariants = $product->variants()->where('status', 'active')->orderBy('position')->get();
        $defaults = $activeVariants->where('is_default', true);

        if ($defaults->count() > 1) {
            throw ValidationException::withMessages(['variants' => 'Only one active variant may be the default.']);
        }

        if ($defaults->isEmpty() && $activeVariants->isNotEmpty()) {
            $activeVariants->first()->update(['is_default' => true]);
        }
    }

    private function submittedCombinationKey(array $values): string
    {
        return collect($values)->map(fn (array $value): string => mb_strtolower(trim($value['option_name'])).'='.mb_strtolower(trim($value['value'])))->sort()->implode('|');
    }

    private function variantCombinationKey(ProductVariant $variant): string
    {
        return $variant->optionValues
            ->map(fn (ProductOptionValue $value): string => mb_strtolower(trim((string) $value->option?->name)).'='.mb_strtolower(trim($value->value)))
            ->sort()
            ->implode('|');
    }

    private function loadProduct(Product $product): Product
    {
        return $product->refresh()->load([
            'options.values',
            'variants.inventoryItem',
            'variants.optionValues.option',
            'media',
            'collections',
        ]);
    }

    /** @return array{current_page: int, per_page: int, total: int, last_page: int} */
    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}
