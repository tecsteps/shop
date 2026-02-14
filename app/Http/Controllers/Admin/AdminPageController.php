<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesAdminContext;
use App\Http\Controllers\Controller;
use App\Models\AnalyticsDaily;
use App\Models\App as PlatformApp;
use App\Models\AppInstallation;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\NavigationMenu;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SearchSetting;
use App\Models\ShippingZone;
use App\Models\StoreSettings;
use App\Models\TaxSetting;
use App\Models\Theme;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class AdminPageController extends Controller
{
    use ResolvesAdminContext;

    public function dashboard(Request $request): View
    {
        $store = $this->currentStoreModel($request);

        $ordersCount = Order::query()->where('store_id', $store->id)->count();
        $revenue = (int) Order::query()->where('store_id', $store->id)->sum('total_amount');
        $customersCount = Customer::query()->where('store_id', $store->id)->count();
        $productsCount = Product::query()->where('store_id', $store->id)->count();

        /** @var EloquentCollection<int, Order> $recentOrders */
        $recentOrders = Order::query()
            ->where('store_id', $store->id)
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('admin.dashboard', [
            'store' => $store,
            'ordersCount' => $ordersCount,
            'revenue' => $revenue,
            'customersCount' => $customersCount,
            'productsCount' => $productsCount,
            'recentOrders' => $recentOrders,
        ]);
    }

    public function productsIndex(Request $request): View
    {
        $store = $this->currentStoreModel($request);

        $products = Product::query()
            ->where('store_id', $store->id)
            ->withCount('variants')
            ->orderByDesc('updated_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin/products/index', [
            'store' => $store,
            'products' => $products,
        ]);
    }

    public function productsCreate(Request $request): View
    {
        $store = $this->currentStoreModel($request);

        return view('admin/products/form', [
            'store' => $store,
            'mode' => 'create',
            'product' => null,
        ]);
    }

    public function productsEdit(Request $request, int $product): View
    {
        $store = $this->currentStoreModel($request);
        $record = $this->requireProduct($store->id, $product);

        return view('admin/products/form', [
            'store' => $store,
            'mode' => 'edit',
            'product' => $record->loadMissing('variants'),
        ]);
    }

    public function productsStore(Request $request): RedirectResponse
    {
        $store = $this->currentStoreModel($request);
        $validated = $this->validateProductInput($request);

        $handle = $this->resolveUniqueHandle(
            Product::class,
            $store->id,
            isset($validated['handle']) && is_string($validated['handle']) && trim($validated['handle']) !== ''
                ? $validated['handle']
                : (string) $validated['title'],
            'product',
        );

        $priceAmount = (int) $validated['price_amount'];
        $compareAtAmount = isset($validated['compare_at_amount']) ? (int) $validated['compare_at_amount'] : null;
        $currency = Str::upper((string) $validated['currency']);
        $requiresShipping = ! array_key_exists('requires_shipping', $validated) || (bool) $validated['requires_shipping'];

        $product = DB::transaction(function () use (
            $store,
            $validated,
            $handle,
            $priceAmount,
            $compareAtAmount,
            $currency,
            $requiresShipping,
        ): Product {
            $record = Product::query()->create([
                'store_id' => $store->id,
                'title' => (string) $validated['title'],
                'handle' => $handle,
                'status' => (string) $validated['status'],
                'description_html' => $validated['description_html'] ?? null,
                'vendor' => $validated['vendor'] ?? null,
                'product_type' => $validated['product_type'] ?? null,
                'tags' => $this->parseTags(isset($validated['tags']) ? (string) $validated['tags'] : null),
                'published_at' => $validated['published_at'] ?? null,
            ]);

            $this->upsertDefaultVariant(
                $record,
                $store->id,
                $priceAmount,
                $compareAtAmount,
                $currency,
                $requiresShipping,
            );

            return $record;
        });

        return redirect()
            ->route('admin.products.edit', ['product' => (int) $product->id])
            ->with('status', 'Product created.');
    }

    public function productsUpdate(Request $request, int $product): RedirectResponse
    {
        $store = $this->currentStoreModel($request);
        $record = $this->requireProduct($store->id, $product);
        $validated = $this->validateProductInput($request);

        $handle = $this->resolveUniqueHandle(
            Product::class,
            $store->id,
            isset($validated['handle']) && is_string($validated['handle']) && trim($validated['handle']) !== ''
                ? $validated['handle']
                : (string) $validated['title'],
            'product',
            (int) $record->id,
        );

        $priceAmount = (int) $validated['price_amount'];
        $compareAtAmount = isset($validated['compare_at_amount']) ? (int) $validated['compare_at_amount'] : null;
        $currency = Str::upper((string) $validated['currency']);
        $requiresShipping = ! array_key_exists('requires_shipping', $validated) || (bool) $validated['requires_shipping'];

        DB::transaction(function () use (
            $record,
            $validated,
            $handle,
            $store,
            $priceAmount,
            $compareAtAmount,
            $currency,
            $requiresShipping,
        ): void {
            $record->fill([
                'title' => (string) $validated['title'],
                'handle' => $handle,
                'status' => (string) $validated['status'],
                'description_html' => $validated['description_html'] ?? null,
                'vendor' => $validated['vendor'] ?? null,
                'product_type' => $validated['product_type'] ?? null,
                'tags' => $this->parseTags(isset($validated['tags']) ? (string) $validated['tags'] : null),
                'published_at' => $validated['published_at'] ?? null,
            ]);
            $record->save();

            $this->upsertDefaultVariant(
                $record,
                $store->id,
                $priceAmount,
                $compareAtAmount,
                $currency,
                $requiresShipping,
            );
        });

        return redirect()
            ->route('admin.products.edit', ['product' => (int) $record->id])
            ->with('status', 'Product updated.');
    }

    public function productsDestroy(Request $request, int $product): RedirectResponse
    {
        $store = $this->currentStoreModel($request);
        $record = $this->requireProduct($store->id, $product);

        $record->status = 'archived';
        $record->save();

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Product archived.');
    }

    public function inventoryIndex(Request $request): View
    {
        $store = $this->currentStoreModel($request);

        $items = InventoryItem::query()
            ->where('store_id', $store->id)
            ->with('variant.product')
            ->orderByDesc('updated_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin/inventory/index', [
            'store' => $store,
            'items' => $items,
        ]);
    }

    public function collectionsIndex(Request $request): View
    {
        $store = $this->currentStoreModel($request);

        $collections = Collection::query()
            ->where('store_id', $store->id)
            ->withCount('products')
            ->orderByDesc('updated_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin/collections/index', [
            'store' => $store,
            'collections' => $collections,
        ]);
    }

    public function collectionsCreate(Request $request): View
    {
        $store = $this->currentStoreModel($request);

        return view('admin/collections/form', [
            'store' => $store,
            'mode' => 'create',
            'collection' => null,
        ]);
    }

    public function collectionsEdit(Request $request, int $collection): View
    {
        $store = $this->currentStoreModel($request);
        $record = $this->requireCollection($store->id, $collection);

        return view('admin/collections/form', [
            'store' => $store,
            'mode' => 'edit',
            'collection' => $record->loadMissing('products:id'),
        ]);
    }

    public function collectionsStore(Request $request): RedirectResponse
    {
        $store = $this->currentStoreModel($request);
        $validated = $this->validateCollectionInput($request);

        $handle = $this->resolveUniqueHandle(
            Collection::class,
            $store->id,
            isset($validated['handle']) && is_string($validated['handle']) && trim($validated['handle']) !== ''
                ? $validated['handle']
                : (string) $validated['title'],
            'collection',
        );

        $productIds = $this->resolveStoreProductIdsFromString(
            $store->id,
            isset($validated['product_ids']) ? (string) $validated['product_ids'] : '',
        );

        $collection = DB::transaction(function () use ($store, $validated, $handle, $productIds): Collection {
            $record = Collection::query()->create([
                'store_id' => $store->id,
                'title' => (string) $validated['title'],
                'handle' => $handle,
                'description_html' => $validated['description_html'] ?? null,
                'type' => (string) $validated['type'],
                'status' => (string) $validated['status'],
            ]);

            $record->products()->sync($productIds);

            return $record;
        });

        return redirect()
            ->route('admin.collections.edit', ['collection' => (int) $collection->id])
            ->with('status', 'Collection created.');
    }

    public function collectionsUpdate(Request $request, int $collection): RedirectResponse
    {
        $store = $this->currentStoreModel($request);
        $record = $this->requireCollection($store->id, $collection);
        $validated = $this->validateCollectionInput($request);

        $handle = $this->resolveUniqueHandle(
            Collection::class,
            $store->id,
            isset($validated['handle']) && is_string($validated['handle']) && trim($validated['handle']) !== ''
                ? $validated['handle']
                : (string) $validated['title'],
            'collection',
            (int) $record->id,
        );

        $productIds = $this->resolveStoreProductIdsFromString(
            $store->id,
            isset($validated['product_ids']) ? (string) $validated['product_ids'] : '',
        );

        DB::transaction(function () use ($record, $validated, $handle, $productIds): void {
            $record->fill([
                'title' => (string) $validated['title'],
                'handle' => $handle,
                'description_html' => $validated['description_html'] ?? null,
                'type' => (string) $validated['type'],
                'status' => (string) $validated['status'],
            ]);
            $record->save();

            $record->products()->sync($productIds);
        });

        return redirect()
            ->route('admin.collections.edit', ['collection' => (int) $record->id])
            ->with('status', 'Collection updated.');
    }

    public function collectionsDestroy(Request $request, int $collection): RedirectResponse
    {
        $store = $this->currentStoreModel($request);
        $record = $this->requireCollection($store->id, $collection);

        $record->delete();

        return redirect()
            ->route('admin.collections.index')
            ->with('status', 'Collection deleted.');
    }

    public function ordersIndex(Request $request): View
    {
        $store = $this->currentStoreModel($request);

        $orders = Order::query()
            ->where('store_id', $store->id)
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin/orders/index', [
            'store' => $store,
            'orders' => $orders,
        ]);
    }

    public function ordersShow(Request $request, int $order): View
    {
        $store = $this->currentStoreModel($request);

        $record = Order::query()
            ->where('store_id', $store->id)
            ->whereKey($order)
            ->with(['lines.variant.product', 'payments', 'fulfillments', 'customer'])
            ->firstOrFail();

        return view('admin/orders/show', [
            'store' => $store,
            'order' => $record,
        ]);
    }

    public function customersIndex(Request $request): View
    {
        $store = $this->currentStoreModel($request);

        $customers = Customer::query()
            ->where('store_id', $store->id)
            ->withCount('orders')
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin/customers/index', [
            'store' => $store,
            'customers' => $customers,
        ]);
    }

    public function customersShow(Request $request, int $customer): View
    {
        $store = $this->currentStoreModel($request);

        $record = Customer::query()
            ->where('store_id', $store->id)
            ->whereKey($customer)
            ->with(['addresses', 'orders'])
            ->firstOrFail();

        return view('admin/customers/show', [
            'store' => $store,
            'customer' => $record,
        ]);
    }

    public function discountsIndex(Request $request): View
    {
        $store = $this->currentStoreModel($request);

        $discounts = Discount::query()
            ->where('store_id', $store->id)
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin/discounts/index', [
            'store' => $store,
            'discounts' => $discounts,
        ]);
    }

    public function discountsCreate(Request $request): View
    {
        $store = $this->currentStoreModel($request);

        return view('admin/discounts/form', [
            'store' => $store,
            'mode' => 'create',
            'discount' => null,
        ]);
    }

    public function discountsEdit(Request $request, int $discount): View
    {
        $store = $this->currentStoreModel($request);
        $record = $this->requireDiscount($store->id, $discount);

        return view('admin/discounts/form', [
            'store' => $store,
            'mode' => 'edit',
            'discount' => $record,
        ]);
    }

    public function discountsStore(Request $request): RedirectResponse
    {
        $store = $this->currentStoreModel($request);
        $validated = $this->validateDiscountInput($request);
        $normalizedCode = $this->normalizeDiscountCode(
            isset($validated['code']) ? (string) $validated['code'] : null,
            (string) $validated['type'],
        );

        if (
            $normalizedCode !== null
            && $this->isDuplicateDiscountCode($store->id, $normalizedCode)
        ) {
            throw ValidationException::withMessages([
                'code' => ['The code has already been taken.'],
            ]);
        }

        $discount = Discount::query()->create([
            'store_id' => $store->id,
            'type' => (string) $validated['type'],
            'code' => $normalizedCode,
            'value_type' => (string) $validated['value_type'],
            'value_amount' => (int) $validated['value_amount'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'] ?? null,
            'usage_limit' => isset($validated['usage_limit']) ? (int) $validated['usage_limit'] : null,
            'usage_count' => isset($validated['usage_count']) ? (int) $validated['usage_count'] : 0,
            'rules_json' => $this->decodeRulesJson(isset($validated['rules_json']) ? (string) $validated['rules_json'] : null),
            'status' => (string) $validated['status'],
        ]);

        return redirect()
            ->route('admin.discounts.edit', ['discount' => (int) $discount->id])
            ->with('status', 'Discount created.');
    }

    public function discountsUpdate(Request $request, int $discount): RedirectResponse
    {
        $store = $this->currentStoreModel($request);
        $record = $this->requireDiscount($store->id, $discount);
        $validated = $this->validateDiscountInput($request);
        $normalizedCode = $this->normalizeDiscountCode(
            isset($validated['code']) ? (string) $validated['code'] : null,
            (string) $validated['type'],
        );

        if (
            $normalizedCode !== null
            && $this->isDuplicateDiscountCode($store->id, $normalizedCode, (int) $record->id)
        ) {
            throw ValidationException::withMessages([
                'code' => ['The code has already been taken.'],
            ]);
        }

        $record->fill([
            'type' => (string) $validated['type'],
            'code' => $normalizedCode,
            'value_type' => (string) $validated['value_type'],
            'value_amount' => (int) $validated['value_amount'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'] ?? null,
            'usage_limit' => isset($validated['usage_limit']) ? (int) $validated['usage_limit'] : null,
            'usage_count' => isset($validated['usage_count']) ? (int) $validated['usage_count'] : (int) $record->usage_count,
            'rules_json' => $this->decodeRulesJson(isset($validated['rules_json']) ? (string) $validated['rules_json'] : null),
            'status' => (string) $validated['status'],
        ]);
        $record->save();

        return redirect()
            ->route('admin.discounts.edit', ['discount' => (int) $record->id])
            ->with('status', 'Discount updated.');
    }

    public function discountsDestroy(Request $request, int $discount): RedirectResponse
    {
        $store = $this->currentStoreModel($request);
        $record = $this->requireDiscount($store->id, $discount);

        $record->delete();

        return redirect()
            ->route('admin.discounts.index')
            ->with('status', 'Discount deleted.');
    }

    public function settingsIndex(Request $request): View
    {
        $store = $this->currentStoreModel($request);

        $settings = StoreSettings::query()->whereKey($store->id)->first();

        return view('admin/settings/index', [
            'store' => $store,
            'settings' => $settings,
        ]);
    }

    public function settingsShipping(Request $request): View
    {
        $store = $this->currentStoreModel($request);

        $zones = ShippingZone::query()
            ->where('store_id', $store->id)
            ->with('rates')
            ->orderBy('name')
            ->get();

        return view('admin/settings/shipping', [
            'store' => $store,
            'zones' => $zones,
        ]);
    }

    public function settingsTaxes(Request $request): View
    {
        $store = $this->currentStoreModel($request);
        $tax = TaxSetting::query()->whereKey($store->id)->first();

        return view('admin/settings/taxes', [
            'store' => $store,
            'tax' => $tax,
        ]);
    }

    public function themesIndex(Request $request): View
    {
        $store = $this->currentStoreModel($request);

        $themes = Theme::query()
            ->where('store_id', $store->id)
            ->orderByDesc('updated_at')
            ->get();

        return view('admin/themes/index', [
            'store' => $store,
            'themes' => $themes,
        ]);
    }

    public function themesEditor(Request $request, int $theme): View
    {
        $store = $this->currentStoreModel($request);

        $record = Theme::query()
            ->where('store_id', $store->id)
            ->whereKey($theme)
            ->with(['files', 'settings'])
            ->firstOrFail();

        return view('admin/themes/editor', [
            'store' => $store,
            'theme' => $record,
        ]);
    }

    public function pagesIndex(Request $request): View
    {
        $store = $this->currentStoreModel($request);

        $pages = Page::query()
            ->where('store_id', $store->id)
            ->orderByDesc('updated_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin/pages/index', [
            'store' => $store,
            'pages' => $pages,
        ]);
    }

    public function pagesCreate(Request $request): View
    {
        $store = $this->currentStoreModel($request);

        return view('admin/pages/form', [
            'store' => $store,
            'mode' => 'create',
            'page' => null,
        ]);
    }

    public function pagesEdit(Request $request, int $page): View
    {
        $store = $this->currentStoreModel($request);
        $record = $this->requirePage($store->id, $page);

        return view('admin/pages/form', [
            'store' => $store,
            'mode' => 'edit',
            'page' => $record,
        ]);
    }

    public function pagesStore(Request $request): RedirectResponse
    {
        $store = $this->currentStoreModel($request);
        $validated = $this->validatePageInput($request);
        $handle = $this->resolveUniqueHandle(
            Page::class,
            $store->id,
            isset($validated['handle']) && is_string($validated['handle']) && trim($validated['handle']) !== ''
                ? $validated['handle']
                : (string) $validated['title'],
            'page',
        );

        $page = Page::query()->create([
            'store_id' => $store->id,
            'title' => (string) $validated['title'],
            'handle' => $handle,
            'body_html' => $validated['body_html'] ?? null,
            'status' => (string) $validated['status'],
            'published_at' => $validated['published_at'] ?? null,
        ]);

        return redirect()
            ->route('admin.pages.edit', ['page' => (int) $page->id])
            ->with('status', 'Page created.');
    }

    public function pagesUpdate(Request $request, int $page): RedirectResponse
    {
        $store = $this->currentStoreModel($request);
        $record = $this->requirePage($store->id, $page);
        $validated = $this->validatePageInput($request);
        $handle = $this->resolveUniqueHandle(
            Page::class,
            $store->id,
            isset($validated['handle']) && is_string($validated['handle']) && trim($validated['handle']) !== ''
                ? $validated['handle']
                : (string) $validated['title'],
            'page',
            (int) $record->id,
        );

        $record->fill([
            'title' => (string) $validated['title'],
            'handle' => $handle,
            'body_html' => $validated['body_html'] ?? null,
            'status' => (string) $validated['status'],
            'published_at' => $validated['published_at'] ?? null,
        ]);
        $record->save();

        return redirect()
            ->route('admin.pages.edit', ['page' => (int) $record->id])
            ->with('status', 'Page updated.');
    }

    public function pagesDestroy(Request $request, int $page): RedirectResponse
    {
        $store = $this->currentStoreModel($request);
        $record = $this->requirePage($store->id, $page);

        $record->delete();

        return redirect()
            ->route('admin.pages.index')
            ->with('status', 'Page deleted.');
    }

    public function navigationIndex(Request $request): View
    {
        $store = $this->currentStoreModel($request);

        $menus = NavigationMenu::query()
            ->where('store_id', $store->id)
            ->with('items')
            ->orderBy('title')
            ->get();

        return view('admin/navigation/index', [
            'store' => $store,
            'menus' => $menus,
        ]);
    }

    public function appsIndex(Request $request): View
    {
        $store = $this->currentStoreModel($request);

        $installations = AppInstallation::query()
            ->where('store_id', $store->id)
            ->with('app')
            ->orderByDesc('installed_at')
            ->get();

        /** @var EloquentCollection<int, PlatformApp> $availableApps */
        $availableApps = PlatformApp::query()
            ->orderBy('name')
            ->limit(50)
            ->get();

        return view('admin/apps/index', [
            'store' => $store,
            'installations' => $installations,
            'availableApps' => $availableApps,
        ]);
    }

    public function appsShow(Request $request, int $installation): View
    {
        $store = $this->currentStoreModel($request);

        $record = AppInstallation::query()
            ->where('store_id', $store->id)
            ->whereKey($installation)
            ->with(['app', 'oauthTokens', 'webhookSubscriptions'])
            ->firstOrFail();

        return view('admin/apps/show', [
            'store' => $store,
            'installation' => $record,
        ]);
    }

    public function developersIndex(Request $request): View
    {
        $store = $this->currentStoreModel($request);

        $installations = AppInstallation::query()
            ->where('store_id', $store->id)
            ->with(['oauthTokens', 'webhookSubscriptions'])
            ->get();

        return view('admin/developers/index', [
            'store' => $store,
            'installations' => $installations,
        ]);
    }

    public function analyticsIndex(Request $request): View
    {
        $store = $this->currentStoreModel($request);

        $daily = AnalyticsDaily::query()
            ->where('store_id', $store->id)
            ->orderByDesc('date')
            ->limit(30)
            ->get();

        return view('admin/analytics/index', [
            'store' => $store,
            'daily' => $daily,
        ]);
    }

    public function searchSettings(Request $request): View
    {
        $store = $this->currentStoreModel($request);

        $settings = SearchSetting::query()->whereKey($store->id)->first();

        return view('admin/search/settings', [
            'store' => $store,
            'settings' => $settings,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProductInput(Request $request): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description_html' => ['nullable', 'string'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'product_type' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(['draft', 'active', 'archived'])],
            'tags' => ['nullable', 'string', 'max:2000'],
            'published_at' => ['nullable', 'date'],
            'price_amount' => ['required', 'integer', 'min:0'],
            'compare_at_amount' => ['nullable', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'requires_shipping' => ['nullable', 'boolean'],
        ]);

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCollectionInput(Request $request): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description_html' => ['nullable', 'string'],
            'type' => ['required', 'string', Rule::in(['manual', 'automated'])],
            'status' => ['required', 'string', Rule::in(['draft', 'active', 'archived'])],
            'product_ids' => ['nullable', 'string', 'regex:/^[0-9,\s]*$/'],
        ]);

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateDiscountInput(Request $request): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(['code', 'automatic'])],
            'code' => ['nullable', 'required_if:type,code', 'string', 'max:50'],
            'value_type' => ['required', 'string', Rule::in(['fixed', 'percent', 'free_shipping'])],
            'value_amount' => ['required', 'integer', 'min:0'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_count' => ['nullable', 'integer', 'min:0'],
            'rules_json' => ['nullable', 'json'],
            'status' => ['required', 'string', Rule::in(['draft', 'active', 'expired', 'disabled'])],
        ]);

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePageInput(Request $request): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'body_html' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(['draft', 'published', 'archived'])],
            'published_at' => ['nullable', 'date'],
        ]);

        return $validated;
    }

    private function upsertDefaultVariant(
        Product $product,
        int $storeId,
        int $priceAmount,
        ?int $compareAtAmount,
        string $currency,
        bool $requiresShipping,
    ): void {
        $variant = ProductVariant::query()
            ->where('product_id', $product->id)
            ->where('is_default', true)
            ->first();

        if (! $variant instanceof ProductVariant) {
            $variant = ProductVariant::query()->create([
                'product_id' => (int) $product->id,
                'sku' => null,
                'barcode' => null,
                'price_amount' => $priceAmount,
                'compare_at_amount' => $compareAtAmount,
                'currency' => $currency,
                'weight_g' => null,
                'requires_shipping' => $requiresShipping,
                'is_default' => true,
                'position' => 1,
                'status' => 'active',
            ]);
        } else {
            $variant->fill([
                'price_amount' => $priceAmount,
                'compare_at_amount' => $compareAtAmount,
                'currency' => $currency,
                'requires_shipping' => $requiresShipping,
            ]);
            $variant->save();
        }

        InventoryItem::query()->firstOrCreate([
            'variant_id' => (int) $variant->id,
        ], [
            'store_id' => $storeId,
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
            'policy' => 'deny',
        ]);
    }

    private function resolveUniqueHandle(
        string $modelClass,
        int $storeId,
        string $source,
        string $fallback,
        ?int $ignoreId = null,
    ): string {
        $base = Str::slug($source);
        $base = $base !== '' ? $base : $fallback;

        $candidate = $base;
        $suffix = 1;

        while (true) {
            $query = $modelClass::query()
                ->where('store_id', $storeId)
                ->where('handle', $candidate);

            if ($ignoreId !== null) {
                $query->where('id', '!=', $ignoreId);
            }

            if (! $query->exists()) {
                return $candidate;
            }

            $candidate = $base.'-'.$suffix;
            $suffix++;
        }
    }

    /**
     * @return list<string>
     */
    private function parseTags(?string $rawTags): array
    {
        if ($rawTags === null || trim($rawTags) === '') {
            return [];
        }

        $tags = array_map(
            static fn (string $tag): string => trim($tag),
            explode(',', $rawTags),
        );

        $tags = array_values(array_filter($tags, static fn (string $tag): bool => $tag !== ''));

        return array_values(array_unique($tags));
    }

    /**
     * @return list<int>
     */
    private function resolveStoreProductIdsFromString(int $storeId, string $rawIds): array
    {
        $segments = preg_split('/[\s,]+/', $rawIds);

        if (! is_array($segments) || $segments === []) {
            return [];
        }

        $ids = [];

        foreach ($segments as $segment) {
            $trimmed = trim($segment);

            if ($trimmed === '' || ! ctype_digit($trimmed)) {
                continue;
            }

            $id = (int) $trimmed;

            if ($id > 0) {
                $ids[] = $id;
            }
        }

        $ids = array_values(array_unique($ids));

        if ($ids === []) {
            return [];
        }

        return Product::query()
            ->where('store_id', $storeId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeRulesJson(?string $rawRules): array
    {
        if ($rawRules === null || trim($rawRules) === '') {
            return [];
        }

        $decoded = json_decode($rawRules, true);

        if (! is_array($decoded)) {
            return [];
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    private function normalizeDiscountCode(?string $code, string $type): ?string
    {
        if ($type !== 'code') {
            return null;
        }

        $normalized = Str::upper(trim((string) $code));

        return $normalized !== '' ? $normalized : null;
    }

    private function isDuplicateDiscountCode(int $storeId, string $code, ?int $ignoreDiscountId = null): bool
    {
        $query = Discount::query()
            ->where('store_id', $storeId)
            ->whereRaw('LOWER(code) = ?', [Str::lower($code)]);

        if ($ignoreDiscountId !== null) {
            $query->where('id', '!=', $ignoreDiscountId);
        }

        return $query->exists();
    }

    private function requireProduct(int $storeId, int $productId): Product
    {
        /** @var Product $product */
        $product = Product::query()
            ->where('store_id', $storeId)
            ->whereKey($productId)
            ->firstOrFail();

        return $product;
    }

    private function requireCollection(int $storeId, int $collectionId): Collection
    {
        /** @var Collection $collection */
        $collection = Collection::query()
            ->where('store_id', $storeId)
            ->whereKey($collectionId)
            ->firstOrFail();

        return $collection;
    }

    private function requireDiscount(int $storeId, int $discountId): Discount
    {
        /** @var Discount $discount */
        $discount = Discount::query()
            ->where('store_id', $storeId)
            ->whereKey($discountId)
            ->firstOrFail();

        return $discount;
    }

    private function requirePage(int $storeId, int $pageId): Page
    {
        /** @var Page $page */
        $page = Page::query()
            ->where('store_id', $storeId)
            ->whereKey($pageId)
            ->firstOrFail();

        return $page;
    }
}
