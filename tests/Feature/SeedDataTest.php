<?php

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Page;
use App\Models\Product;
use App\Models\SearchSettings;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\TaxSettings;
use App\Models\Theme;
use App\Models\User;

beforeEach(function () {
    $this->seed();
});

// -- Stores and Organization --

test('seeds two stores under one organization', function () {
    expect(Organization::count())->toBe(1);
    expect(Store::count())->toBe(2);

    $fashion = Store::where('handle', 'acme-fashion')->first();
    $electronics = Store::where('handle', 'acme-electronics')->first();
    expect($fashion)->not->toBeNull();
    expect($electronics)->not->toBeNull();
});

test('seeds store domains correctly', function () {
    expect(StoreDomain::count())->toBe(4);
    expect(StoreDomain::where('hostname', 'acme-fashion.test')->where('is_primary', true)->exists())->toBeTrue();
    expect(StoreDomain::where('hostname', 'admin.acme-fashion.test')->where('type', 'admin')->exists())->toBeTrue();
});

// -- Users --

test('seeds five users with correct roles', function () {
    expect(User::count())->toBe(5);
    expect(User::where('email', 'admin@acme.test')->exists())->toBeTrue();
    expect(User::where('email', 'staff@acme.test')->exists())->toBeTrue();
});

// -- Store Settings --

test('seeds store settings for both stores', function () {
    expect(StoreSettings::count())->toBe(2);
    $fashion = Store::where('handle', 'acme-fashion')->first();
    $settings = StoreSettings::where('store_id', $fashion->id)->first();
    expect($settings->settings_json['order_number_prefix'])->toBe('#');
    expect($settings->settings_json['order_number_start'])->toBe(1001);
});

// -- Tax Settings --

test('seeds tax settings for both stores', function () {
    expect(TaxSettings::count())->toBe(2);
});

// -- Shipping --

test('seeds fashion store with three shipping zones', function () {
    $fashion = Store::where('handle', 'acme-fashion')->first();
    app()->instance('current_store', $fashion);
    expect(ShippingZone::where('store_id', $fashion->id)->count())->toBe(3);
    expect(ShippingRate::count())->toBeGreaterThanOrEqual(4);
});

// -- Collections --

test('seeds fashion store with four collections', function () {
    $fashion = Store::where('handle', 'acme-fashion')->first();
    app()->instance('current_store', $fashion);
    expect(Collection::where('store_id', $fashion->id)->count())->toBe(4);
    expect(Collection::where('handle', 'new-arrivals')->exists())->toBeTrue();
    expect(Collection::where('handle', 't-shirts')->exists())->toBeTrue();
    expect(Collection::where('handle', 'pants-jeans')->exists())->toBeTrue();
    expect(Collection::where('handle', 'sale')->exists())->toBeTrue();
});

// -- Products --

test('seeds twenty fashion products', function () {
    $fashion = Store::where('handle', 'acme-fashion')->first();
    app()->instance('current_store', $fashion);
    expect(Product::where('store_id', $fashion->id)->withoutGlobalScopes()->count())->toBe(20);
});

test('seeds five electronics products', function () {
    $electronics = Store::where('handle', 'acme-electronics')->first();
    app()->instance('current_store', $electronics);
    expect(Product::where('store_id', $electronics->id)->withoutGlobalScopes()->count())->toBe(5);
});

test('classic cotton t-shirt has twelve variants at correct price', function () {
    $fashion = Store::where('handle', 'acme-fashion')->first();
    app()->instance('current_store', $fashion);
    $product = Product::where('handle', 'classic-cotton-t-shirt')->first();
    expect($product)->not->toBeNull();
    expect($product->variants()->count())->toBe(12);
    $defaultVariant = $product->variants()->where('is_default', true)->first();
    expect($defaultVariant->price_amount)->toBe(2499);
});

test('premium slim fit jeans has sale price', function () {
    $fashion = Store::where('handle', 'acme-fashion')->first();
    app()->instance('current_store', $fashion);
    $product = Product::where('handle', 'premium-slim-fit-jeans')->first();
    $defaultVariant = $product->variants()->where('is_default', true)->first();
    expect($defaultVariant->price_amount)->toBe(7999);
    expect($defaultVariant->compare_at_amount)->toBe(9999);
});

test('draft product is seeded', function () {
    $fashion = Store::where('handle', 'acme-fashion')->first();
    $product = Product::withoutGlobalScopes()
        ->where('store_id', $fashion->id)
        ->where('status', 'draft')
        ->first();
    expect($product)->not->toBeNull();
});

test('gift card product is digital', function () {
    $fashion = Store::where('handle', 'acme-fashion')->first();
    app()->instance('current_store', $fashion);
    $product = Product::where('handle', 'gift-card')->first();
    expect($product)->not->toBeNull();
    expect($product->product_type)->toBe('Gift Cards');
    $variant = $product->variants()->first();
    expect($variant->requires_shipping)->toBeFalsy();
    expect($product->variants()->count())->toBe(3);
});

// -- Discounts --

test('seeds five discounts with correct codes', function () {
    $fashion = Store::where('handle', 'acme-fashion')->first();
    app()->instance('current_store', $fashion);
    expect(Discount::where('store_id', $fashion->id)->count())->toBe(5);
    expect(Discount::where('code', 'WELCOME10')->exists())->toBeTrue();
    expect(Discount::where('code', 'FLAT5')->exists())->toBeTrue();
    expect(Discount::where('code', 'FREESHIP')->exists())->toBeTrue();
    expect(Discount::where('code', 'EXPIRED20')->exists())->toBeTrue();
    expect(Discount::where('code', 'MAXED')->exists())->toBeTrue();
});

