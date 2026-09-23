<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ReindexStoreProducts;
use App\Models\Collection;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSetting;
use App\Models\User;
use App\Services\AdminCollectionDiscountService;
use App\Services\AdminProductApiService;
use App\Services\FulfillmentService;
use App\Services\ProductService;
use App\Services\RefundService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function me(int $storeId): JsonResponse
    {
        $store = $this->store($storeId);
        $user = request()->user();
        $role = $user->roleForStore($store);

        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'store_id' => $store->id,
                'role' => $role,
                'email' => $user->email,
                'name' => $user->name,
                'permissions' => $this->permissionsForRole((string) $role),
            ],
        ]);
    }

    public function products(Request $request, int $storeId, AdminProductApiService $resources): JsonResponse
    {
        $store = $this->store($storeId);
        Gate::authorize('viewAny', Product::class);
        $filters = $request->validate([
            'status' => ['sometimes', 'string', Rule::in(['draft', 'active', 'archived'])],
            'query' => ['sometimes', 'string', 'max:255'],
            'collection_id' => [
                'sometimes', 'integer', 'min:1',
                Rule::exists('collections', 'id')->where(fn (QueryBuilder $query): QueryBuilder => $query->where('store_id', $store->getKey())),
            ],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'string', Rule::in(['title_asc', 'title_desc', 'created_at_asc', 'created_at_desc', 'updated_at_desc'])],
        ]);

        return response()->json($resources->list($store, $filters));
    }

    public function createProduct(Request $request, int $storeId, AdminProductApiService $products): JsonResponse
    {
        $store = $this->store($storeId);
        Gate::authorize('create', Product::class);
        $validated = $this->validateProductPayload($request, $storeId, true);
        $requestedStatus = ProductStatus::from($validated['status'] ?? 'draft');

        if ($requestedStatus === ProductStatus::Archived) {
            Gate::authorize('archive-products');
        } elseif ($requestedStatus === ProductStatus::Active) {
            Gate::authorize('manage-products');
        }

        return response()->json(['data' => $products->resource($products->create($store, $validated))], 201);
    }

    public function product(int $storeId, int $productId, AdminProductApiService $resources): JsonResponse
    {
        $this->store($storeId);
        $product = Product::query()->where('store_id', $storeId)->findOrFail($productId);
        Gate::authorize('view', $product);

        return response()->json(['data' => $resources->resource($product)]);
    }

    public function updateProduct(Request $request, int $storeId, int $productId, AdminProductApiService $products): JsonResponse
    {
        $store = $this->store($storeId);
        $product = Product::query()->where('store_id', $storeId)->findOrFail($productId);
        Gate::authorize('update', $product);
        $validated = $this->validateProductPayload($request, $storeId, false, $product);

        if (($validated['status'] ?? null) === ProductStatus::Archived->value) {
            Gate::authorize('archive-products');
        } elseif (isset($validated['status'])) {
            Gate::authorize('manage-products');
        }

        return response()->json(['data' => $products->resource($products->update($product, $store, $validated))]);
    }

    public function deleteProduct(int $storeId, int $productId, ProductService $products): JsonResponse
    {
        $this->store($storeId);
        $product = Product::query()->where('store_id', $storeId)->findOrFail($productId);
        Gate::authorize('archive-products');
        $products->transitionStatus($product, ProductStatus::Archived);

        return response()->json(['data' => [
            'id' => $product->getKey(),
            'status' => $product->refresh()->status,
            'updated_at' => $product->updated_at?->toISOString(),
        ]]);
    }

    public function collections(Request $request, int $storeId, AdminCollectionDiscountService $resources): JsonResponse
    {
        $store = $this->store($storeId);
        Gate::authorize('view-collections');
        $validated = $request->validate([
            'status' => ['sometimes', 'string', Rule::in(['draft', 'active', 'archived'])],
            'query' => ['sometimes', 'string', 'max:255'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json($resources->listCollections($store, $validated, $validated['per_page'] ?? 25));
    }

    public function createCollection(Request $request, int $storeId, AdminCollectionDiscountService $resources): JsonResponse
    {
        $store = $this->store($storeId);
        Gate::authorize('manage-products');
        $validated = $request->validate($this->collectionRules($storeId, false));
        $collection = $resources->createCollection($store, $validated);

        return response()->json($resources->collectionResource($collection), 201);
    }

    public function updateCollection(Request $request, int $storeId, int $collectionId, AdminCollectionDiscountService $resources): JsonResponse
    {
        $store = $this->store($storeId);
        Gate::authorize('manage-products');
        $collection = Collection::query()->where('store_id', $store->getKey())->findOrFail($collectionId);
        $validated = $request->validate($this->collectionRules($storeId, true, $collectionId));

        if (array_key_exists('product_ids', $validated)
            && (array_key_exists('add_product_ids', $validated) || array_key_exists('remove_product_ids', $validated))) {
            throw ValidationException::withMessages([
                'product_ids' => 'Use product_ids or the incremental product fields, not both.',
            ]);
        }

        return response()->json($resources->collectionResource($resources->updateCollection($collection, $validated)));
    }

    public function deleteCollection(int $storeId, int $collectionId, AdminCollectionDiscountService $resources): JsonResponse
    {
        $store = $this->store($storeId);
        Gate::authorize('manage-products');
        $collection = Collection::query()->where('store_id', $store->getKey())->findOrFail($collectionId);
        $resources->deleteCollection($collection);

        return response()->json(['message' => 'Collection deleted']);
    }

    public function orders(Request $request, int $storeId): JsonResponse
    {
        $store = $this->store($storeId);
        Gate::authorize('viewAny', Order::class);
        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['pending', 'paid', 'fulfilled', 'cancelled', 'refunded'])],
            'financial_status' => ['sometimes', Rule::in(['pending', 'paid', 'partially_refunded', 'refunded'])],
            'fulfillment_status' => ['sometimes', Rule::in(['unfulfilled', 'partial', 'fulfilled'])],
            'customer_id' => [
                'sometimes', 'integer', 'min:1',
                Rule::exists('customers', 'id')->where(fn (QueryBuilder $query): QueryBuilder => $query->where('store_id', $store->getKey())),
            ],
            'created_after' => ['sometimes', 'date'],
            'created_before' => ['sometimes', 'date', 'after_or_equal:created_after'],
            'query' => ['sometimes', 'string', 'max:255'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', Rule::in(['placed_at_desc', 'placed_at_asc', 'total_desc', 'total_asc'])],
        ]);
        $query = Order::query()->where('store_id', $store->getKey())->with(['customer'])->withCount('lines');

        foreach (['status', 'financial_status', 'fulfillment_status', 'customer_id'] as $filter) {
            if (isset($validated[$filter])) {
                $query->where($filter, $validated[$filter]);
            }
        }

        if (isset($validated['created_after'])) {
            $query->where('placed_at', '>=', CarbonImmutable::parse($validated['created_after'])->toDateTimeString());
        }

        if (isset($validated['created_before'])) {
            $query->where('placed_at', '<=', CarbonImmutable::parse($validated['created_before'])->toDateTimeString());
        }

        if (isset($validated['query'])) {
            $text = addcslashes($validated['query'], '%_\\');
            $query->where(function (\Illuminate\Database\Eloquent\Builder $builder) use ($text): void {
                $builder->where('order_number', 'like', "%{$text}%")
                    ->orWhere('email', 'like', "%{$text}%")
                    ->orWhereHas('customer', fn (\Illuminate\Database\Eloquent\Builder $customers) => $customers->where('email', 'like', "%{$text}%"));
            });
        }

        [$sortColumn, $sortDirection] = match ($validated['sort'] ?? 'placed_at_desc') {
            'placed_at_asc' => ['placed_at', 'asc'],
            'total_desc' => ['total_amount', 'desc'],
            'total_asc' => ['total_amount', 'asc'],
            default => ['placed_at', 'desc'],
        };
        $page = $validated['page'] ?? 1;
        $perPage = $validated['per_page'] ?? 25;
        $orders = $query->orderBy($sortColumn, $sortDirection)->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $orders->getCollection()->map(fn (Order $order): array => $this->orderListResource($order))->all(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    public function order(int $storeId, int $orderId): JsonResponse
    {
        $this->store($storeId);
        $order = Order::query()->where('store_id', $storeId)->with([
            'customer', 'lines', 'payments', 'refunds.lines', 'fulfillments.lines',
        ])->findOrFail($orderId);
        Gate::authorize('view', $order);

        return response()->json(['data' => $this->orderResource($order)]);
    }

    public function fulfill(Request $request, int $storeId, int $orderId, FulfillmentService $fulfillments): JsonResponse
    {
        $this->store($storeId);
        $order = Order::query()->where('store_id', $storeId)->with(['lines.fulfillmentLines'])->findOrFail($orderId);
        Gate::authorize('createFulfillment', $order);
        $validated = $request->validate([
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*' => ['required', 'array:order_line_id,quantity'],
            'line_items.*.order_line_id' => [
                'required', 'integer', 'distinct',
                Rule::exists('order_lines', 'id')->where(fn (QueryBuilder $query): QueryBuilder => $query->where('order_id', $order->getKey())),
            ],
            'line_items.*.quantity' => ['required', 'integer', 'min:1'],
            'tracking_company' => ['nullable', 'string', 'max:255'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'tracking_url' => ['nullable', 'url', 'max:2048'],
            'notify_customer' => ['sometimes', 'boolean'],
        ]);
        if (! in_array($order->financial_status, ['paid', 'partially_refunded'], true) || $order->fulfillment_status === 'fulfilled' || $order->status === 'cancelled') {
            return response()->json(['message' => 'The order is not in a fulfillable state.'], 409);
        }

        $lineQuantities = collect($validated['line_items'])->mapWithKeys(static fn (array $line): array => [(int) $line['order_line_id'] => (int) $line['quantity']])->all();
        $fulfillment = $fulfillments->create($order, $lineQuantities, $validated);
        $fulfillment = $fulfillments->markShipped($fulfillment, (bool) ($validated['notify_customer'] ?? true));

        return response()->json(['data' => [
            'id' => $fulfillment->getKey(),
            'order_id' => $fulfillment->order_id,
            'status' => $fulfillment->status,
            'tracking_company' => $fulfillment->tracking_company,
            'tracking_number' => $fulfillment->tracking_number,
            'tracking_url' => $fulfillment->tracking_url,
            'shipped_at' => $fulfillment->shipped_at?->toISOString(),
            'line_items' => $fulfillment->load('lines')->lines->map(static fn ($line): array => [
                'order_line_id' => $line->order_line_id,
                'quantity' => (int) $line->quantity,
            ])->all(),
        ]], 201);
    }

    public function refund(Request $request, int $storeId, int $orderId, RefundService $refunds): JsonResponse
    {
        $this->store($storeId);
        $order = Order::query()->where('store_id', $storeId)->findOrFail($orderId);
        Gate::authorize('processRefund', $order);
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'line_items' => ['sometimes', 'array'],
            'line_items.*' => ['required', 'array:order_line_id,quantity'],
            'line_items.*.order_line_id' => [
                'required', 'integer', 'distinct',
                Rule::exists('order_lines', 'id')->where(fn (QueryBuilder $query): QueryBuilder => $query->where('order_id', $order->getKey())),
            ],
            'line_items.*.quantity' => ['required', 'integer', 'min:1'],
            'notify_customer' => ['sometimes', 'boolean'],
        ]);
        if (! in_array($order->financial_status, ['paid', 'partially_refunded'], true)) {
            return response()->json(['message' => 'The order cannot be refunded.'], 409);
        }

        $lineQuantities = collect($validated['line_items'] ?? [])->mapWithKeys(static fn (array $line): array => [(int) $line['order_line_id'] => (int) $line['quantity']])->all();
        $refund = $refunds->refund(
            $order,
            (int) $validated['amount'],
            $validated['reason'] ?? null,
            false,
            $lineQuantities,
            (bool) ($validated['notify_customer'] ?? true),
        );

        return response()->json(['data' => [
            'id' => $refund->getKey(),
            'order_id' => $refund->order_id,
            'payment_id' => $refund->payment_id,
            'provider_refund_id' => $refund->provider_refund_id,
            'amount' => (int) $refund->amount,
            'reason' => $refund->reason,
            'status' => $refund->status === 'processed' ? 'completed' : $refund->status,
            'created_at' => $refund->created_at?->toISOString(),
        ]], 201);
    }

    public function discounts(Request $request, int $storeId, AdminCollectionDiscountService $resources): JsonResponse
    {
        $store = $this->store($storeId);
        Gate::authorize('view-discounts');
        $validated = $request->validate([
            'type' => ['sometimes', 'string', Rule::in(['code', 'automatic'])],
            'status' => ['sometimes', 'string', Rule::in(['active', 'expired', 'scheduled'])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json($resources->listDiscounts($store, $validated, $validated['per_page'] ?? 25));
    }

    public function createDiscount(Request $request, int $storeId, AdminCollectionDiscountService $resources): JsonResponse
    {
        $store = $this->store($storeId);
        Gate::authorize('manage-discounts');
        $this->normalizeDiscountCode($request);
        $validated = $request->validate($this->discountRules($request, $storeId, true));
        if (($validated['type'] ?? null) === 'automatic' && filled($validated['code'] ?? null)) {
            throw ValidationException::withMessages(['code' => 'Automatic discounts must not have a code.']);
        }

        $this->validateDiscountDates($validated);
        $discount = $resources->createDiscount($store, $validated);

        return response()->json($resources->discountResource($discount), 201);
    }

    public function updateDiscount(Request $request, int $storeId, int $discountId, AdminCollectionDiscountService $resources): JsonResponse
    {
        $store = $this->store($storeId);
        Gate::authorize('manage-discounts');
        $discount = Discount::query()->where('store_id', $store->getKey())->findOrFail($discountId);
        $this->normalizeDiscountCode($request);
        $validated = $request->validate($this->discountRules($request, $storeId, false, $discount));
        $requestedType = $validated['type'] ?? ($discount->code === null ? 'automatic' : 'code');
        $currentType = $discount->code === null ? 'automatic' : 'code';

        if ($requestedType !== $currentType) {
            throw ValidationException::withMessages(['type' => 'The discount type cannot be changed after creation.']);
        }

        if (array_key_exists('code', $validated) && $validated['code'] !== $discount->code) {
            throw ValidationException::withMessages(['code' => 'The discount code cannot be changed after creation.']);
        }

        $this->validateEffectiveDiscountValue($validated, $discount);
        $this->validateDiscountDates($validated, $discount);

        return response()->json($resources->discountResource($resources->updateDiscount($discount, $validated)));
    }

    public function deleteDiscount(int $storeId, int $discountId, AdminCollectionDiscountService $resources): JsonResponse
    {
        $store = $this->store($storeId);
        Gate::authorize('manage-discounts');
        $discount = Discount::query()->where('store_id', $store->getKey())->findOrFail($discountId);
        $resources->deleteDiscount($discount);

        return response()->json(['message' => 'Discount deleted']);
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
        $settings = TaxSetting::query()->firstOrCreate(['store_id' => $storeId]);

        return response()->json(['data' => [
            'store_id' => $storeId,
            'mode' => $settings->mode ?? 'manual',
            'provider' => $settings->provider ?? 'none',
            'prices_include_tax' => (bool) $settings->prices_include_tax,
            'config_json' => $settings->taxConfiguration(),
            'updated_at' => $settings->updated_at,
        ]]);
    }

    public function updateTaxSettings(Request $request, int $storeId): JsonResponse
    {
        $this->store($storeId);
        Gate::authorize('manage-store-settings');
        $this->requireJsonObjectField($request, 'config_json');
        $validated = $request->validate([
            'mode' => ['required', Rule::in(['manual', 'provider'])],
            'provider' => ['required_if:mode,provider', 'nullable', Rule::in(['stripe_tax', 'none'])],
            'prices_include_tax' => ['required', 'boolean'],
            'config_json' => ['required', 'array:default_tax_rate,tax_rates,fallback,stripe_tax_settings_id'],
            'config_json.default_tax_rate' => ['sometimes', 'integer', 'min:0', 'max:10000'],
            'config_json.tax_rates' => ['sometimes', 'array', 'max:250'],
            'config_json.tax_rates.*' => ['required', 'array:country_code,province_code,rate,name,shipping_taxed'],
            'config_json.tax_rates.*.country_code' => ['required', 'string', 'size:2', 'alpha', 'uppercase'],
            'config_json.tax_rates.*.province_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'config_json.tax_rates.*.rate' => ['required', 'integer', 'min:0', 'max:10000'],
            'config_json.tax_rates.*.name' => ['sometimes', 'string', 'max:100'],
            'config_json.tax_rates.*.shipping_taxed' => ['sometimes', 'boolean'],
            'config_json.fallback' => ['sometimes', Rule::in(['block', 'allow'])],
            'config_json.stripe_tax_settings_id' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);
        if (($validated['mode'] ?? null) === 'provider' && empty($validated['provider'])) {
            throw ValidationException::withMessages(['provider' => 'The provider field is required when tax mode is provider.']);
        }

        $config = $validated['config_json'];
        $legacyRates = [];

        foreach ($config['tax_rates'] ?? [] as $taxRate) {
            $countryCode = strtoupper($taxRate['country_code']);
            $provinceCode = strtoupper((string) ($taxRate['province_code'] ?? ''));

            if ($provinceCode === '') {
                $legacyRates[$countryCode] = (int) $taxRate['rate'];
            } else {
                $legacyRates[$countryCode][$provinceCode] = (int) $taxRate['rate'];
            }
        }

        $settings = TaxSetting::query()->updateOrCreate(['store_id' => $storeId], [
            'mode' => $validated['mode'],
            'provider' => $validated['provider'] ?? 'none',
            'prices_include_tax' => $validated['prices_include_tax'],
            'config_json' => $config,
            'default_rate' => $config['default_tax_rate'] ?? 0,
            'rates_json' => $legacyRates,
            'updated_at' => now(),
        ]);

        return response()->json(['data' => $settings->only([
            'store_id', 'mode', 'provider', 'prices_include_tax', 'config_json', 'updated_at',
        ])]);
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

        $wasQueued = DB::transaction(function () use ($storeId): bool {
            DB::table('search_settings')->insertOrIgnore(['store_id' => $storeId]);
            $settings = DB::table('search_settings')->where('store_id', $storeId)->lockForUpdate()->first();

            if (in_array($settings->index_status, ['queued', 'processing'], true)) {
                return false;
            }

            DB::table('search_settings')->where('store_id', $storeId)->update([
                'index_status' => 'queued',
                'pending_updates' => Product::query()->count(),
            ]);

            return true;
        });

        if (! $wasQueued) {
            return response()->json(['message' => 'A search reindex is already in progress.'], 409);
        }

        $jobId = 'job_reindex_'.Str::lower(Str::random(16));
        ReindexStoreProducts::dispatch($storeId);

        return response()->json([
            'message' => 'Reindex job queued.',
            'job_id' => $jobId,
            'status' => 'queued',
        ], 202);
    }

    public function searchStatus(int $storeId): JsonResponse
    {
        $this->store($storeId);
        Gate::authorize('manage-store-settings');
        DB::table('search_settings')->insertOrIgnore(['store_id' => $storeId]);
        $settings = DB::table('search_settings')->where('store_id', $storeId)->first();

        return response()->json([
            'data' => [
                'store_id' => $storeId,
                'index_status' => $settings->index_status,
                'last_reindex_at' => $settings->last_reindex_at,
                'last_reindex_duration_seconds' => $settings->last_reindex_duration_seconds,
                'documents_count' => (int) $settings->documents_count,
                'pending_updates' => (int) $settings->pending_updates,
            ],
        ]);
    }

    public function analyticsSummary(Request $request, int $storeId): JsonResponse
    {
        $this->store($storeId);
        Gate::authorize('view-analytics');
        $validated = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'granularity' => ['sometimes', Rule::in(['day', 'week', 'month'])],
        ]);
        $start = CarbonImmutable::parse($validated['from'])->startOfDay();
        $end = CarbonImmutable::parse($validated['to'])->startOfDay();

        if ($start->diffInDays($end) > 364) {
            throw ValidationException::withMessages(['to' => 'The date range may not exceed 365 days.']);
        }

        $rows = DB::table('analytics_daily')
            ->where('store_id', $storeId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get();
        $ordersCount = (int) $rows->sum('orders_count');
        $revenueAmount = (int) $rows->sum('revenue_amount');
        $visitsCount = (int) $rows->sum('visits_count');
        $granularity = $validated['granularity'] ?? 'day';
        $daily = $rows->groupBy(function (object $row) use ($granularity): string {
            $date = CarbonImmutable::parse($row->date);

            return match ($granularity) {
                'week' => $date->startOfWeek(CarbonImmutable::MONDAY)->toDateString(),
                'month' => $date->startOfMonth()->toDateString(),
                default => $date->toDateString(),
            };
        })->map(function ($bucket, string $date): array {
            $orders = (int) $bucket->sum('orders_count');
            $revenue = (int) $bucket->sum('revenue_amount');

            return [
                'date' => $date,
                'orders_count' => $orders,
                'revenue_amount' => $revenue,
                'aov_amount' => $orders > 0 ? intdiv($revenue, $orders) : 0,
                'visits_count' => (int) $bucket->sum('visits_count'),
                'add_to_cart_count' => (int) $bucket->sum('add_to_cart_count'),
                'checkout_started_count' => (int) $bucket->sum('checkout_started_count'),
            ];
        })->values();
        $topProducts = DB::table('order_lines')
            ->join('orders', 'orders.id', '=', 'order_lines.order_id')
            ->where('orders.store_id', $storeId)
            ->whereBetween(DB::raw('date(orders.placed_at)'), [$start->toDateString(), $end->toDateString()])
            ->whereIn('orders.financial_status', ['paid', 'partially_refunded'])
            ->select('order_lines.product_id')
            ->selectRaw('order_lines.title_snapshot AS title')
            ->selectRaw('SUM(order_lines.quantity) AS units_sold')
            ->selectRaw('SUM(order_lines.total_amount) AS revenue_amount')
            ->groupBy('order_lines.product_id', 'order_lines.title_snapshot')
            ->orderByDesc('revenue_amount')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => [
                'period' => ['from' => $start->toDateString(), 'to' => $end->toDateString()],
                'summary' => [
                    'orders_count' => $ordersCount,
                    'revenue_amount' => $revenueAmount,
                    'aov_amount' => $ordersCount > 0 ? intdiv($revenueAmount, $ordersCount) : 0,
                    'visits_count' => $visitsCount,
                    'add_to_cart_count' => (int) $rows->sum('add_to_cart_count'),
                    'checkout_started_count' => (int) $rows->sum('checkout_started_count'),
                    'conversion_rate' => $visitsCount > 0 ? round($ordersCount / $visitsCount, 4) : 0.0,
                    'currency' => $this->store($storeId)->default_currency,
                ],
                'daily' => $daily,
                'top_products' => $topProducts,
            ],
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    private function collectionRules(int $storeId, bool $isUpdate, ?int $collectionId = null): array
    {
        $handleRule = Rule::unique('collections', 'handle')
            ->where(fn (QueryBuilder $query): QueryBuilder => $query->where('store_id', $storeId));

        if ($collectionId !== null) {
            $handleRule->ignore($collectionId);
        }

        $productIds = ['array'];
        $productIdsItem = [
            'required',
            'integer',
            'distinct',
            Rule::exists('products', 'id')->where(fn (QueryBuilder $query): QueryBuilder => $query->where('store_id', $storeId)),
        ];

        return [
            'title' => [$isUpdate ? 'sometimes' : 'required', 'required', 'string', 'max:255'],
            'handle' => ['sometimes', 'nullable', 'string', 'max:255', $handleRule],
            'description_html' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'type' => [$isUpdate ? 'sometimes' : 'required', 'required', 'string', Rule::in(['manual', 'automated'])],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'active', 'archived'])],
            'product_ids' => $productIds,
            'product_ids.*' => $productIdsItem,
            'add_product_ids' => ['sometimes', 'array'],
            'add_product_ids.*' => $productIdsItem,
            'remove_product_ids' => ['sometimes', 'array'],
            'remove_product_ids.*' => $productIdsItem,
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function discountRules(Request $request, int $storeId, bool $isCreate, ?Discount $discount = null): array
    {
        $type = $request->input('type', $discount?->code === null ? 'automatic' : 'code');
        $valueType = $request->input('value_type', match ($discount?->type) {
            'percentage' => 'percent',
            'fixed_amount' => 'fixed',
            'free_shipping' => 'free_shipping',
            default => null,
        });
        $codeRule = Rule::unique('discounts', 'code')
            ->where(fn (QueryBuilder $query): QueryBuilder => $query->where('store_id', $storeId));

        if ($discount !== null) {
            $codeRule->ignore($discount->getKey());
        }

        $valueAmount = [$isCreate ? 'required' : 'sometimes', 'required', 'integer'];

        if ($valueType === 'percent') {
            $valueAmount[] = 'min:1';
            $valueAmount[] = 'max:100';
        } elseif ($valueType === 'fixed') {
            $valueAmount[] = 'min:1';
        }

        return [
            'type' => [$isCreate ? 'required' : 'sometimes', 'required', 'string', Rule::in(['code', 'automatic'])],
            'code' => [$isCreate ? Rule::requiredIf($type === 'code') : 'sometimes', 'nullable', 'string', 'max:50', $codeRule],
            'value_type' => [$isCreate ? 'required' : 'sometimes', 'required', 'string', Rule::in(['percent', 'fixed', 'free_shipping'])],
            'value_amount' => $valueAmount,
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date'],
            'usage_limit' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'rules_json' => ['sometimes', 'array:minimum_purchase_amount,applicable_product_ids,applicable_collection_ids,customer_eligibility,once_per_customer'],
            'rules_json.minimum_purchase_amount' => ['sometimes', 'integer', 'min:0'],
            'rules_json.applicable_product_ids' => ['sometimes', 'array'],
            'rules_json.applicable_product_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('products', 'id')->where(fn (QueryBuilder $query): QueryBuilder => $query->where('store_id', $storeId)),
            ],
            'rules_json.applicable_collection_ids' => ['sometimes', 'array'],
            'rules_json.applicable_collection_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('collections', 'id')->where(fn (QueryBuilder $query): QueryBuilder => $query->where('store_id', $storeId)),
            ],
            'rules_json.customer_eligibility' => ['sometimes', 'string', Rule::in(['all', 'specific_customers', 'specific_segments'])],
            'rules_json.once_per_customer' => ['sometimes', 'boolean'],
        ];
    }

    private function normalizeDiscountCode(Request $request): void
    {
        if ($request->exists('code') && is_string($request->input('code'))) {
            $request->merge(['code' => mb_strtoupper(trim($request->input('code')))]);
        }
    }

    /** @param array<string, mixed> $validated */
    private function validateDiscountDates(array $validated, ?Discount $discount = null): void
    {
        $startsAt = array_key_exists('starts_at', $validated) ? $validated['starts_at'] : $discount?->starts_at;
        $endsAt = array_key_exists('ends_at', $validated) ? $validated['ends_at'] : $discount?->ends_at;

        if ($startsAt !== null && $endsAt !== null
            && CarbonImmutable::parse($endsAt)->lessThanOrEqualTo(CarbonImmutable::parse($startsAt))) {
            throw ValidationException::withMessages(['ends_at' => 'The end date must be after the start date.']);
        }
    }

    /** @param array<string, mixed> $validated */
    private function validateEffectiveDiscountValue(array $validated, Discount $discount): void
    {
        $valueType = $validated['value_type'] ?? match ($discount->type) {
            'percentage' => 'percent',
            'fixed_amount' => 'fixed',
            'free_shipping' => 'free_shipping',
            default => $discount->type,
        };

        if ($valueType === 'free_shipping') {
            return;
        }

        $amount = (int) ($validated['value_amount'] ?? $discount->value);
        $isValid = $valueType === 'percent'
            ? $amount >= 1 && $amount <= 100
            : $amount >= 1;

        if (! $isValid) {
            throw ValidationException::withMessages([
                'value_amount' => $valueType === 'percent'
                    ? 'The percentage discount must be between 1 and 100.'
                    : 'The fixed discount amount must be at least 1 minor unit.',
            ]);
        }
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

    private function requireJsonObjectField(Request $request, string $field): void
    {
        $body = json_decode($request->getContent());

        if (! is_object($body) || ! isset($body->{$field}) || ! is_object($body->{$field})) {
            throw ValidationException::withMessages([$field => "The {$field} field must be an object."]);
        }
    }

    /** @return array<string, mixed> */
    private function validateProductPayload(Request $request, int $storeId, bool $creating, ?Product $product = null): array
    {
        $submittedVariants = $request->input('variants');

        foreach (is_array($submittedVariants) ? $submittedVariants : [] as $index => $variant) {
            if (is_array($variant) && isset($variant['currency']) && is_string($variant['currency'])) {
                $request->merge([
                    'variants' => collect($request->input('variants'))->map(function (mixed $item, int $position) use ($index, $variant): mixed {
                        if ($position === $index && is_array($item)) {
                            $item['currency'] = strtoupper($variant['currency']);
                        }

                        return $item;
                    })->all(),
                ]);
            }
        }

        $handleRule = Rule::unique('products', 'handle')->where(fn (QueryBuilder $query): QueryBuilder => $query->where('store_id', $storeId));

        if ($product !== null) {
            $handleRule->ignore($product->getKey());
        }

        $rules = [
            'title' => [$creating ? 'required' : 'sometimes', 'required', 'string', 'max:255'],
            'handle' => ['sometimes', 'nullable', 'string', 'max:255', $handleRule],
            'description_html' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'vendor' => ['sometimes', 'nullable', 'string', 'max:255'],
            'product_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tags' => ['sometimes', 'array', 'max:50'],
            'tags.*' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', 'string', Rule::in($creating ? ['draft', 'active'] : ['draft', 'active', 'archived'])],
            'options' => ['sometimes', 'array', 'max:3'],
            'options.*' => ['required', 'array:name,position,values'],
            'options.*.name' => ['required', 'string', 'max:255'],
            'options.*.position' => ['required', 'integer', 'min:1', 'max:3', 'distinct'],
            'options.*.values' => ['sometimes', 'array', 'min:1', 'max:100'],
            'options.*.values.*' => ['required', 'string', 'max:255', 'distinct:ignore_case'],
            'collections' => ['sometimes', 'array', 'max:100'],
            'collections.*' => [
                'required', 'integer', 'distinct',
                Rule::exists('collections', 'id')->where(fn (QueryBuilder $query): QueryBuilder => $query->where('store_id', $storeId)),
            ],
            'variants' => [$creating ? 'required' : 'sometimes', 'array', 'min:1', 'max:100'],
            'variants.*' => ['required', 'array:id,sku,barcode,price_amount,compare_at_amount,currency,weight_g,requires_shipping,is_default,position,status,option_values,inventory'],
            'variants.*.id' => ['sometimes', 'integer', $product === null ? 'prohibited' : Rule::exists('product_variants', 'id')->where(fn (QueryBuilder $query): QueryBuilder => $query->where('product_id', $product->getKey()))],
            'variants.*.sku' => [$creating ? 'required' : 'sometimes', 'required', 'string', 'max:255', 'distinct'],
            'variants.*.barcode' => ['sometimes', 'nullable', 'string', 'max:255'],
            'variants.*.price_amount' => [$creating ? 'required' : 'sometimes', 'required', 'integer', 'min:0'],
            'variants.*.compare_at_amount' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'variants.*.currency' => ['sometimes', 'string', 'size:3', Rule::in([strtoupper((string) app('current_store')->default_currency)])],
            'variants.*.weight_g' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'variants.*.requires_shipping' => ['sometimes', 'boolean'],
            'variants.*.is_default' => ['sometimes', 'boolean'],
            'variants.*.position' => ['sometimes', 'integer', 'min:1', 'max:100', 'distinct'],
            'variants.*.status' => ['sometimes', Rule::in(['active', 'archived'])],
            'variants.*.option_values' => ['sometimes', 'array', 'max:3'],
            'variants.*.option_values.*' => ['required', 'array:option_name,value'],
            'variants.*.option_values.*.option_name' => ['required', 'string', 'max:255'],
            'variants.*.option_values.*.value' => ['required', 'string', 'max:255'],
            'variants.*.inventory' => ['sometimes', 'array:quantity_on_hand,policy'],
            'variants.*.inventory.quantity_on_hand' => ['sometimes', 'integer', 'min:0'],
            'variants.*.inventory.policy' => ['sometimes', Rule::in(['deny', 'continue'])],
            'delete_variant_ids' => [$creating ? 'prohibited' : 'sometimes', 'array', 'max:100'],
            'delete_variant_ids.*' => [
                'required', 'integer', 'distinct',
                Rule::exists('product_variants', 'id')->where(fn (QueryBuilder $query): QueryBuilder => $query->where('product_id', $product?->getKey())),
            ],
        ];

        $validated = $request->validate($rules);

        if (isset($validated['options'])) {
            $optionNames = collect($validated['options'])->pluck('name')->map(static fn (string $name): string => mb_strtolower(trim($name)));

            if ($optionNames->unique()->count() !== $optionNames->count()) {
                throw ValidationException::withMessages(['options' => 'Option names must be unique.']);
            }

            if (($validated['variants'] ?? []) !== []) {
                $optionValueSets = [];

                foreach ($validated['options'] as $option) {
                    $name = mb_strtolower(trim($option['name']));
                    $optionValueSets[$name] = collect($option['values'] ?? [])->map(static fn (string $value): string => mb_strtolower(trim($value)))->all();
                }

                foreach ($validated['variants'] as $variantIndex => $variantData) {
                    $submittedOptionNames = collect($variantData['option_values'] ?? [])->pluck('option_name')->map(static fn (string $name): string => mb_strtolower(trim($name)));

                    if ($submittedOptionNames->unique()->count() !== $submittedOptionNames->count()) {
                        throw ValidationException::withMessages(["variants.{$variantIndex}.option_values" => 'An option may appear only once per variant.']);
                    }

                    foreach ($variantData['option_values'] ?? [] as $valueIndex => $optionValue) {
                        $name = mb_strtolower(trim($optionValue['option_name']));
                        $value = mb_strtolower(trim($optionValue['value']));

                        if (! array_key_exists($name, $optionValueSets)) {
                            throw ValidationException::withMessages(["variants.{$variantIndex}.option_values.{$valueIndex}.option_name" => 'The option does not belong to this product.']);
                        }

                        if ($optionValueSets[$name] !== [] && ! in_array($value, $optionValueSets[$name], true)) {
                            throw ValidationException::withMessages(["variants.{$variantIndex}.option_values.{$valueIndex}.value" => 'The option value is not defined for this product.']);
                        }
                    }
                }

                $matrixSize = collect($validated['options'])->reduce(fn (int $size, array $option): int => $size * max(1, count($option['values'] ?? collect($validated['variants'])->flatMap(fn (array $variant): array => collect($variant['option_values'] ?? [])->filter(fn (array $choice): bool => mb_strtolower($choice['option_name']) === mb_strtolower($option['name']))->pluck('value')->unique()->all())->unique()->all())), 1);

                if ($matrixSize > 100) {
                    throw ValidationException::withMessages(['options' => 'The option matrix may not contain more than 100 variants.']);
                }
            }
        }

        $variants = $validated['variants'] ?? [];
        $defaultVariants = collect($variants)->filter(static fn (array $variant): bool => (bool) ($variant['is_default'] ?? false))->count();

        if ($creating && $variants !== [] && $defaultVariants > 1) {
            throw ValidationException::withMessages(['variants' => 'Exactly one variant may be the default.']);
        }

        if ($creating && $variants !== [] && $defaultVariants === 0) {
            $variants[0]['is_default'] = true;
            $validated['variants'] = $variants;
        }

        foreach ($variants as $position => $variant) {
            if (isset($variant['compare_at_amount'], $variant['price_amount']) && $variant['compare_at_amount'] <= $variant['price_amount']) {
                throw ValidationException::withMessages(["variants.{$position}.compare_at_amount" => 'The compare-at price must be greater than the variant price.']);
            }
        }

        $this->assertProductSkusAvailable($variants, $storeId, $product);

        return $validated;
    }

    /** @param list<array<string, mixed>> $variants */
    private function assertProductSkusAvailable(array $variants, int $storeId, ?Product $product): void
    {
        foreach ($variants as $variant) {
            $sku = $variant['sku'] ?? null;

            if (! is_string($sku) || trim($sku) === '') {
                continue;
            }

            $duplicate = DB::table('product_variants')
                ->join('products', 'products.id', '=', 'product_variants.product_id')
                ->where('products.store_id', $storeId)
                ->where('product_variants.sku', trim($sku))
                ->when($product !== null, fn (QueryBuilder $query): QueryBuilder => $query->where('product_variants.product_id', '!=', $product->getKey()))
                ->when(isset($variant['id']), fn (QueryBuilder $query): QueryBuilder => $query->where('product_variants.id', '!=', $variant['id']))
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages(['variants.sku' => 'The SKU is already in use in this store.']);
            }
        }
    }

    /** @return array<string, mixed> */
    private function orderListResource(Order $order): array
    {
        return [
            'id' => $order->getKey(),
            'order_number' => $order->order_number,
            'status' => $order->status,
            'financial_status' => $order->financial_status,
            'fulfillment_status' => $order->fulfillment_status,
            'customer' => $order->customer === null ? null : [
                'id' => $order->customer->getKey(),
                'name' => $order->customer->name,
                'email' => $order->customer->email,
            ],
            'currency' => $order->currency,
            'subtotal_amount' => (int) $order->subtotal_amount,
            'discount_amount' => (int) $order->discount_amount,
            'shipping_amount' => (int) $order->shipping_amount,
            'tax_amount' => (int) $order->tax_amount,
            'total_amount' => (int) $order->total_amount,
            'line_count' => (int) $order->lines_count,
            'placed_at' => $order->placed_at?->toISOString(),
            'created_at' => $order->created_at?->toISOString(),
        ];
    }

    /** @return array<string, mixed> */
    private function orderResource(Order $order): array
    {
        return [
            'id' => $order->getKey(),
            'store_id' => $order->store_id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'financial_status' => $order->financial_status,
            'fulfillment_status' => $order->fulfillment_status,
            'customer' => $order->customer === null ? null : [
                'id' => $order->customer->getKey(),
                'name' => $order->customer->name,
                'email' => $order->customer->email,
            ],
            'email' => $order->email,
            'currency' => $order->currency,
            'subtotal_amount' => (int) $order->subtotal_amount,
            'discount_amount' => (int) $order->discount_amount,
            'shipping_amount' => (int) $order->shipping_amount,
            'tax_amount' => (int) $order->tax_amount,
            'total_amount' => (int) $order->total_amount,
            'billing_address_json' => $order->billing_address_json,
            'shipping_address_json' => $order->shipping_address_json,
            'lines' => $order->lines->map(static fn ($line): array => [
                'id' => $line->getKey(),
                'product_id' => $line->product_id,
                'variant_id' => $line->variant_id,
                'title_snapshot' => $line->title_snapshot,
                'sku_snapshot' => $line->sku_snapshot,
                'quantity' => (int) $line->quantity,
                'unit_price_amount' => (int) $line->unit_price_amount,
                'total_amount' => (int) $line->total_amount,
                'tax_lines_json' => $line->tax_lines_json ?? [],
                'discount_allocations_json' => $line->discount_allocations_json ?? [],
            ])->all(),
            'payments' => $order->payments->map(static fn ($payment): array => [
                'id' => $payment->getKey(),
                'provider' => $payment->provider,
                'method' => $payment->method,
                'provider_payment_id' => $payment->provider_payment_id,
                'status' => $payment->status,
                'amount' => (int) $payment->amount,
                'currency' => $payment->currency,
                'created_at' => $payment->created_at === null ? null : CarbonImmutable::parse($payment->created_at)->toISOString(),
            ])->all(),
            'fulfillments' => $order->fulfillments->map(static fn ($fulfillment): array => [
                'id' => $fulfillment->getKey(),
                'status' => $fulfillment->status,
                'tracking_company' => $fulfillment->tracking_company,
                'tracking_number' => $fulfillment->tracking_number,
                'tracking_url' => $fulfillment->tracking_url,
                'shipped_at' => $fulfillment->shipped_at?->toISOString(),
                'delivered_at' => $fulfillment->delivered_at?->toISOString(),
                'line_items' => $fulfillment->lines->map(static fn ($line): array => [
                    'order_line_id' => $line->order_line_id,
                    'quantity' => (int) $line->quantity,
                ])->all(),
            ])->all(),
            'refunds' => $order->refunds->map(static fn ($refund): array => [
                'id' => $refund->getKey(),
                'payment_id' => $refund->payment_id,
                'provider_refund_id' => $refund->provider_refund_id,
                'amount' => (int) $refund->amount,
                'reason' => $refund->reason,
                'status' => $refund->status === 'processed' ? 'completed' : $refund->status,
                'created_at' => $refund->created_at === null ? null : CarbonImmutable::parse($refund->created_at)->toISOString(),
                'line_items' => $refund->lines->map(static fn ($line): array => [
                    'order_line_id' => $line->order_line_id,
                    'quantity' => (int) $line->quantity,
                    'amount' => (int) $line->amount,
                ])->all(),
            ])->all(),
            'placed_at' => $order->placed_at?->toISOString(),
            'created_at' => $order->created_at?->toISOString(),
            'updated_at' => $order->updated_at?->toISOString(),
        ];
    }

    /** @return list<string> */
    private function permissionsForRole(string $role): array
    {
        return match ($role) {
            'owner', 'admin' => [
                'read-products', 'write-products', 'read-orders', 'write-orders', 'read-customers', 'write-customers',
                'read-collections', 'write-collections', 'read-discounts', 'write-discounts', 'read-analytics',
                'read-settings', 'write-settings', 'read-themes', 'write-themes', 'read-content', 'write-content',
            ],
            'staff' => [
                'read-products', 'write-products', 'read-orders', 'write-orders', 'read-customers', 'write-customers',
                'read-collections', 'write-collections', 'read-discounts', 'write-discounts', 'read-analytics',
                'read-content', 'write-content',
            ],
            'support' => ['read-products', 'read-orders', 'read-customers', 'read-collections', 'read-discounts'],
            default => [],
        };
    }
}
