<?php

use App\Models\AppInstallation;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\Store;
use App\Models\Theme;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

/**
 * Pragmatic smoke test (spec 09 Phase 11): every public storefront GET
 * route and every key admin GET route must render without throwing, so
 * Livewire/Blade regressions surface cheaply. Runs against the full demo
 * seed from spec 07.
 */
beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
});

it('renders every public storefront page for the demo store', function () {
    $base = 'http://acme-fashion.test';

    $paths = ['/', '/collections', '/search', '/search?q=shirt', '/cart', '/account/login', '/account/register'];

    foreach (Collection::query()->withoutGlobalScopes()->where('store_id', $this->store->id)->pluck('handle') as $handle) {
        $paths[] = "/collections/{$handle}";
    }

    foreach (Product::query()->withoutGlobalScopes()->where('store_id', $this->store->id)->where('status', 'active')->pluck('handle') as $handle) {
        $paths[] = "/products/{$handle}";
    }

    foreach (Page::query()->withoutGlobalScopes()->where('store_id', $this->store->id)->pluck('handle') as $handle) {
        $paths[] = "/pages/{$handle}";
    }

    foreach ($paths as $path) {
        $response = $this->get($base.$path);

        expect($response->getStatusCode())->toBe(200, "GET {$path} returned {$response->getStatusCode()}");
    }

    // An empty cart sends the checkout page back to the cart.
    $this->get($base.'/checkout')->assertRedirect();
});

it('renders every customer account page for the demo customer', function () {
    $base = 'http://acme-fashion.test';

    $customer = Customer::query()
        ->withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('email', 'customer@acme.test')
        ->firstOrFail();

    $paths = ['/account', '/account/orders', '/account/addresses'];

    foreach (Order::query()->withoutGlobalScopes()->where('store_id', $this->store->id)->where('customer_id', $customer->id)->pluck('order_number') as $orderNumber) {
        $paths[] = '/account/orders/'.ltrim($orderNumber, '#');
    }

    foreach ($paths as $path) {
        $response = $this->actingAs($customer, 'customer')->get($base.$path);

        expect($response->getStatusCode())->toBe(200, "GET {$path} returned {$response->getStatusCode()}");
    }
});

it('renders every key admin page for the demo owner', function () {
    $owner = User::query()->where('email', 'admin@acme.test')->firstOrFail();

    $storeId = $this->store->id;
    $productId = Product::query()->withoutGlobalScopes()->where('store_id', $storeId)->value('id');
    $orderId = Order::query()->withoutGlobalScopes()->where('store_id', $storeId)->value('id');
    $customerId = Customer::query()->withoutGlobalScopes()->where('store_id', $storeId)->value('id');
    $collectionId = Collection::query()->withoutGlobalScopes()->where('store_id', $storeId)->value('id');
    $discountId = Discount::query()->withoutGlobalScopes()->where('store_id', $storeId)->value('id');
    $themeId = Theme::query()->withoutGlobalScopes()->where('store_id', $storeId)->value('id');
    $pageId = Page::query()->withoutGlobalScopes()->where('store_id', $storeId)->value('id');
    $installationId = AppInstallation::query()->withoutGlobalScopes()->where('store_id', $storeId)->value('id');

    $paths = [
        '/admin',
        '/admin/products',
        '/admin/products/create',
        "/admin/products/{$productId}/edit",
        '/admin/orders',
        "/admin/orders/{$orderId}",
        '/admin/customers',
        "/admin/customers/{$customerId}",
        '/admin/collections',
        '/admin/collections/create',
        "/admin/collections/{$collectionId}/edit",
        '/admin/inventory',
        '/admin/discounts',
        '/admin/discounts/create',
        "/admin/discounts/{$discountId}/edit",
        '/admin/settings',
        '/admin/settings/shipping',
        '/admin/settings/taxes',
        '/admin/themes',
        "/admin/themes/{$themeId}/editor",
        '/admin/pages',
        '/admin/pages/create',
        "/admin/pages/{$pageId}/edit",
        '/admin/navigation',
        '/admin/analytics',
        '/admin/search/settings',
        '/admin/developers',
        '/admin/apps',
        "/admin/apps/{$installationId}",
    ];

    foreach ($paths as $path) {
        $response = $this->actingAs($owner)
            ->withSession(['current_store_id' => $storeId])
            ->get($path);

        expect($response->getStatusCode())->toBe(200, "GET {$path} returned {$response->getStatusCode()}");
    }
});
