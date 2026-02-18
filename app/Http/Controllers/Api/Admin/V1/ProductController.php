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

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService,
    ) {}

    public function index(Request $request, Store $store): JsonResponse
    {
        $this->authorizeStore($request, $store);

        $query = Product::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('query')) {
            $query->where('title', 'like', '%'.$request->input('query').'%');
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $products = $query->orderBy('updated_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => ProductResource::collection($products->items()),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    public function store(StoreProductRequest $request, Store $store): JsonResponse
    {
        $this->authorizeStore($request, $store);

        $product = $this->productService->create($store, $request->validated());

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Store $store, int $product): JsonResponse
    {
        $this->authorizeStore($request, $store);

        $productModel = Product::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($product);

        return (new ProductResource($productModel))->response();
    }

    public function update(UpdateProductRequest $request, Store $store, int $product): JsonResponse
    {
        $this->authorizeStore($request, $store);

        $productModel = Product::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($product);

        $productModel = $this->productService->update($productModel, $request->validated());

        return (new ProductResource($productModel))->response();
    }

    public function destroy(Request $request, Store $store, int $product): JsonResponse
    {
        $this->authorizeStore($request, $store);

        $productModel = Product::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($product);

        try {
            $this->productService->delete($productModel);
        } catch (\LogicException) {
            // If not draft, archive instead
            $this->productService->transitionStatus($productModel, \App\Enums\ProductStatus::Archived);
            $productModel->refresh();
        }

        return response()->json([
            'data' => [
                'id' => $productModel->id,
                'status' => $productModel->status->value,
                'updated_at' => $productModel->updated_at?->toIso8601String(),
            ],
        ]);
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $hasAccess = $user->stores()->where('stores.id', $store->id)->exists();

        if (! $hasAccess) {
            abort(403, 'You do not have access to this store.');
        }

        app()->instance('current_store', $store);
    }
}
