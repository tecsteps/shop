<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateFulfillmentRequest;
use App\Http\Requests\CreateOrderExportRequest;
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
use App\Jobs\GenerateOrderExport;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderExport;
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
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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

        $config = $data['config_json'];
        $price = (int) ($data['price_amount'] ?? $config['price_amount'] ?? 0);
        $currency = $data['currency'] ?? $config['currency'] ?? app('current_store')->default_currency;

        return response()->json(['data' => ShippingRate::create([...$data, 'shipping_zone_id' => $zoneId, 'price_amount' => $price, 'currency' => $currency, 'config_json' => $config, 'is_active' => $data['is_active'] ?? true])], 201);
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
        $settings = TaxSettings::withoutGlobalScopes()->updateOrCreate(['store_id' => $storeId], [...$data, 'provider_config_json' => $data['config_json']]);

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
        /** @var UploadedFile $file */
        $file = $request->file('file');
        $archive = new \ZipArchive;
        abort_unless($archive->open($file->getRealPath()) === true, 422, 'The theme archive is invalid.');
        $manifest = [];
        $paths = [];

        for ($index = 0; $index < $archive->numFiles; $index++) {
            $path = $archive->getNameIndex($index);
            abort_if($path === false || str_contains($path, '..') || str_starts_with($path, '/'), 422, 'The theme archive contains an invalid path.');
            if (! str_ends_with($path, '/')) {
                $paths[] = $path;
            }
            if ($path === 'theme.json') {
                $manifest = json_decode((string) $archive->getFromIndex($index), true) ?: [];
            }
        }
        abort_if($paths === [], 422, 'The theme archive is empty.');
        abort_if($manifest === [] || ! is_string($manifest['name'] ?? null) || ! is_string($manifest['version'] ?? null), 422, 'The theme archive has an invalid manifest.');
        abort_if(! collect($paths)->contains(fn (string $path): bool => str_starts_with($path, 'templates/') || str_ends_with($path, '.blade.php')), 422, 'The theme archive is missing storefront templates.');
        $theme = Theme::withoutGlobalScopes()->create(['store_id' => $storeId, 'name' => $data['name'] ?? ($manifest['name'] ?? 'Uploaded theme'), 'version' => $manifest['version'] ?? '1.0.0', 'status' => 'draft']);
        foreach ($paths as $path) {
            $contents = $archive->getFromName($path);
            $theme->files()->create(['path' => $path, 'storage_key' => 'themes/'.$theme->getKey().'/'.$path, 'sha256' => hash('sha256', (string) $contents), 'byte_size' => strlen((string) $contents), 'content' => $contents]);
        }
        $archive->close();
        $theme->settings()->create(['settings_json' => []]);

        return response()->json(['data' => $theme->load('settings')], 201);
    }

    public function publishTheme(int $storeId, int $themeId): JsonResponse
    {
        $this->assertStore($storeId);
        $theme = Theme::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($themeId);
        Theme::withoutGlobalScopes()->where('store_id', $storeId)->update(['status' => 'draft']);
        $theme->update(['status' => 'published', 'published_at' => now()]);

        return response()->json(['data' => $theme->refresh()]);
    }

    public function updateThemeSettings(UpdateThemeSettingsRequest $request, int $storeId, int $themeId): JsonResponse
    {
        $this->assertStore($storeId);
        $theme = Theme::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($themeId);
        $theme->settings()->updateOrCreate(['theme_id' => $theme->getKey()], ['settings_json' => $request->validated()['settings_json']]);

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
        $data = $request->validate(['from' => ['required', 'date_format:Y-m-d'], 'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'], 'granularity' => ['sometimes', 'in:day,week,month']]);
        $from = CarbonImmutable::createFromFormat('Y-m-d', $data['from'])->startOfDay();
        $to = CarbonImmutable::createFromFormat('Y-m-d', $data['to'])->endOfDay();
        abort_if($from->diffInDays($to) > 365, 422, 'The analytics range may not exceed 365 days.');
        $days = \App\Models\AnalyticsDaily::withoutGlobalScopes()->where('store_id', $storeId)->whereBetween('date', [$from->toDateString(), $to->toDateString()])->orderBy('date')->get();
        $orders = (int) $days->sum('orders_count');
        $revenue = (int) $days->sum('revenue_amount');
        $visits = (int) $days->sum('visits_count');
        $topProducts = DB::table('order_lines')->join('orders', 'orders.id', '=', 'order_lines.order_id')->where('orders.store_id', $storeId)->whereBetween('orders.placed_at', [$from, $to])->whereIn('orders.financial_status', ['paid', 'partially_refunded'])->select('order_lines.product_id', 'order_lines.product_title as title')->selectRaw('sum(order_lines.quantity) as units_sold')->selectRaw('sum(order_lines.line_total_amount) as revenue_amount')->groupBy('order_lines.product_id', 'order_lines.product_title')->orderByDesc('units_sold')->limit(10)->get();

        return response()->json(['data' => ['period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()], 'summary' => ['orders_count' => $orders, 'revenue_amount' => $revenue, 'aov_amount' => $orders > 0 ? intdiv($revenue, $orders) : 0, 'visits_count' => $visits, 'add_to_cart_count' => (int) $days->sum('add_to_cart_count'), 'checkout_started_count' => (int) $days->sum('checkout_started_count'), 'conversion_rate' => $visits > 0 ? round($orders / $visits, 4) : 0, 'currency' => app('current_store')->default_currency], 'daily' => $days, 'top_products' => $topProducts]]);
    }

    public function createOrderExport(CreateOrderExportRequest $request, int $storeId): JsonResponse
    {
        $this->assertStore($storeId);
        $data = $request->validated();
        $export = OrderExport::withoutGlobalScopes()->create(['store_id' => $storeId, 'format' => $data['format'] ?? 'csv', 'filters_json' => $data['filters'] ?? [], 'status' => 'queued']);
        GenerateOrderExport::dispatch($export);

        return response()->json(['export_id' => $export->getKey(), 'status' => 'queued', 'created_at' => $export->created_at], 202);
    }

    public function showOrderExport(int $storeId, int $exportId): JsonResponse
    {
        $this->assertStore($storeId);
        $export = OrderExport::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($exportId);

        return response()->json(['data' => ['id' => $export->getKey(), 'status' => $export->status, 'format' => $export->format, 'row_count' => $export->row_count, 'download_url' => $export->download_url, 'download_expires_at' => $export->download_expires_at, 'created_at' => $export->created_at, 'completed_at' => $export->completed_at, 'error_message' => $export->error_message]]);
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
