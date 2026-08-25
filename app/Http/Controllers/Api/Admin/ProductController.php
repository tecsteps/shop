<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(Request $request, int $storeId)
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::query()
            ->when($request->input('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->input('query'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->input('query').'%')
                    ->orWhere('vendor', 'like', '%'.$request->input('query').'%')
                    ->orWhereHas('variants', fn ($v) => $v->where('sku', 'like', '%'.$request->input('query').'%'));
            }))
            ->paginate($request->per_page ?? 25);

        return response()->json([
            'data' => $products->map(fn (Product $product) => [
                'id' => $product->id,
                'store_id' => $product->store_id,
                'title' => $product->title,
                'handle' => $product->handle,
                'status' => $product->status,
                'vendor' => $product->vendor,
                'product_type' => $product->product_type,
                'tags' => $product->tags,
                'variants_count' => $product->variants()->count(),
            ]),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    public function store(Request $request, int $storeId)
    {
        $this->authorize('create', Product::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description_html' => ['sometimes', 'nullable', 'string'],
            'vendor' => ['sometimes', 'nullable', 'string', 'max:255'],
            'product_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'in:draft,active'],
            'tags' => ['sometimes', 'array'],
            'price_amount' => ['sometimes', 'integer', 'min:0'],
            'quantity_on_hand' => ['sometimes', 'integer', 'min:0'],
            'sku' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $product = $this->productService->create(app('current_store'), $validated);

        return response()->json(['data' => ['id' => $product->id, 'title' => $product->title, 'handle' => $product->handle, 'status' => $product->status]], 201);
    }

    public function show(int $storeId, int $productId)
    {
        $product = Product::findOrFail($productId);
        $this->authorize('view', $product);

        return response()->json(['data' => [
            'id' => $product->id,
            'store_id' => $product->store_id,
            'title' => $product->title,
            'handle' => $product->handle,
            'description_html' => $product->description_html,
            'vendor' => $product->vendor,
            'product_type' => $product->product_type,
            'status' => $product->status,
            'tags' => $product->tags,
        ]]);
    }

    public function update(Request $request, int $storeId, int $productId)
    {
        $product = Product::findOrFail($productId);
        $this->authorize('update', $product);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'handle' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description_html' => ['sometimes', 'nullable', 'string'],
            'vendor' => ['sometimes', 'nullable', 'string', 'max:255'],
            'product_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'in:draft,active,archived'],
            'tags' => ['sometimes', 'array'],
        ]);

        $product = $this->productService->update($product, $validated);

        return response()->json(['data' => ['id' => $product->id, 'title' => $product->title, 'status' => $product->status]]);
    }

    public function destroy(int $storeId, int $productId)
    {
        $product = Product::findOrFail($productId);
        $this->authorize('delete', $product);

        $this->productService->delete($product);

        return response()->json(['data' => ['id' => $productId, 'status' => 'archived']]);
    }
}
