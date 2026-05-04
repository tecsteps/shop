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

    $pages = visit([
        '/admin',
        '/admin/products',
        '/admin/orders',
        '/admin/customers',
        '/admin/navigation',
    ], browserSmokeHost());

    $pages->assertNoJavaScriptErrors();

    [$dashboard, $products, $orders, $customers, $navigation] = $pages;

    $dashboard->assertSee('Dashboard');
    $products->assertSee('Products');
    $orders->assertSee('Orders');
    $customers->assertSee('Customers');
    $navigation->assertSee('Navigation');
});

test('storefront home renders on a mobile viewport', function (): void {
    $store = browserSmokeStore();

    visit('/', browserSmokeHost())
        ->on()
        ->mobile()
        ->assertSee($store->name)
        ->assertNoJavaScriptErrors();
});
