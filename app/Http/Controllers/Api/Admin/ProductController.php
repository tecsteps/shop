<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Store;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(Request $request, Store $store): AnonymousResourceCollection
    {
        $this->ensureStore($store);
        $products = Product::query()->with('variants')->when($request->filled('status'), fn (Builder $query): Builder => $query->where('status', $request->string('status')))->when($request->filled('search'), fn (Builder $query): Builder => $query->where('title', 'like', '%'.$request->string('search').'%'))->latest()->paginate(min(50, max(1, $request->integer('per_page', 15))));

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request, Store $store): JsonResponse
    {
        $this->ensureStore($store);

        return (new ProductResource($this->productService->create($store, $request->validated())))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Store $store, Product $product): ProductResource
    {
        $this->ensureRelated($store, $product);

        return new ProductResource($product->load(['options.values', 'variants.inventoryItem', 'collections', 'media']));
    }

    public function update(UpdateProductRequest $request, Store $store, Product $product): ProductResource
    {
        $this->ensureRelated($store, $product);

        return new ProductResource($this->productService->update($product, $request->validated()));
    }

    public function destroy(Store $store, Product $product): JsonResponse
    {
        $this->ensureRelated($store, $product);
        $this->productService->delete($product);

        return response()->json(['deleted' => true]);
    }

    private function ensureStore(Store $store): void
    {
        abort_unless($store->is(app('current_store')), Response::HTTP_NOT_FOUND);
    }

    private function ensureRelated(Store $store, Product $product): void
    {
        $this->ensureStore($store);
        abort_unless($product->store_id === $store->id, Response::HTTP_NOT_FOUND);
    }
}
