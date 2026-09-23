<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSetting;
use App\Models\User;
use App\Services\FulfillmentService;
use App\Services\ProductService;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function me(int $storeId): JsonResponse
    {
        $store = $this->store($storeId);
        $user = request()->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roleForStore($store),
            'store' => $store,
            'abilities' => request()->attributes->get('api_token')?->abilities ?? [],
        ]);
    }

    public function products(Request $request, int $storeId): JsonResponse
    {
        $this->store($storeId);
        Gate::authorize('viewAny', Product::class);
        $query = Product::query()->with(['variants.inventoryItem', 'options.values', 'media']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('q')) {
            $text = addcslashes($request->string('q')->toString(), '%_\\');
            $query->where('title', 'like', "%{$text}%");
        }

        return response()->json($query->orderBy('title')->paginate(min(max((int) $request->integer('per_page', 25), 1), 100)));
    }

    public function createProduct(Request $request, int $storeId, ProductService $products): JsonResponse
    {
        $store = $this->store($storeId);
        Gate::authorize('create', Product::class);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255'],
            'description_html' => ['nullable', 'string', 'max:65535'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'product_type' => ['nullable', 'string', 'max:255'],
            'tags' => ['sometimes', 'array'],
            'status' => ['sometimes', Rule::enum(ProductStatus::class)],
            'options' => ['sometimes', 'array'],
            'options.*.name' => ['required_with:options', 'string', 'max:100'],
            'options.*.values' => ['required_with:options', 'array', 'min:1'],
            'options.*.values.*' => ['required', 'string', 'max:100'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.price_amount' => ['required', 'integer', 'min:0'],
            'variants.*.compare_at_amount' => ['nullable', 'integer', 'min:0'],
            'variants.*.quantity_on_hand' => ['sometimes', 'integer', 'min:0'],
            'variants.*.inventory_policy' => ['sometimes', Rule::in(['deny', 'continue'])],
            'variants.*.requires_shipping' => ['sometimes', 'boolean'],
        ]);
        $requestedStatus = ProductStatus::from($validated['status'] ?? 'draft');
        $validated['status'] = 'draft';
        $product = $products->create($store, $validated);

        if ($requestedStatus !== ProductStatus::Draft) {
            Gate::authorize($requestedStatus === ProductStatus::Archived ? 'archive-products' : 'manage-products');
            $products->transitionStatus($product, $requestedStatus);
        }

        return response()->json($product->load(['variants.inventoryItem', 'options.values']), 201);
    }

    public function product(int $storeId, int $productId): JsonResponse
    {
        $this->store($storeId);
        $product = Product::query()->with(['variants.inventoryItem', 'variants.optionValues.option', 'options.values', 'media', 'collections'])->findOrFail($productId);
        Gate::authorize('view', $product);

        return response()->json($product);
    }

    public function updateProduct(Request $request, int $storeId, int $productId, ProductService $products): JsonResponse
    {
        $this->store($storeId);
        $product = Product::query()->with('variants.inventoryItem')->findOrFail($productId);
        Gate::authorize('update', $product);
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'handle' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description_html' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'vendor' => ['sometimes', 'nullable', 'string', 'max:255'],
            'product_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tags' => ['sometimes', 'array'],
            'status' => ['sometimes', Rule::enum(ProductStatus::class)],
            'variants' => ['sometimes', 'array', 'min:1'],
            'variants.*.id' => ['sometimes', 'integer'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.price_amount' => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.compare_at_amount' => ['nullable', 'integer', 'min:0'],
            'variants.*.quantity_on_hand' => ['sometimes', 'integer', 'min:0'],
        ]);

        $updated = $products->update($product, $validated);

        foreach ($validated['variants'] ?? [] as $variantData) {
            $variant = isset($variantData['id'])
                ? $product->variants()->findOrFail($variantData['id'])
                : $product->variants()->create([
                    'sku' => $variantData['sku'] ?? null,
                    'price_amount' => $variantData['price_amount'],
                    'compare_at_amount' => $variantData['compare_at_amount'] ?? null,
                    'currency' => $this->store($storeId)->default_currency,
                    'is_default' => $product->variants()->doesntExist(),
                ]);
            abort_unless((int) $variant->product_id === (int) $product->id, 404);
            $variant->update(collect($variantData)->only(['sku', 'price_amount', 'compare_at_amount'])->all());

            if (array_key_exists('quantity_on_hand', $variantData)) {
                $variant->inventoryItem()->updateOrCreate([], ['store_id' => $storeId, 'quantity_on_hand' => $variantData['quantity_on_hand']]);
            }
        }

        if (isset($validated['status']) && $updated->status !== $validated['status']) {
            Gate::authorize($validated['status'] === 'archived' ? 'archive-products' : 'manage-products');
            $products->transitionStatus($updated, ProductStatus::from($validated['status']));
        }

        return response()->json($updated->refresh()->load(['variants.inventoryItem', 'options.values', 'media']));
    }

    public function deleteProduct(int $storeId, int $productId, ProductService $products): JsonResponse
    {
        $this->store($storeId);
        $product = Product::query()->findOrFail($productId);
        Gate::authorize('delete', $product);
        $products->delete($product);

        return response()->json([], 204);
    }

    public function collections(int $storeId): JsonResponse
    {
        $this->store($storeId);
        Gate::authorize('manage-products');

        return response()->json(Collection::query()->withCount('products')->orderBy('title')->paginate(50));
    }

    public function orders(Request $request, int $storeId): JsonResponse
    {
        $this->store($storeId);
        Gate::authorize('viewAny', Order::class);
        $query = Order::query()->withCount('lines')->latest('placed_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return response()->json($query->paginate(min(max((int) $request->integer('per_page', 25), 1), 100)));
    }

    public function order(int $storeId, int $orderId): JsonResponse
    {
        $this->store($storeId);
        $order = Order::query()->with('lines', 'payments', 'refunds.lines', 'fulfillments.lines')->findOrFail($orderId);
        Gate::authorize('view', $order);

        return response()->json($order);
    }

    public function fulfill(Request $request, int $storeId, int $orderId, FulfillmentService $fulfillments): JsonResponse
    {
        $this->store($storeId);
        $order = Order::query()->with('lines')->findOrFail($orderId);
        Gate::authorize('createFulfillment', $order);
        $validated = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*' => ['required', 'integer', 'min:1'],
            'tracking_company' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'tracking_url' => ['nullable', 'url', 'max:2048'],
        ]);
        $fulfillment = $fulfillments->create($order, $validated['lines'], $validated);

        return response()->json($fulfillment->load('lines.orderLine'), 201);
    }

    public function refund(Request $request, int $storeId, int $orderId, RefundService $refunds): JsonResponse
    {
        $this->store($storeId);
        $order = Order::query()->findOrFail($orderId);
        Gate::authorize('processRefund', $order);
        $validated = $request->validate([
            'amount' => ['sometimes', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:500'],
            'restock' => ['sometimes', 'boolean'],
            'lines' => ['sometimes', 'array'],
            'lines.*' => ['integer', 'min:1'],
        ]);
        $refund = $refunds->refund($order, (int) ($validated['amount'] ?? 0), $validated['reason'] ?? null, (bool) ($validated['restock'] ?? false), $validated['lines'] ?? []);

        return response()->json($refund->load('lines.orderLine'), 201);
    }

    public function discounts(int $storeId): JsonResponse
    {
        $this->store($storeId);
        Gate::authorize('manage-discounts');

        return response()->json(Discount::query()->orderBy('title')->paginate(50));
    }

    public function shippingZones(int $storeId): JsonResponse
    {
        $this->store($storeId);
        Gate::authorize('manage-shipping');

        return response()->json(ShippingZone::query()->with('rates')->orderBy('name')->get());
    }

    public function taxSettings(int $storeId): JsonResponse
    {
        $this->store($storeId);
        Gate::authorize('manage-store-settings');

        return response()->json(TaxSetting::query()->firstOrCreate(['store_id' => $storeId]));
    }

    public function updateTaxSettings(Request $request, int $storeId): JsonResponse
    {
        $this->store($storeId);
        Gate::authorize('manage-store-settings');
        $validated = $request->validate([
            'prices_include_tax' => ['required', 'boolean'],
            'default_rate' => ['required', 'integer', 'min:0', 'max:10000'],
            'rates_json' => ['sometimes', 'array'],
        ]);
        $settings = TaxSetting::query()->updateOrCreate(['store_id' => $storeId], [
            'prices_include_tax' => $validated['prices_include_tax'],
            'default_rate' => $validated['default_rate'],
            'rates_json' => $validated['rates_json'] ?? [],
            'updated_at' => now(),
        ]);

        return response()->json($settings);
    }

    public function pages(int $storeId): JsonResponse
    {
        $this->store($storeId);
        Gate::authorize('manage-store-settings');

        return response()->json(Page::query()->orderBy('title')->paginate(50));
    }

    public function reindexSearch(int $storeId): JsonResponse
    {
        $this->store($storeId);
        Gate::authorize('manage-store-settings');
        $products = Product::query()->get();

        foreach ($products as $product) {
            $product->saveQuietly();
        }

        return response()->json(['indexed' => $products->count()], 202);
    }

    public function searchStatus(int $storeId): JsonResponse
    {
        $this->store($storeId);
        Gate::authorize('manage-store-settings');

        return response()->json([
            'indexed_products' => DB::table('products_fts')->where('store_id', (string) $storeId)->count(),
            'last_reindexed_at' => DB::table('search_settings')->where('store_id', $storeId)->value('updated_at'),
        ]);
    }

    public function analyticsSummary(Request $request, int $storeId): JsonResponse
    {
        $this->store($storeId);
        Gate::authorize('view-analytics');
        $validated = $request->validate([
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
        ]);
        $start = $validated['from'] ?? now()->subDays(29)->toDateString();
        $end = $validated['to'] ?? now()->toDateString();
        $rows = DB::table('analytics_daily')->where('store_id', $storeId)->whereBetween('date', [$start, $end])->orderBy('date')->get();

        return response()->json([
            'from' => $start,
            'to' => $end,
            'orders_count' => (int) $rows->sum('orders_count'),
            'revenue_amount' => (int) $rows->sum('revenue_amount'),
            'visits_count' => (int) $rows->sum('visits_count'),
            'add_to_cart_count' => (int) $rows->sum('add_to_cart_count'),
            'checkout_started_count' => (int) $rows->sum('checkout_started_count'),
            'daily' => $rows,
        ]);
    }

    private function store(int $storeId): Store
    {
        $store = app('current_store');
        abort_unless($store instanceof Store && (int) $store->id === $storeId, 404);

        /** @var User $user */
        $user = request()->user();
        abort_unless($user->stores()->whereKey($store->id)->exists(), 403);

        return $store;
    }
}
