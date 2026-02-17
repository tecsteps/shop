<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreProductRequest;
use App\Http\Requests\Api\Admin\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Store;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService,
    ) {}

    public function index(Request $request, int $storeId): AnonymousResourceCollection
    {
        $this->authorizeAbility($request, 'read-products');

        $store = Store::findOrFail($storeId);
        $this->authorizeStoreAccess($request, $store);

        $query = Product::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->withCount('variants');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('query')) {
            $search = $request->input('query');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('vendor', 'like', "%{$search}%");
            });
        }

        $sort = $request->input('sort', 'updated_at_desc');
        $query = match ($sort) {
            'title_asc' => $query->orderBy('title'),
            'title_desc' => $query->orderByDesc('title'),
            'created_at_asc' => $query->orderBy('created_at'),
            'created_at_desc' => $query->orderByDesc('created_at'),
            default => $query->orderByDesc('updated_at'),
        };

        $perPage = min((int) $request->input('per_page', 15), 100);

        return ProductResource::collection(
            $query->paginate($perPage)
        );
    }

    public function store(StoreProductRequest $request, int $storeId): JsonResponse
    {
        $this->authorizeAbility($request, 'write-products');

        $store = Store::findOrFail($storeId);
        $this->authorizeStoreAccess($request, $store);

        $product = $this->productService->create($store, $request->validated());

        $product->load('variants.inventoryItem');

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, int $storeId, int $productId): ProductResource
    {
        $this->authorizeAbility($request, 'read-products');

        $store = Store::findOrFail($storeId);
        $this->authorizeStoreAccess($request, $store);

        $product = Product::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->with('variants.inventoryItem', 'options', 'media', 'collections')
            ->findOrFail($productId);

        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, int $storeId, int $productId): ProductResource
    {
        $this->authorizeAbility($request, 'write-products');

        $store = Store::findOrFail($storeId);
        $this->authorizeStoreAccess($request, $store);

        $product = Product::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($productId);

        $product = $this->productService->update($product, $request->validated());

        $product->load('variants.inventoryItem');

        return new ProductResource($product);
    }

    public function destroy(Request $request, int $storeId, int $productId): JsonResponse
    {
        $this->authorizeAbility($request, 'write-products');

        $store = Store::findOrFail($storeId);
        $this->authorizeStoreAccess($request, $store);

        $product = Product::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($productId);

        try {
            $this->productService->delete($product);
        } catch (\App\Exceptions\InvalidProductTransitionException) {
            // Archive instead of delete for non-draft products
            $this->productService->update($product, ['status' => 'archived']);
            $product->refresh();
        }

        return response()->json([
            'data' => [
                'id' => $product->id,
                'status' => $product->status->value,
                'updated_at' => $product->updated_at?->toIso8601String(),
            ],
        ]);
    }

    private function authorizeAbility(Request $request, string $ability): void
    {
        $user = $request->user();

        if (! $user || ! $user->tokenCan($ability)) {
            abort(403, 'Insufficient permissions.');
        }
    }

    private function authorizeStoreAccess(Request $request, Store $store): void
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        $hasAccess = $user->stores()->where('stores.id', $store->id)->exists();

        if (! $hasAccess) {
            abort(403, 'You do not have access to this store.');
        }

        app()->instance('current_store', $store);
    }
}
