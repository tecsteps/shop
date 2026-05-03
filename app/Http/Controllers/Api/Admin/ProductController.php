<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListProductsRequest;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Resources\Admin\ProductResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(ListProductsRequest $request, Store $store): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 25);

        $query = Product::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->with('variants.inventoryItem', 'variants.optionValues.option', 'media', 'options.values', 'collections')
            ->withCount('variants')
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['collection_id'] ?? null, fn ($query, int $collectionId) => $query->whereHas('collections', fn ($query) => $query->whereKey($collectionId)))
            ->when($validated['query'] ?? null, function ($query, string $term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('title', 'like', '%'.$term.'%')
                        ->orWhere('vendor', 'like', '%'.$term.'%')
                        ->orWhereHas('variants', fn ($query) => $query->where('sku', 'like', '%'.$term.'%'));
                });
            });

        $this->applySort($query, $validated['sort'] ?? 'updated_at_desc');

        return ProductResource::collection(
            $query->paginate($perPage)->appends($request->query())
        );
    }

    public function store(StoreProductRequest $request, Store $store, ProductService $products): JsonResponse
    {
        $validated = $request->validated();

        try {
            $product = $products->create($store, $this->productPayload($validated, $store));
        } catch (InvalidProductTransitionException $exception) {
            throw ValidationException::withMessages([
                'product' => [$exception->getMessage()],
            ]);
        }

        $this->syncCollections($product, $validated['collections'] ?? null);
        $this->syncVariants($product, $store, $this->normalizedVariants($validated['variants'] ?? []));

        return (new ProductResource($this->loadProduct($product)))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Store $store, int $product): ProductResource
    {
        return new ProductResource($this->findProduct($store, $product));
    }

    public function update(UpdateProductRequest $request, Store $store, int $product, ProductService $products): ProductResource
    {
        $existingProduct = $this->findProduct($store, $product);
        $validated = $request->validated();

        try {
            $existingProduct = $products->update($existingProduct, $this->productPayload($validated, $store));
        } catch (InvalidProductTransitionException $exception) {
            throw ValidationException::withMessages([
                'product' => [$exception->getMessage()],
            ]);
        }

        if (array_key_exists('collections', $validated)) {
            $this->syncCollections($existingProduct, $validated['collections']);
        }

        if (array_key_exists('variants', $validated)) {
            $this->syncVariants($existingProduct, $store, $this->normalizedVariants($validated['variants']));
        }

        return new ProductResource($this->loadProduct($existingProduct));
    }

    public function destroy(Store $store, int $product, ProductService $products): ProductResource
    {
        $existingProduct = $this->findProduct($store, $product);
        $products->transitionStatus($existingProduct, ProductStatus::Archived);

        return new ProductResource($this->loadProduct($existingProduct));
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'title_asc' => $query->orderBy('title'),
            'title_desc' => $query->orderByDesc('title'),
            'created_at_asc' => $query->orderBy('created_at'),
            'created_at_desc' => $query->orderByDesc('created_at'),
            default => $query->latest('updated_at'),
        };
    }

    private function findProduct(Store $store, int $productId): Product
    {
        return Product::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereKey($productId)
            ->firstOrFail();
    }

    private function loadProduct(Product $product): Product
    {
        return $product->refresh()
            ->load('variants.inventoryItem', 'variants.optionValues.option', 'media', 'options.values', 'collections')
            ->loadCount('variants');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function productPayload(array $validated, Store $store): array
    {
        $payload = Arr::only($validated, [
            'title',
            'handle',
            'description_html',
            'vendor',
            'product_type',
            'status',
            'tags',
            'options',
        ]);

        if (isset($payload['options'])) {
            $payload['options'] = collect($payload['options'])
                ->map(fn (array $option): array => [
                    'name' => $option['name'],
                    'values' => $option['values'] ?? [],
                ])
                ->values()
                ->all();
        }

        if (isset($validated['variants'])) {
            $payload['variants'] = collect($this->normalizedVariants($validated['variants']))
                ->map(fn (array $variant): array => [
                    ...Arr::only($variant, [
                        'sku',
                        'barcode',
                        'price_amount',
                        'compare_at_amount',
                        'currency',
                        'weight_g',
                        'requires_shipping',
                        'is_default',
                        'position',
                        'status',
                    ]),
                    'currency' => $variant['currency'] ?? $store->default_currency,
                ])
                ->values()
                ->all();
        }

        return $payload;
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     * @return array<int, array<string, mixed>>
     */
    private function normalizedVariants(array $variants): array
    {
        $hasDefault = collect($variants)->contains(fn (array $variant): bool => (bool) ($variant['is_default'] ?? false));

        return collect(array_values($variants))
            ->map(function (array $variant, int $index) use ($hasDefault): array {
                return [
                    ...$variant,
                    'is_default' => (bool) ($variant['is_default'] ?? (! $hasDefault && $index === 0)),
                    'position' => (int) ($variant['position'] ?? $index),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>|null  $collectionIds
     */
    private function syncCollections(Product $product, ?array $collectionIds): void
    {
        if ($collectionIds === null) {
            return;
        }

        $product->collections()->sync($collectionIds);
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function syncVariants(Product $product, Store $store, array $variants): void
    {
        if ($variants === []) {
            return;
        }

        $existingVariants = $product->variants()
            ->with('inventoryItem')
            ->orderBy('position')
            ->get()
            ->values();

        $product->variants()->update(['is_default' => false]);

        foreach ($variants as $index => $variantData) {
            $variant = isset($variantData['id'])
                ? $product->variants()->whereKey((int) $variantData['id'])->firstOrFail()
                : $existingVariants->get($index);

            if (! $variant instanceof ProductVariant) {
                $variant = $product->variants()->create([
                    'price_amount' => 0,
                    'currency' => $store->default_currency,
                    'position' => $index,
                ]);
            }

            $variant->forceFill([
                ...Arr::only($variantData, [
                    'sku',
                    'barcode',
                    'price_amount',
                    'compare_at_amount',
                    'currency',
                    'weight_g',
                    'requires_shipping',
                    'is_default',
                    'position',
                    'status',
                ]),
                'currency' => $variantData['currency'] ?? $store->default_currency,
            ])->save();

            $inventory = $variantData['inventory'] ?? [];

            $variant->inventoryItem()->updateOrCreate(
                ['variant_id' => $variant->id],
                [
                    'store_id' => $store->id,
                    'quantity_on_hand' => (int) ($inventory['quantity_on_hand'] ?? $variant->inventoryItem?->quantity_on_hand ?? 0),
                    'policy' => $inventory['policy'] ?? $variant->inventoryItem?->policy ?? InventoryPolicy::Deny,
                ],
            );
        }
    }
}
