<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function index(Request $request, int $storeId): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'in:draft,active,archived'],
            'query' => ['sometimes', 'string', 'max:200'],
            'collection_id' => ['sometimes', 'integer'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'in:title_asc,title_desc,created_at_asc,created_at_desc,updated_at_desc'],
        ]);
        [$sort, $direction] = match ($validated['sort'] ?? 'updated_at_desc') {
            'title_asc' => ['title', 'asc'], 'title_desc' => ['title', 'desc'],
            'created_at_asc' => ['created_at', 'asc'], 'created_at_desc' => ['created_at', 'desc'],
            default => ['updated_at', 'desc'],
        };
        $products = Product::withoutGlobalScopes()->where('store_id', $storeId)
            ->when(isset($validated['status']), fn ($query) => $query->where('status', $validated['status']))
            ->when(isset($validated['query']), fn ($query) => $query->where(function ($nested) use ($validated): void {
                $term = $validated['query'];
                $nested->where('title', 'like', "%{$term}%")->orWhere('vendor', 'like', "%{$term}%")
                    ->orWhereHas('variants', fn ($variants) => $variants->where('sku', 'like', "%{$term}%"));
            }))
            ->when(isset($validated['collection_id']), fn ($query) => $query->whereHas('collections', fn ($collections) => $collections->whereKey($validated['collection_id'])))
            ->with(['variants.inventoryItem', 'media', 'collections'])->orderBy($sort, $direction)->paginate($validated['per_page'] ?? 25);

        return response()->json(['data' => $products->items(), 'meta' => ['current_page' => $products->currentPage(), 'last_page' => $products->lastPage(), 'total' => $products->total()]]);
    }

    public function store(Request $request, int $storeId): JsonResponse
    {
        $data = $this->validated($request, $storeId);
        $product = $this->products->create(app('current_store'), $data);

        return response()->json(['data' => $product], 201);
    }

    public function show(int $storeId, int $productId): JsonResponse
    {
        return response()->json(['data' => $this->product($storeId, $productId)->load(['options.values', 'variants.optionValues', 'variants.inventoryItem', 'media', 'collections'])]);
    }

    public function update(Request $request, int $storeId, int $productId): JsonResponse
    {
        $data = $this->validated($request, $storeId, true);

        return response()->json(['data' => $this->products->update($this->product($storeId, $productId), $data)]);
    }

    public function destroy(int $storeId, int $productId): JsonResponse
    {
        $product = $this->product($storeId, $productId);
        $this->products->transitionStatus($product, ProductStatus::Archived);

        return response()->json(['message' => 'Product archived.', 'data' => $product->refresh()]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, int $storeId, bool $partial = false): array
    {
        $sometimes = $partial ? ['sometimes'] : ['required'];

        return $request->validate([
            'title' => [...$sometimes, 'string', 'max:255'],
            'handle' => ['sometimes', 'string', 'max:255', Rule::unique('products')->where('store_id', $storeId)->ignore($request->route('productId'))],
            'description_html' => ['nullable', 'string'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'product_type' => ['nullable', 'string', 'max:255'],
            'tags' => ['sometimes', 'array'],
            'status' => ['sometimes', 'in:draft,active,archived'],
            'options' => ['sometimes', 'array', 'max:3'],
            'options.*.name' => ['required_with:options', 'string', 'max:100'],
            'options.*.values' => ['required_with:options', 'array', 'min:1'],
            'variant' => ['sometimes', 'array'],
            'variant.price_amount' => ['sometimes', 'integer', 'min:0'],
            'variant.sku' => ['nullable', 'string', 'max:100'],
            'variant.quantity_on_hand' => ['sometimes', 'integer'],
        ]);
    }

    private function product(int $storeId, int $productId): Product
    {
        return Product::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($productId);
    }
}
