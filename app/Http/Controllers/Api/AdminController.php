<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateFulfillmentRequest;
use App\Http\Requests\CreateRefundRequest;
use App\Http\Requests\StoreCollectionRequest;
use App\Http\Requests\StoreDiscountRequest;
use App\Http\Requests\StorePageRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\StoreShippingRateRequest;
use App\Http\Requests\StoreShippingZoneRequest;
use App\Http\Requests\StoreThemeRequest;
use App\Http\Requests\UpdateCollectionRequest;
use App\Http\Requests\UpdateDiscountRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\UpdateShippingZoneRequest;
use App\Http\Requests\UpdateTaxSettingsRequest;
use App\Http\Requests\UpdateThemeSettingsRequest;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Models\Theme;
use App\Services\FulfillmentService;
use App\Services\ProductService;
use App\Services\RefundService;
use App\Services\SearchService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    public function storeProduct(StoreProductRequest $request, int $storeId, ProductService $products): JsonResponse
    {
        $this->assertStore($storeId);
        $data = $request->validated();
        $product = $products->create(app('current_store'), [...$data, 'status' => ProductStatus::from($data['status'] ?? ProductStatus::Draft->value)]);

        return response()->json(['data' => $product->load(['variants.inventory', 'variants.optionValues.option', 'options.values', 'media', 'collections'])->toArray()], 201);
    }

    public function showProduct(int $storeId, int $productId): JsonResponse
    {
        $this->assertStore($storeId);
        $product = Product::withoutGlobalScopes()->where('store_id', $storeId)->with(['variants.inventory', 'variants.optionValues.option', 'options.values', 'media', 'collections'])->findOrFail($productId);

        return response()->json(['data' => $product->toArray()]);
    }

    public function updateProduct(UpdateProductRequest $request, int $storeId, int $productId, ProductService $products): JsonResponse
    {
        $this->assertStore($storeId);
        $product = Product::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($productId);
        $data = $request->validated();

        return response()->json(['data' => $products->update($product, $data)->load(['variants.inventory', 'variants.optionValues.option', 'options.values', 'media', 'collections'])->toArray()]);
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

    public function storeCollection(StoreCollectionRequest $request, int $storeId): JsonResponse
    {
        $this->assertStore($storeId);
        $data = $request->validated();
        $collection = Collection::withoutGlobalScopes()->create(['store_id' => $storeId, 'title' => $data['title'], 'handle' => $data['handle'] ?? Str::slug($data['title']), 'description' => $data['description_html'] ?? null, 'status' => $data['status'] ?? 'active']);
        $this->syncCollectionProducts($collection, $data['product_ids'] ?? [], $storeId);

        return response()->json(['data' => $collection->load('products')->toArray()], 201);
    }

    public function updateCollection(UpdateCollectionRequest $request, int $storeId, int $collectionId): JsonResponse
    {
        $this->assertStore($storeId);
        $collection = Collection::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($collectionId);
        $data = $request->validated();
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

    public function storeDiscount(StoreDiscountRequest $request, int $storeId): JsonResponse
    {
        $this->assertStore($storeId);
        $data = $request->validated();
        $discount = Discount::withoutGlobalScopes()->create([...$data, 'store_id' => $storeId, 'status' => $data['status'] ?? 'active', 'usage_count' => 0]);

        return response()->json(['data' => $discount], 201);
    }

    public function updateDiscount(UpdateDiscountRequest $request, int $storeId, int $discountId): JsonResponse
    {
        $this->assertStore($storeId);
        $discount = Discount::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($discountId);
        $discount->update($request->validated());

        return response()->json(['data' => $discount->refresh()]);
    }

    public function deleteDiscount(int $storeId, int $discountId): JsonResponse
    {
        $this->assertStore($storeId);
        Discount::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($discountId)->delete();

        return response()->json(['message' => 'Discount deleted.']);
    }

    public function fulfillOrder(CreateFulfillmentRequest $request, int $storeId, int $orderId, FulfillmentService $fulfillments): JsonResponse
    {
        $this->assertStore($storeId);
        $order = Order::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($orderId);
        $data = $request->validated();
        $fulfillment = $fulfillments->create($order, $data['lines'], array_filter(['tracking_company' => $data['tracking_company'] ?? null, 'tracking_number' => $data['tracking_number'] ?? null, 'tracking_url' => $data['tracking_url'] ?? null]));

        return response()->json(['data' => $fulfillment->toArray()], 201);
    }

    public function refundOrder(CreateRefundRequest $request, int $storeId, int $orderId, RefundService $refunds): JsonResponse
    {
        $this->assertStore($storeId);
        $order = Order::withoutGlobalScopes()->where('store_id', $storeId)->with('payments')->findOrFail($orderId);
        $data = $request->validated();
        $payment = $order->payments->firstWhere('id', $data['payment_id'] ?? null) ?? $order->payments->firstWhere('status', 'captured');
        abort_unless($payment !== null, 422, 'No captured payment is available.');
        $refund = $refunds->create($order, $payment, $data['lines'] ?? $data['amount'] ?? null, $data['reason'] ?? null, (bool) ($data['restock'] ?? false), $data['lines'] ?? []);

        return response()->json(['data' => $refund->toArray()], 201);
    }

    public function shippingZones(int $storeId): JsonResponse
    {
        $this->assertStore($storeId);

        return response()->json(['data' => ShippingZone::withoutGlobalScopes()->where('store_id', $storeId)->with('rates')->get()]);
    }

    public function storeShippingZone(StoreShippingZoneRequest $request, int $storeId): JsonResponse
    {
        $this->assertStore($storeId);
        $data = $request->validated();

        return response()->json(['data' => ShippingZone::withoutGlobalScopes()->create([...$data, 'store_id' => $storeId])], 201);
    }

    public function updateShippingZone(UpdateShippingZoneRequest $request, int $storeId, int $zoneId): JsonResponse
    {
        $this->assertStore($storeId);
        $zone = ShippingZone::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($zoneId);
        $zone->update($request->validated());

        return response()->json(['data' => $zone->refresh()]);
    }

    public function storeShippingRate(StoreShippingRateRequest $request, int $storeId, int $zoneId): JsonResponse
    {
        $this->assertStore($storeId);
        ShippingZone::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($zoneId);
        $data = $request->validated();

        return response()->json(['data' => ShippingRate::create([...$data, 'shipping_zone_id' => $zoneId, 'is_active' => $data['is_active'] ?? true])], 201);
    }

    public function taxSettings(int $storeId): JsonResponse
    {
        $this->assertStore($storeId);

        return response()->json(['data' => TaxSettings::withoutGlobalScopes()->firstOrCreate(['store_id' => $storeId])]);
    }

    public function updateTaxSettings(UpdateTaxSettingsRequest $request, int $storeId): JsonResponse
    {
        $this->assertStore($storeId);
        $data = $request->validated();
        $settings = TaxSettings::withoutGlobalScopes()->updateOrCreate(['store_id' => $storeId], $data);

        return response()->json(['data' => $settings]);
    }

    public function pages(Request $request, int $storeId): JsonResponse
    {
        $this->assertStore($storeId);

        return $this->paginated(Page::withoutGlobalScopes()->where('store_id', $storeId)->latest()->paginate($request->integer('per_page', 25)));
    }

    public function storePage(StorePageRequest $request, int $storeId): JsonResponse
    {
        $this->assertStore($storeId);
        $data = $request->validated();
        $page = Page::withoutGlobalScopes()->create([...$data, 'store_id' => $storeId, 'handle' => $data['handle'] ?? Str::slug($data['title']), 'published_at' => $data['status'] === 'published' ? now() : null]);

        return response()->json(['data' => $page], 201);
    }

    public function updatePage(UpdatePageRequest $request, int $storeId, int $pageId): JsonResponse
    {
        $this->assertStore($storeId);
        $page = Page::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($pageId);
        $data = $request->validated();
        if (($data['status'] ?? null) === 'published') {
            $data['published_at'] = $page->published_at ?? now();
        }
        $page->update($data);

        return response()->json(['data' => $page->refresh()]);
    }

    public function deletePage(int $storeId, int $pageId): JsonResponse
    {
        $this->assertStore($storeId);
        Page::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($pageId)->delete();

        return response()->json(['message' => 'Page deleted.']);
    }

    public function storeTheme(StoreThemeRequest $request, int $storeId): JsonResponse
    {
        $this->assertStore($storeId);
        $data = $request->validated();
        $theme = Theme::withoutGlobalScopes()->create(['store_id' => $storeId, 'name' => $data['name'], 'version' => $data['version'] ?? null, 'status' => 'draft']);
        $theme->settings()->create(['settings_json' => $data['settings'] ?? []]);

        return response()->json(['data' => $theme->load('settings')], 201);
    }

    public function publishTheme(int $storeId, int $themeId): JsonResponse
    {
        $this->assertStore($storeId);
        $theme = Theme::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($themeId);
        Theme::withoutGlobalScopes()->where('store_id', $storeId)->update(['status' => 'draft']);
        $theme->update(['status' => 'published']);

        return response()->json(['data' => $theme->refresh()]);
    }

    public function updateThemeSettings(UpdateThemeSettingsRequest $request, int $storeId, int $themeId): JsonResponse
    {
        $this->assertStore($storeId);
        $theme = Theme::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($themeId);
        $theme->settings()->updateOrCreate(['theme_id' => $theme->getKey()], ['settings_json' => $request->validated()['settings']]);

        return response()->json(['data' => $theme->refresh()->load('settings')]);
    }

    public function reindex(SearchService $search, int $storeId): JsonResponse
    {
        $this->assertStore($storeId);
        Product::withoutGlobalScopes()->where('store_id', $storeId)->each(fn (Product $product) => $search->syncProduct($product));

        return response()->json(['status' => 'completed']);
    }

    public function searchStatus(int $storeId): JsonResponse
    {
        $this->assertStore($storeId);

        return response()->json(['status' => 'ready', 'product_count' => Product::withoutGlobalScopes()->where('store_id', $storeId)->count()]);
    }

    public function analyticsSummary(Request $request, int $storeId): JsonResponse
    {
        $this->assertStore($storeId);
        $days = \App\Models\AnalyticsDaily::withoutGlobalScopes()->where('store_id', $storeId)->whereBetween('date', [$request->input('from', now()->subDays(29)->toDateString()), $request->input('to', now()->toDateString())])->get();

        return response()->json(['data' => ['visits' => (int) $days->sum('visits_count'), 'orders' => (int) $days->sum('orders_count'), 'revenue_amount' => (int) $days->sum('revenue_amount'), 'checkout_completed' => (int) $days->sum('checkout_completed_count'), 'days' => $days]]);
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

    private function paginated(LengthAwarePaginator $paginator): JsonResponse
    {
        return response()->json(['data' => $paginator->items(), 'meta' => ['current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total(), 'last_page' => $paginator->lastPage()]]);
    }
}
