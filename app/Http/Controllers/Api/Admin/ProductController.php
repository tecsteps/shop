<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreProductRequest;
use App\Http\Requests\Api\Admin\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Store;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(Request $request, int $storeId): JsonResponse
    {
        $store = $this->resolveStore($request, $storeId);

        $products = Product::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->with('variants')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return ProductResource::collection($products)->response();
    }

    public function store(StoreProductRequest $request, int $storeId): JsonResponse
    {
        $store = $this->resolveStore($request, $storeId);
        $this->authorizeAbility($request, 'write-products');

        $product = $this->productService->create((int) $store->getKey(), $request->validated());

        return (new ProductResource($product->load('variants')))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $storeId, int $productId): JsonResponse
    {
        $store = $this->resolveStore($request, $storeId);

        $product = Product::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->with('variants', 'media')
            ->findOrFail($productId);

        return (new ProductResource($product))->response();
    }

    public function update(UpdateProductRequest $request, int $storeId, int $productId): JsonResponse
    {
        $store = $this->resolveStore($request, $storeId);
        $this->authorizeAbility($request, 'write-products');

        $product = Product::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->findOrFail($productId);

        $product = $this->productService->update($product, $request->validated());

        return (new ProductResource($product->load('variants')))->response();
    }

    public function destroy(Request $request, int $storeId, int $productId): JsonResponse
    {
        $store = $this->resolveStore($request, $storeId);
        $this->authorizeAbility($request, 'write-products');

        $product = Product::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->findOrFail($productId);

        $this->productService->delete($product);

        return response()->json(null, 204);
    }

    protected function resolveStore(Request $request, int $storeId): Store
    {
        $user = $request->user();
        $store = Store::query()->findOrFail($storeId);

        if ($user === null || ! $user->stores()->wherePivot('store_id', $store->getKey())->exists()) {
            abort(403);
        }

        app()->instance('current_store', $store);

        return $store;
    }

    protected function authorizeAbility(Request $request, string $ability): void
    {
        $token = $request->user()?->currentAccessToken();

        if ($token !== null && method_exists($token, 'can') && ! $token->can($ability) && ! $token->can('*')) {
            abort(403, 'Token missing required ability: '.$ability);
        }
    }
}
