<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\ResolvesApiContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Products\ListProductsRequest;
use App\Http\Requests\Admin\Products\StoreProductRequest;
use App\Http\Requests\Admin\Products\UpdateProductRequest;
use App\Http\Resources\Admin\ProductResource;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use ResolvesApiContext;

    public function index(ListProductsRequest $request, int $store): JsonResponse
    {
        $storeId = $this->scopedStoreId($request, $store);
        $validated = $request->validated();

        $query = Product::query()
            ->where('store_id', $storeId)
            ->withCount('variants');

        if (isset($validated['status']) && is_string($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['query']) && is_string($validated['query']) && trim($validated['query']) !== '') {
            $term = trim($validated['query']);
            $like = '%'.$term.'%';

            $query->where(function ($builder) use ($like): void {
                $builder->where('title', 'like', $like)
                    ->orWhere('vendor', 'like', $like)
                    ->orWhere('handle', 'like', $like);
            });
        }

        $sort = (string) ($validated['sort'] ?? 'updated_at_desc');

        match ($sort) {
            'title_asc' => $query->orderBy('title'),
            'title_desc' => $query->orderByDesc('title'),
            'created_at_asc' => $query->orderBy('created_at'),
            'created_at_desc' => $query->orderByDesc('created_at'),
            default => $query->orderByDesc('updated_at'),
        };

        $perPage = (int) ($validated['per_page'] ?? 25);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => ProductResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => (int) $paginator->currentPage(),
                'per_page' => (int) $paginator->perPage(),
                'total' => (int) $paginator->total(),
                'last_page' => (int) $paginator->lastPage(),
            ],
        ]);
    }

    public function store(StoreProductRequest $request, int $store): JsonResponse
    {
        $storeId = $this->scopedStoreId($request, $store);
        $storeModel = $this->currentStoreModel($request);
        $validated = $request->validated();

        $service = $this->resolveService('App\\Services\\ProductService');

        if ($service !== null && method_exists($service, 'create')) {
            try {
                $serviceProduct = $service->create($storeModel, $validated);

                if ($serviceProduct instanceof Product) {
                    $serviceProduct->loadCount('variants');

                    return response()->json([
                        'data' => ProductResource::make($serviceProduct)->resolve(),
                    ], 201);
                }
            } catch (\Throwable) {
                // Fall back to direct persistence while service contracts stabilize.
            }
        }

        $handle = $this->resolveUniqueHandle(
            $storeId,
            isset($validated['handle']) && is_string($validated['handle'])
                ? $validated['handle']
                : (string) $validated['title'],
        );

        $product = Product::query()->create([
            'store_id' => $storeId,
            'title' => (string) $validated['title'],
            'handle' => $handle,
            'status' => (string) ($validated['status'] ?? 'draft'),
            'description_html' => $validated['description_html'] ?? null,
            'vendor' => $validated['vendor'] ?? null,
            'product_type' => $validated['product_type'] ?? null,
            'tags' => isset($validated['tags']) && is_array($validated['tags']) ? $validated['tags'] : [],
            'published_at' => $validated['published_at'] ?? null,
        ]);

        $defaultVariant = ProductVariant::query()->create([
            'product_id' => (int) $product->id,
            'sku' => null,
            'barcode' => null,
            'price_amount' => (int) ($validated['price_amount'] ?? 0),
            'compare_at_amount' => isset($validated['compare_at_amount']) ? (int) $validated['compare_at_amount'] : null,
            'currency' => (string) ($validated['currency'] ?? $storeModel->default_currency ?? 'USD'),
            'weight_g' => null,
            'requires_shipping' => (bool) ($validated['requires_shipping'] ?? true),
            'is_default' => true,
            'position' => 1,
            'status' => 'active',
        ]);

        InventoryItem::query()->create([
            'store_id' => $storeId,
            'variant_id' => (int) $defaultVariant->id,
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
            'policy' => 'deny',
        ]);

        $product = Product::query()
            ->whereKey($product->id)
            ->withCount('variants')
            ->firstOrFail();

        return response()->json([
            'data' => ProductResource::make($product)->resolve(),
        ], 201);
    }

    public function show(Request $request, int $store, int $product): JsonResponse
    {
        $storeId = $this->scopedStoreId($request, $store);
        $foundProduct = $this->findStoreProduct($storeId, $product);

        if (! $foundProduct instanceof Product) {
            return response()->json([
                'message' => 'Product not found.',
            ], 404);
        }

        $foundProduct->loadCount('variants');

        return response()->json([
            'data' => ProductResource::make($foundProduct)->resolve(),
        ]);
    }

    public function update(UpdateProductRequest $request, int $store, int $product): JsonResponse
    {
        $storeId = $this->scopedStoreId($request, $store);
        $foundProduct = $this->findStoreProduct($storeId, $product);

        if (! $foundProduct instanceof Product) {
            return response()->json([
                'message' => 'Product not found.',
            ], 404);
        }

        $validated = $request->validated();
        $service = $this->resolveService('App\\Services\\ProductService');

        if ($service !== null && method_exists($service, 'update')) {
            try {
                $serviceProduct = $service->update($foundProduct, $validated);

                if ($serviceProduct instanceof Product) {
                    $serviceProduct->loadCount('variants');

                    return response()->json([
                        'data' => ProductResource::make($serviceProduct)->resolve(),
                    ]);
                }
            } catch (\Throwable) {
                // Fall back to direct persistence while service contracts stabilize.
            }
        }

        if (array_key_exists('handle', $validated) && is_string($validated['handle']) && $validated['handle'] !== '') {
            $validated['handle'] = $this->resolveUniqueHandle($storeId, $validated['handle'], (int) $foundProduct->id);
        }

        $payload = [];

        foreach (['title', 'handle', 'description_html', 'vendor', 'product_type', 'status', 'published_at'] as $field) {
            if (array_key_exists($field, $validated)) {
                $payload[$field] = $validated[$field];
            }
        }

        if (array_key_exists('tags', $validated)) {
            $payload['tags'] = is_array($validated['tags']) ? $validated['tags'] : [];
        }

        if ($payload !== []) {
            $foundProduct->fill($payload);
            $foundProduct->save();
        }

        if (
            array_key_exists('price_amount', $validated)
            || array_key_exists('compare_at_amount', $validated)
            || array_key_exists('currency', $validated)
            || array_key_exists('requires_shipping', $validated)
        ) {
            $variant = ProductVariant::query()
                ->where('product_id', $foundProduct->id)
                ->where('is_default', true)
                ->first();

            if (! $variant instanceof ProductVariant) {
                $variant = ProductVariant::query()->create([
                    'product_id' => (int) $foundProduct->id,
                    'sku' => null,
                    'barcode' => null,
                    'price_amount' => 0,
                    'compare_at_amount' => null,
                    'currency' => (string) ($validated['currency'] ?? $foundProduct->store->default_currency ?? 'USD'),
                    'weight_g' => null,
                    'requires_shipping' => true,
                    'is_default' => true,
                    'position' => 1,
                    'status' => 'active',
                ]);

                InventoryItem::query()->firstOrCreate([
                    'variant_id' => (int) $variant->id,
                ], [
                    'store_id' => (int) $foundProduct->store_id,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'policy' => 'deny',
                ]);
            }

            $variant->fill([
                'price_amount' => array_key_exists('price_amount', $validated)
                    ? (int) $validated['price_amount']
                    : (int) $variant->price_amount,
                'compare_at_amount' => array_key_exists('compare_at_amount', $validated)
                    ? (isset($validated['compare_at_amount']) ? (int) $validated['compare_at_amount'] : null)
                    : $variant->compare_at_amount,
                'currency' => array_key_exists('currency', $validated)
                    ? (string) $validated['currency']
                    : $variant->currency,
                'requires_shipping' => array_key_exists('requires_shipping', $validated)
                    ? (bool) $validated['requires_shipping']
                    : (bool) $variant->requires_shipping,
            ]);
            $variant->save();
        }

        $foundProduct->loadCount('variants');

        return response()->json([
            'data' => ProductResource::make($foundProduct)->resolve(),
        ]);
    }

    public function destroy(Request $request, int $store, int $product): JsonResponse
    {
        $storeId = $this->scopedStoreId($request, $store);
        $foundProduct = $this->findStoreProduct($storeId, $product);

        if (! $foundProduct instanceof Product) {
            return response()->json([
                'message' => 'Product not found.',
            ], 404);
        }

        $service = $this->resolveService('App\\Services\\ProductService');

        if ($service !== null) {
            try {
                if (method_exists($service, 'archive')) {
                    $service->archive($foundProduct);
                } elseif (method_exists($service, 'delete')) {
                    $service->delete($foundProduct);
                }
            } catch (\Throwable) {
                // Fall back to direct persistence while service contracts stabilize.
            }
        }

        $foundProduct->status = 'archived';
        $foundProduct->save();

        return response()->json([
            'data' => [
                'id' => (int) $foundProduct->id,
                'status' => (string) $this->enumValue($foundProduct->status),
                'updated_at' => $this->iso($foundProduct->updated_at),
            ],
        ]);
    }

    private function findStoreProduct(int $storeId, int $productId): ?Product
    {
        return Product::query()
            ->where('store_id', $storeId)
            ->whereKey($productId)
            ->first();
    }

    private function resolveUniqueHandle(int $storeId, string $source, ?int $ignoreProductId = null): string
    {
        $base = Str::slug($source);
        $base = $base !== '' ? $base : 'product';

        $candidate = $base;
        $suffix = 1;

        while (true) {
            $query = Product::query()
                ->where('store_id', $storeId)
                ->where('handle', $candidate);

            if ($ignoreProductId !== null) {
                $query->where('id', '!=', $ignoreProductId);
            }

            if (! $query->exists()) {
                return $candidate;
            }

            $candidate = $base.'-'.$suffix;
            $suffix++;
        }
    }
}
