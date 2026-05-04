<?php

use App\Models\Collection as ProductCollection;
use App\Models\Page;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

function browserSmokeStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

function browserSmokeHost(): array
{
    return ['host' => 'shop.test'];
}

test('storefront core pages render without javascript errors', function (): void {
    $store = browserSmokeStore();
    $product = Product::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('status', 'active')
        ->firstOrFail();
    $collection = ProductCollection::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('status', 'active')
        ->firstOrFail();
    $page = Page::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('status', 'published')
        ->firstOrFail();

    $pages = visit([
        '/',
        '/collections',
        "/collections/{$collection->handle}",
        "/products/{$product->handle}",
        '/search?q=shirt',
        "/pages/{$page->handle}",
        '/cart',
    ], browserSmokeHost());

    $pages->assertNoJavaScriptErrors();

    [$home, $collections, $collectionPage, $productPage, $search, $contentPage, $cart] = $pages;

    $home->assertSee($store->name);
    $collections->assertSee('Collections');
    $collectionPage->assertSee($collection->title);
    $productPage->assertSee($product->title);
    $search->assertSee('Search');
    $contentPage->assertSee($page->title);
    $cart->assertSee('Cart');
});

test('admin core pages render for an authenticated store user', function (): void {
    $store = browserSmokeStore();
    $user = User::query()->where('email', 'admin@acme.test')->firstOrFail();

    $this->actingAs($user);
    $this->withSession(['current_store_id' => $store->getKey()]);

    $expected = [
        '/admin' => 'Dashboard',
        '/admin/analytics' => 'Analytics',
        '/admin/apps' => 'Apps',
        '/admin/developers' => 'Developers',
        '/admin/products' => 'Products',
        '/admin/collections' => 'Collections',
        '/admin/inventory' => 'Inventory',
        '/admin/orders' => 'Orders',
        '/admin/customers' => 'Customers',
        '/admin/discounts' => 'Discounts',
        '/admin/pages' => 'Pages',
        '/admin/navigation' => 'Navigation',
        '/admin/themes' => 'Themes',
        '/admin/settings' => 'Settings',
        '/admin/settings/shipping' => 'Shipping',
        '/admin/settings/taxes' => 'Taxes',
        '/admin/settings/checkout' => 'Checkout',
        '/admin/settings/notifications' => 'Notifications',
        '/admin/search/settings' => 'Search',
    ];

    $pages = visit(array_keys($expected), browserSmokeHost());

    $pages->assertNoJavaScriptErrors();

    foreach ($pages as $index => $page) {
        $page->assertSee(array_values($expected)[$index]);
    }
});

test('storefront account auth pages render without javascript errors', function (): void {
    $pages = visit([
        '/account/login',
        '/account/register',
        '/account/forgot-password',
        '/account/reset-password/test-token?email=customer@example.test',
    ], browserSmokeHost());

    $pages->assertNoJavaScriptErrors();

    [$login, $register, $forgotPassword, $resetPassword] = $pages;

    $login->assertSee('Log in');
    $register->assertSee('Create an account');
    $forgotPassword->assertSee('Reset password');
    $resetPassword->assertSee('New password');
});

test('storefront home renders on a mobile viewport', function (): void {
    $store = browserSmokeStore();

    visit('/', browserSmokeHost())
        ->on()
        ->mobile()
        ->assertSee($store->name)
        ->assertNoJavaScriptErrors();
});
