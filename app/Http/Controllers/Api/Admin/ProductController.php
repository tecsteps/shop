<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request, Store $store): JsonResponse
    {
        $this->authorizeStoreAccess($store);

        $query = Product::withoutGlobalScopes()
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
        match ($sort) {
            'title_asc' => $query->orderBy('title', 'asc'),
            'title_desc' => $query->orderBy('title', 'desc'),
            'created_at_asc' => $query->orderBy('created_at', 'asc'),
            'created_at_desc' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('updated_at', 'desc'),
        };

        $perPage = min((int) $request->input('per_page', 25), 100);
        $products = $query->paginate($perPage);

        return ProductListResource::collection($products)
            ->response();
    }

    public function show(Store $store, Product $product): ProductResource|JsonResponse
    {
        $this->authorizeStoreAccess($store);

        if ($product->store_id !== $store->id) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        return new ProductResource($product);
    }

    public function store(Request $request, Store $store): JsonResponse
    {
        $this->authorizeStoreAccess($store);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'handle' => 'nullable|string|max:255',
            'description_html' => 'nullable|string|max:65535',
            'vendor' => 'nullable|string|max:255',
            'product_type' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:draft,active',
            'tags' => 'nullable|array|max:50',
            'tags.*' => 'string|max:255',
        ]);

        $handle = $validated['handle'] ?? Str::slug($validated['title']);
        $status = $validated['status'] ?? 'draft';

        $product = Product::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'title' => $validated['title'],
            'handle' => $handle,
            'description_html' => $validated['description_html'] ?? null,
            'vendor' => $validated['vendor'] ?? null,
            'product_type' => $validated['product_type'] ?? null,
            'status' => ProductStatus::from($status),
            'tags' => $validated['tags'] ?? null,
        ]);

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Store $store, Product $product): ProductResource|JsonResponse
    {
        $this->authorizeStoreAccess($store);

        if ($product->store_id !== $store->id) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'handle' => 'sometimes|string|max:255',
            'description_html' => 'sometimes|nullable|string|max:65535',
            'vendor' => 'sometimes|nullable|string|max:255',
            'product_type' => 'sometimes|nullable|string|max:255',
            'status' => 'sometimes|string|in:draft,active,archived',
            'tags' => 'sometimes|nullable|array|max:50',
            'tags.*' => 'string|max:255',
        ]);

        $data = [];
        foreach (['title', 'handle', 'description_html', 'vendor', 'product_type', 'tags'] as $field) {
            if (array_key_exists($field, $validated)) {
                $data[$field] = $validated[$field];
            }
        }

        if (isset($validated['status'])) {
            $data['status'] = ProductStatus::from($validated['status']);
        }

        $product->update($data);

        return new ProductResource($product->fresh());
    }

    public function destroy(Store $store, Product $product): JsonResponse
    {
        $this->authorizeStoreAccess($store);

        if ($product->store_id !== $store->id) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $product->update(['status' => ProductStatus::Archived]);

        return response()->json([
            'data' => [
                'id' => $product->id,
                'status' => 'archived',
                'updated_at' => $product->fresh()->updated_at,
            ],
        ]);
    }

    private function authorizeStoreAccess(Store $store): void
    {
        $user = auth()->user();

        if (! $user || ! $user->stores()->where('stores.id', $store->id)->exists()) {
            abort(403, 'You do not have access to this store.');
        }
    }
}
