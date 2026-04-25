<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\Shop\AdminOrderService;
use App\Services\Shop\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        $orders = Order::query()->latest()->limit(5)->get();
        $sales = Order::query()->sum('total_amount');
        $customers = Customer::query()->count();
        $products = Product::query()->count();

        return view('admin.dashboard', compact('orders', 'sales', 'customers', 'products'));
    }

    public function products(Request $request): View
    {
        $products = Product::query()
            ->with('defaultVariant')
            ->when($request->query('q'), fn ($query, $search) => $query->where('title', 'like', '%'.$search.'%'))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20);

        return view('admin.products.index', compact('products'));
    }

    public function createProduct(): View
    {
        return view('admin.products.form', ['product' => new Product]);
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'price_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:draft,active,archived'],
            'description_html' => ['nullable', 'string'],
        ]);

        $product = Product::query()->create([
            'title' => $validated['title'],
            'handle' => Str::slug($validated['title']).'-'.strtolower(Str::random(4)),
            'status' => $validated['status'],
            'description_html' => $validated['description_html'] ?: '<p>'.$validated['title'].'</p>',
            'vendor' => 'Acme',
            'product_type' => 'Admin Created',
            'tags' => ['admin'],
            'published_at' => $validated['status'] === 'active' ? now() : null,
        ]);

        $variant = $product->variants()->create([
            'price_amount' => (int) round(((float) $validated['price_amount']) * 100),
            'currency' => app('current_store')->default_currency,
            'is_default' => true,
            'sku' => strtoupper(Str::slug($validated['title'])).'-DEFAULT',
        ]);
        $variant->inventoryItem()->create(['store_id' => app('current_store')->id, 'quantity_available' => 10, 'policy' => 'deny']);

        return redirect()->route('admin.products.index')->with('status', 'Product saved successfully.');
    }

    public function editProduct(Product $product): View
    {
        $product->load('defaultVariant');

        return view('admin.products.form', compact('product'));
    }

    public function updateProduct(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'price_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:draft,active,archived'],
            'description_html' => ['nullable', 'string'],
        ]);

        $product->update([
            'title' => $validated['title'],
            'status' => $validated['status'],
            'description_html' => $validated['description_html'],
            'published_at' => $validated['status'] === 'active' ? now() : null,
        ]);
        $product->defaultVariant()->update(['price_amount' => (int) round(((float) $validated['price_amount']) * 100)]);

        return redirect()->route('admin.products.index')->with('status', 'Product saved successfully.');
    }

    public function archiveProduct(Product $product): RedirectResponse
    {
        $product->update(['status' => 'archived', 'published_at' => null]);

        return back()->with('status', 'Product archived.');
    }

    public function orders(Request $request): View
    {
        $orders = Order::query()
            ->with('customer')
            ->when($request->query('status'), fn ($query, $status) => $query->where('financial_status', $status))
            ->latest()
            ->paginate(20);

        return view('admin.orders.index', compact('orders'));
    }

    public function order(Order $order): View
    {
        $order->load('lines', 'payments', 'customer.addresses', 'fulfillments.lines');

        return view('admin.orders.show', compact('order'));
    }

    public function confirmPayment(Order $order, CheckoutService $checkoutService): RedirectResponse
    {
        $checkoutService->confirmBankTransfer($order);

        return back()->with('status', 'Payment confirmed.');
    }

    public function refund(Request $request, Order $order, AdminOrderService $orders): RedirectResponse
    {
        $validated = $request->validate(['amount' => ['required', 'numeric', 'min:0.01']]);

        try {
            $orders->refund($order, (int) round(((float) $validated['amount']) * 100));
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'Refund processed.');
    }

    public function fulfill(Order $order, AdminOrderService $orders): RedirectResponse
    {
        try {
            $orders->fulfill($order->load('lines'));
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'Fulfillment created.');
    }

    public function markShipped(Fulfillment $fulfillment, AdminOrderService $orders): RedirectResponse
    {
        $orders->markShipped($fulfillment);

        return back()->with('status', 'Fulfillment marked shipped.');
    }

    public function markDelivered(Fulfillment $fulfillment, AdminOrderService $orders): RedirectResponse
    {
        $orders->markDelivered($fulfillment);

        return back()->with('status', 'Fulfillment marked delivered.');
    }

    public function customers(): View
    {
        $customers = Customer::query()->withCount('orders')->latest()->paginate(20);

        return view('admin.customers.index', compact('customers'));
    }

    public function customer(Customer $customer): View
    {
        $customer->load('orders', 'addresses');

        return view('admin.customers.show', compact('customer'));
    }

    public function discounts(): View
    {
        $discounts = Discount::query()->latest()->paginate(20);

        return view('admin.discounts.index', compact('discounts'));
    }

    public function storeDiscount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'type' => ['required', 'in:percentage,fixed_amount,free_shipping'],
            'value' => ['nullable', 'numeric', 'min:0'],
        ]);

        Discount::query()->updateOrCreate(
            ['code' => strtoupper($validated['code'])],
            [
                'type' => $validated['type'],
                'value_bps' => $validated['type'] === 'percentage' ? (int) round(((float) ($validated['value'] ?? 0)) * 100) : 0,
                'value_amount' => $validated['type'] === 'fixed_amount' ? (int) round(((float) ($validated['value'] ?? 0)) * 100) : 0,
                'starts_at' => now()->subMinute(),
                'ends_at' => now()->addYear(),
                'is_active' => true,
            ],
        );

        return back()->with('status', 'Discount saved.');
    }

    public function settings(): View
    {
        $store = app('current_store')->load('domains', 'settings');
        $zones = ShippingZone::query()->with('rates')->get();
        $tax = TaxSettings::query()->whereKey($store->id)->first();

        return view('admin.settings.index', compact('store', 'zones', 'tax'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:255']]);
        app('current_store')->update(['name' => $validated['name']]);

        return back()->with('status', 'Settings saved.');
    }

    public function addShippingRate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shipping_zone_id' => ['required', 'integer', 'exists:shipping_zones,id'],
            'name' => ['required', 'string', 'max:255'],
            'price_amount' => ['required', 'numeric', 'min:0'],
        ]);

        ShippingRate::query()->create([
            'shipping_zone_id' => $validated['shipping_zone_id'],
            'name' => $validated['name'],
            'price_amount' => (int) round(((float) $validated['price_amount']) * 100),
        ]);

        return back()->with('status', 'Shipping rate added.');
    }

    public function toggleTax(): RedirectResponse
    {
        $tax = TaxSettings::query()->whereKey(app('current_store')->id)->firstOrFail();
        $tax->update(['prices_include_tax' => ! $tax->prices_include_tax]);

        return back()->with('status', 'Tax settings updated.');
    }

    public function simple(string $section): View
    {
        $records = match ($section) {
            'collections' => Collection::query()->latest()->get(),
            'pages' => Page::query()->latest()->get(),
            'analytics' => Order::query()->latest()->get(),
            default => collect(),
        };

        return view('admin.simple', compact('section', 'records'));
    }
}
