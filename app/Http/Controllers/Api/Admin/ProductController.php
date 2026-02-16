<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request, Store $store): JsonResponse
    {
        $this->authorize('view', $store);
        $this->authorize('viewAny', Product::class);

        $products = Product::where('store_id', $store->id)
            ->with('variants')
            ->paginate(20);

        return response()->json($products);
    }

    public function store(Request $request, Store $store): JsonResponse
    {
        $this->authorize('view', $store);
        $this->authorize('create', Product::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'handle' => 'required|string|max:255|unique:products,handle',
            'description_html' => 'nullable|string',
            'status' => 'sometimes|string|in:active,draft,archived',
            'vendor' => 'nullable|string|max:255',
            'product_type' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            ...$validated,
            'published_at' => ($validated['status'] ?? 'draft') === 'active' ? now() : null,
        ]);

        return response()->json($product, 201);
    }

    public function update(Request $request, Store $store, Product $product): JsonResponse
    {
        $this->authorize('view', $store);
        abort_unless($product->store_id === $store->id, 404);
        $this->authorize('update', $product);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'handle' => 'sometimes|string|max:255',
            'description_html' => 'nullable|string',
            'status' => 'sometimes|string|in:active,draft,archived',
            'vendor' => 'nullable|string|max:255',
            'product_type' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
        ]);

        $product->update($validated);

        return response()->json($product->fresh());
    }

    public function destroy(Request $request, Store $store, Product $product): JsonResponse
    {
        $this->authorize('view', $store);
        abort_unless($product->store_id === $store->id, 404);
        $this->authorize('delete', $product);

        $product->delete();

        return response()->json(null, 204);
    }
}