test('expired discount has past dates', function () {
    $fashion = Store::where('handle', 'acme-fashion')->first();
    app()->instance('current_store', $fashion);
    $expired = Discount::where('code', 'EXPIRED20')->first();
    expect($expired->ends_at)->not->toBeNull();
    expect(now()->isAfter($expired->ends_at))->toBeTrue();
});

test('maxed discount has reached usage limit', function () {
    $fashion = Store::where('handle', 'acme-fashion')->first();
    app()->instance('current_store', $fashion);
    $maxed = Discount::where('code', 'MAXED')->first();
    expect($maxed->usage_count)->toBe($maxed->usage_limit);
});

// -- Customers --

test('seeds ten fashion customers', function () {
    $fashion = Store::where('handle', 'acme-fashion')->first();
    app()->instance('current_store', $fashion);
    expect(Customer::where('store_id', $fashion->id)->count())->toBe(10);
});

test('seeds two electronics customers for tenant isolation', function () {
    $electronics = Store::where('handle', 'acme-electronics')->first();
    app()->instance('current_store', $electronics);
    expect(Customer::where('store_id', $electronics->id)->count())->toBe(2);
});

test('john doe has two addresses', function () {
    $fashion = Store::where('handle', 'acme-fashion')->first();
    app()->instance('current_store', $fashion);
    $john = Customer::where('email', 'customer@acme.test')->first();
    expect($john->addresses()->count())->toBe(2);
    expect($john->addresses()->where('is_default', true)->count())->toBe(1);
});

// -- Orders --

test('seeds fifteen fashion orders', function () {
    $fashion = Store::where('handle', 'acme-fashion')->first();
    app()->instance('current_store', $fashion);
    expect(Order::where('store_id', $fashion->id)->count())->toBe(15);
});

test('seeds three electronics orders', function () {
    $electronics = Store::where('handle', 'acme-electronics')->first();
    app()->instance('current_store', $electronics);
    expect(Order::where('store_id', $electronics->id)->count())->toBe(3);
});

test('order 1001 exists and is unfulfilled', function () {
    $fashion = Store::where('handle', 'acme-fashion')->first();
    app()->instance('current_store', $fashion);
    $order = Order::where('order_number', '#1001')->first();
    expect($order)->not->toBeNull();
    expect($order->fulfillment_status->value)->toBe('unfulfilled');
});

// -- Themes --

test('seeds themes for both stores', function () {
    expect(Theme::withoutGlobalScopes()->count())->toBe(2);
});

// -- Pages --

test('seeds five fashion pages', function () {
    $fashion = Store::where('handle', 'acme-fashion')->first();
    app()->instance('current_store', $fashion);
    expect(Page::where('store_id', $fashion->id)->count())->toBe(5);
    expect(Page::where('handle', 'about')->exists())->toBeTrue();
    expect(Page::where('handle', 'faq')->exists())->toBeTrue();
});

// -- Navigation --

test('seeds fashion main and footer menus', function () {
    $fashion = Store::where('handle', 'acme-fashion')->first();
    app()->instance('current_store', $fashion);
    $mainMenu = NavigationMenu::where('store_id', $fashion->id)->where('handle', 'main-menu')->first();
    $footerMenu = NavigationMenu::where('store_id', $fashion->id)->where('handle', 'footer-menu')->first();
    expect($mainMenu)->not->toBeNull();
    expect($footerMenu)->not->toBeNull();
    expect(NavigationItem::where('menu_id', $mainMenu->id)->count())->toBe(5);
    expect(NavigationItem::where('menu_id', $footerMenu->id)->count())->toBe(5);
});

// -- Analytics --

test('seeds thirty-one days of daily analytics', function () {
    $fashion = Store::where('handle', 'acme-fashion')->first();
    app()->instance('current_store', $fashion);
    expect(AnalyticsDaily::where('store_id', $fashion->id)->count())->toBe(31);
});

test('seeds analytics events', function () {
    $fashion = Store::where('handle', 'acme-fashion')->first();
    app()->instance('current_store', $fashion);
    expect(AnalyticsEvent::where('store_id', $fashion->id)->count())->toBeGreaterThan(200);
});

// -- Search Settings --

test('seeds search settings for both stores', function () {
    expect(SearchSettings::count())->toBe(2);
});

// -- Error Pages --

test('404 page renders for nonexistent routes', function () {
    $fashion = Store::where('handle', 'acme-fashion')->first();
    $domain = StoreDomain::where('store_id', $fashion->id)->where('is_primary', true)->first();

    $this->withServerVariables(['HTTP_HOST' => $domain->hostname])
        ->get('/nonexistent-page-xyz')
        ->assertNotFound();
});

// -- Accessibility --

test('storefront layout includes skip-to-content link', function () {
    $fashion = Store::where('handle', 'acme-fashion')->first();
    $domain = StoreDomain::where('store_id', $fashion->id)->where('is_primary', true)->first();

    $response = $this->withServerVariables(['HTTP_HOST' => $domain->hostname])->get('/');
    $response->assertSuccessful();
    $response->assertSee('Skip to main content');
    $response->assertSee('id="main-content"', false);
});

test('admin layout includes skip-to-content link', function () {
    $admin = User::where('email', 'admin@acme.test')->first();
    $fashion = Store::where('handle', 'acme-fashion')->first();
    $adminDomain = StoreDomain::where('store_id', $fashion->id)->where('type', 'admin')->first();

    $this->actingAs($admin);
    $response = $this->withServerVariables(['HTTP_HOST' => $adminDomain->hostname])
        ->get('/');
    $response->assertSee('Skip to main content');
});
