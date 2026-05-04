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

function browserSmokeProduct(string $handle = 'classic-cotton-t-shirt'): Product
{
    $store = browserSmokeStore();

    return Product::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('handle', $handle)
        ->firstOrFail();
}

function browserSmokeCollection(string $handle = 'new-arrivals'): ProductCollection
{
    $store = browserSmokeStore();

    return ProductCollection::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('handle', $handle)
        ->firstOrFail();
}

function browserSmokePage(string $handle = 'about'): Page
{
    $store = browserSmokeStore();

    return Page::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('handle', $handle)
        ->firstOrFail();
}

test('loads the storefront home page', function (): void {
    $store = browserSmokeStore();

    visit('/', browserSmokeHost())
        ->assertSee($store->name)
        ->assertNoJavaScriptErrors();
});

test('loads a collection page', function (): void {
    $collection = browserSmokeCollection();

    visit("/collections/{$collection->handle}", browserSmokeHost())
        ->assertSee($collection->title)
        ->assertNoJavaScriptErrors();
});

test('loads a product page', function (): void {
    $product = browserSmokeProduct();

    visit("/products/{$product->handle}", browserSmokeHost())
        ->assertSee($product->title)
        ->assertNoJavaScriptErrors();
});

test('loads the cart page', function (): void {
    visit('/cart', browserSmokeHost())
        ->assertSee('Cart')
        ->assertNoJavaScriptErrors();
});

test('loads the customer login page', function (): void {
    visit('/account/login', browserSmokeHost())
        ->assertSee('Log in')
        ->assertNoJavaScriptErrors();
});

test('loads the admin login page', function (): void {
    visit('/admin/login', browserSmokeHost())
        ->assertSee('Sign in')
        ->assertNoJavaScriptErrors();
});

test('loads the about page', function (): void {
    $page = browserSmokePage();

    visit("/pages/{$page->handle}", browserSmokeHost())
        ->assertSee($page->title)
        ->assertNoJavaScriptErrors();
});

test('loads the search page', function (): void {
    visit('/search?q=shirt', browserSmokeHost())
        ->assertSee('Search')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();
});

test('loads all collections listing', function (): void {
    visit('/collections', browserSmokeHost())
        ->assertSee('Collections')
        ->assertSee('New Arrivals')
        ->assertNoJavaScriptErrors();
});

test('has no errors on critical pages', function (): void {
    $store = browserSmokeStore();
    $product = browserSmokeProduct();
    $collection = browserSmokeCollection();
    $page = browserSmokePage();

    $pages = visit([
        '/',
        '/collections',
        "/collections/{$collection->handle}",
        "/products/{$product->handle}",
        '/search?q=shirt',
        "/pages/{$page->handle}",
        '/cart',
        '/account/login',
        '/account/register',
        '/forgot-password',
        '/reset-password/test-token?email=customer@example.test',
        '/account/forgot-password',
        '/account/reset-password/test-token?email=customer@example.test',
        '/admin/login',
    ], browserSmokeHost());

    $pages->assertNoJavaScriptErrors();

    [$home, $collections, $collectionPage, $productPage, $search, $contentPage, $cart, $login, $register, $forgotPassword, $resetPassword, $accountForgotPassword, $accountResetPassword, $adminLogin] = $pages;

    $home->assertSee($store->name);
    $collections->assertSee('Collections');
    $collectionPage->assertSee($collection->title);
    $productPage->assertSee($product->title);
    $search->assertSee('Search');
    $contentPage->assertSee($page->title);
    $cart->assertSee('Cart');
    $login->assertSee('Log in');
    $register->assertSee('Create an account');
    $forgotPassword->assertSee('Reset password');
    $resetPassword->assertSee('New password');
    $accountForgotPassword->assertSee('Reset password');
    $accountResetPassword->assertSee('New password');
    $adminLogin->assertSee('Sign in');

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

    $user = User::query()->where('email', 'admin@acme.test')->firstOrFail();

    $this->actingAs($user);
    $this->withSession(['current_store_id' => $store->getKey()]);

    $adminPages = visit(array_keys($expected), browserSmokeHost());

    $adminPages->assertNoJavaScriptErrors();

    foreach ($adminPages as $index => $adminPage) {
        $adminPage->assertSee(array_values($expected)[$index]);
    }
});
