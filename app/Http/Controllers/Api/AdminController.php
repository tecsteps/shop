<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function products(Request $request, int $storeId): JsonResponse
    {
        $this->assertStore($storeId);
        $data = $request->validate(['status' => ['nullable', 'in:draft,active,archived'], 'query' => ['nullable', 'string'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $products = Product::withoutGlobalScopes()->where('store_id', $storeId)->with(['variants.inventory', 'collections'])->when($data['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))->when($data['query'] ?? null, fn ($query, string $queryText) => $query->where(function ($nested) use ($queryText): void {
            $nested->where('title', 'like', '%'.$queryText.'%')->orWhere('vendor', 'like', '%'.$queryText.'%')->orWhereHas('variants', fn ($variants) => $variants->where('sku', 'like', '%'.$queryText.'%'));
        }))->latest('updated_at')->paginate($data['per_page'] ?? 25);

        return $this->paginated($products);
    }

    public function storeProduct(Request $request, int $storeId, ProductService $products): JsonResponse
    {
        $this->assertStore($storeId);
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'handle' => ['nullable', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'vendor' => ['nullable', 'string', 'max:255'], 'product_type' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', 'in:draft,active,archived'], 'variants' => ['nullable', 'array']]);
        $product = $products->create(app('current_store'), [...$data, 'status' => ProductStatus::from($data['status'] ?? ProductStatus::Draft->value)]);

        return response()->json(['data' => $product->load('variants')->toArray()], 201);
    }

    public function showProduct(int $storeId, int $productId): JsonResponse
    {
        $this->assertStore($storeId);
        $product = Product::withoutGlobalScopes()->where('store_id', $storeId)->with(['variants.inventory', 'options.values', 'media', 'collections'])->findOrFail($productId);

        return response()->json(['data' => $product->toArray()]);
    }

    public function updateProduct(Request $request, int $storeId, int $productId, ProductService $products): JsonResponse
    {
        $this->assertStore($storeId);
        $product = Product::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($productId);
        $data = $request->validate(['title' => ['sometimes', 'string', 'max:255'], 'description' => ['sometimes', 'nullable', 'string'], 'vendor' => ['sometimes', 'nullable', 'string', 'max:255'], 'product_type' => ['sometimes', 'nullable', 'string', 'max:255'], 'status' => ['sometimes', 'in:draft,active,archived']]);

        return response()->json(['data' => $products->update($product, $data)->toArray()]);
    }

    public function deleteProduct(int $storeId, int $productId, ProductService $products): JsonResponse
    {
        $this->assertStore($storeId);
        $product = Product::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($productId);
        $products->transitionStatus($product, ProductStatus::Archived);

        return response()->json(['message' => 'Product archived']);
    }

    public function collections(Request $request, int $storeId): JsonResponse
    {
        $this->assertStore($storeId);
        $data = $request->validate(['query' => ['nullable', 'string'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $collections = Collection::withoutGlobalScopes()->where('store_id', $storeId)->withCount('products')->when($data['query'] ?? null, fn ($query, string $queryText) => $query->where('title', 'like', '%'.$queryText.'%'))->latest()->paginate($data['per_page'] ?? 25);

        return $this->paginated($collections);
    }

    public function storeCollection(Request $request, int $storeId): JsonResponse
    {
        $this->assertStore($storeId);
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'handle' => ['nullable', 'string', 'max:255'], 'description_html' => ['nullable', 'string'], 'status' => ['nullable', 'in:draft,active,archived'], 'product_ids' => ['nullable', 'array']]);
        $collection = Collection::withoutGlobalScopes()->create(['store_id' => $storeId, 'title' => $data['title'], 'handle' => $data['handle'] ?? Str::slug($data['title']), 'description' => $data['description_html'] ?? null, 'status' => $data['status'] ?? 'active']);
        $this->syncCollectionProducts($collection, $data['product_ids'] ?? [], $storeId);

        return response()->json(['data' => $collection->load('products')->toArray()], 201);
    }

    public function updateCollection(Request $request, int $storeId, int $collectionId): JsonResponse
    {
        $this->assertStore($storeId);
        $collection = Collection::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($collectionId);
        $data = $request->validate(['title' => ['sometimes', 'string', 'max:255'], 'description_html' => ['sometimes', 'nullable', 'string'], 'status' => ['sometimes', 'in:draft,active,archived'], 'product_ids' => ['sometimes', 'array']]);
        $collection->update(array_filter(['title' => $data['title'] ?? null, 'description' => $data['description_html'] ?? null, 'status' => $data['status'] ?? null], fn ($value): bool => $value !== null));

        if (array_key_exists('product_ids', $data)) {
            $this->syncCollectionProducts($collection, $data['product_ids'], $storeId);
        }

        return response()->json(['data' => $collection->load('products')->toArray()]);
    }

    public function deleteCollection(int $storeId, int $collectionId): JsonResponse
    {
        $this->assertStore($storeId);
        Collection::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($collectionId)->delete();

        return response()->json(['message' => 'Collection deleted']);
    }

    public function orders(Request $request, int $storeId): JsonResponse
    {
        $this->assertStore($storeId);
        $data = $request->validate(['status' => ['nullable', 'string'], 'financial_status' => ['nullable', 'string'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $orders = Order::withoutGlobalScopes()->where('store_id', $storeId)->with('customer')->when($data['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))->when($data['financial_status'] ?? null, fn ($query, string $status) => $query->where('financial_status', $status))->latest('placed_at')->paginate($data['per_page'] ?? 25);

        return $this->paginated($orders);
    }

    public function showOrder(int $storeId, int $orderId): JsonResponse
    {
        $this->assertStore($storeId);
        $order = Order::withoutGlobalScopes()->where('store_id', $storeId)->with(['customer', 'lines', 'payments', 'refunds', 'fulfillments.lines'])->findOrFail($orderId);

        return response()->json(['data' => $order->toArray()]);
    }

    public function customers(Request $request, int $storeId): JsonResponse
    {
        $this->assertStore($storeId);
        $data = $request->validate(['query' => ['nullable', 'string'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $customers = Customer::withoutGlobalScopes()->where('store_id', $storeId)->when($data['query'] ?? null, fn ($query, string $queryText) => $query->where(function ($nested) use ($queryText): void {
            $nested->where('email', 'like', '%'.$queryText.'%')->orWhere('first_name', 'like', '%'.$queryText.'%')->orWhere('last_name', 'like', '%'.$queryText.'%');
        }))->latest()->paginate($data['per_page'] ?? 25);

        return $this->paginated($customers);
    }

    public function discounts(Request $request, int $storeId): JsonResponse
    {
        $this->assertStore($storeId);

        return $this->paginated(Discount::withoutGlobalScopes()->where('store_id', $storeId)->latest()->paginate($request->integer('per_page', 25)));
    }

    private function assertStore(int $storeId): void
    {
        abort_unless((int) app('current_store')->getKey() === $storeId, 404);
    }

    private function syncCollectionProducts(Collection $collection, array $productIds, int $storeId): void
    {
        $validIds = Product::withoutGlobalScopes()->where('store_id', $storeId)->whereIn('id', $productIds)->pluck('id')->all();
        $collection->products()->sync(array_fill_keys($validIds, ['position' => 0]));
    }

    private function paginated($paginator): JsonResponse
    {
        return response()->json(['data' => $paginator->items(), 'meta' => ['current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total(), 'last_page' => $paginator->lastPage()]]);
    }
}
