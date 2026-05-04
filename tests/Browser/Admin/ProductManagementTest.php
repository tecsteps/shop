<?php

use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Playwright\Playwright;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Playwright::setHost('shop.test');

    $this->seed(DatabaseSeeder::class);
});

afterEach(function (): void {
    Playwright::setHost(null);
});

function adminProductHost(): array
{
    return ['host' => 'shop.test'];
}

function adminProductAuthenticate(mixed $testCase): void
{
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $user = User::query()->where('email', 'admin@acme.test')->firstOrFail();

    $testCase->actingAs($user);
    $testCase->withSession(['current_store_id' => $store->getKey()]);
}

function adminProductOpenProducts(mixed $testCase): mixed
{
    adminProductAuthenticate($testCase);

    return visit('/admin/products', adminProductHost())
        ->wait(1)
        ->assertPathIs('/admin/products')
        ->assertSee('Products')
        ->assertNoJavaScriptErrors();
}

function adminProductFillProductForm(
    mixed $page,
    string $title,
    string $handle,
    string $sku,
    string $price,
    string $quantity,
    string $description = '',
    string $vendor = '',
    string $productType = '',
): mixed {
    $page
        ->fill('input[wire\\:model\\.live\\.debounce\\.300ms="title"]', $title)
        ->fill('input[wire\\:model="handle"]', $handle)
        ->fill('input[wire\\:model="variants.0.sku"]', $sku)
        ->fill('input[wire\\:model="variants.0.price"]', $price)
        ->fill('input[wire\\:model="variants.0.quantity"]', $quantity);

    if ($description !== '') {
        $page->fill('textarea[wire\\:model="descriptionHtml"]', $description);
    }

    if ($vendor !== '') {
        $page->fill('input[wire\\:model="vendor"]', $vendor);
    }

    if ($productType !== '') {
        $page->fill('input[wire\\:model="productType"]', $productType);
    }

    return $page->wait(1);
}

function adminProductSave(mixed $page): mixed
{
    return $page
        ->click('button[data-test="product-save-button"]')
        ->wait(1)
        ->assertSee('Product saved')
        ->assertNoJavaScriptErrors();
}

function adminProductReturnToList(mixed $page): mixed
{
    return $page
        ->click('ui-sidebar a[href$="/admin/products"]')
        ->wait(1)
        ->assertPathIs('/admin/products')
        ->assertSee('Products')
        ->assertNoJavaScriptErrors();
}

test('shows the product list with seeded products', function (): void {
    adminProductOpenProducts($this)
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('Premium Slim Fit Jeans')
        ->assertNoJavaScriptErrors();
});

test('can create a new product', function (): void {
    $page = adminProductOpenProducts($this)
        ->click('a[href$="/admin/products/create"]')
        ->wait(1)
        ->assertPathIs('/admin/products/create')
        ->assertSee('Add product');

    adminProductFillProductForm(
        $page,
        'Test Product Created by E2E',
        'test-product-created-by-e2e',
        'E2E-TEST-001',
        '29.99',
        '50',
        'This product was created by the E2E test suite.',
        'Test Vendor',
        'T-Shirts',
    );

    adminProductSave($page);

    adminProductReturnToList($page)
        ->assertSee('Test Product Created by E2E')
        ->assertNoJavaScriptErrors();
});

test('can edit an existing product title', function (): void {
    $page = adminProductOpenProducts($this)
        ->assertSee('Classic Cotton T-Shirt')
        ->click('a:has-text("Classic Cotton T-Shirt")')
        ->wait(1)
        ->assertSee('Classic Cotton T-Shirt');

    $page
        ->fill('input[wire\\:model\\.live\\.debounce\\.300ms="title"]', 'Classic Cotton T-Shirt Updated')
        ->wait(1);

    adminProductSave($page);

    adminProductReturnToList($page)
        ->assertSee('Classic Cotton T-Shirt Updated')
        ->assertNoJavaScriptErrors();
});

test('can archive a product', function (): void {
    $page = adminProductOpenProducts($this)
        ->click('a[href$="/admin/products/create"]')
        ->wait(1)
        ->assertPathIs('/admin/products/create')
        ->assertSee('Add product');

    adminProductFillProductForm(
        $page,
        'Product To Archive',
        'product-to-archive',
        'E2E-ARCHIVE-001',
        '19.99',
        '10',
    );

    adminProductSave($page);

    adminProductReturnToList($page)
        ->assertSee('Product To Archive')
        ->click('a:has-text("Product To Archive")')
        ->wait(1)
        ->assertSee('Product To Archive')
        ->select('select[wire\\:model="status"]', 'archived');

    adminProductSave($page);

    adminProductReturnToList($page)
        ->assertDontSee('Product To Archive')
        ->assertNoJavaScriptErrors();
});

test('shows draft products only in admin and not storefront', function (): void {
    $page = adminProductOpenProducts($this)
        ->select('select[wire\\:model\\.live="statusFilter"]', 'draft')
        ->wait(1)
        ->assertSee('Unreleased Winter Jacket')
        ->assertSee('Draft')
        ->assertDontSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors();

    $page->navigate('/collections/t-shirts')
        ->wait(1)
        ->assertPathIs('/collections/t-shirts')
        ->assertDontSee('Unreleased Winter Jacket')
        ->assertNoJavaScriptErrors()
        ->navigate('/search?q=draft')
        ->wait(1)
        ->assertPathIs('/search')
        ->assertDontSee('Unreleased Winter Jacket')
        ->assertNoJavaScriptErrors();
});

test('can search products in admin', function (): void {
    adminProductOpenProducts($this)
        ->fill('input[wire\\:model\\.live\\.debounce\\.300ms="search"]', 'Cotton')
        ->wait(1)
        ->assertSee('Classic Cotton T-Shirt')
        ->assertDontSee('Premium Slim Fit Jeans')
        ->assertNoJavaScriptErrors();
});

test('can filter products by status in admin', function (): void {
    adminProductOpenProducts($this)
        ->select('select[wire\\:model\\.live="statusFilter"]', 'draft')
        ->wait(1)
        ->assertSee('Unreleased Winter Jacket')
        ->assertDontSee('Classic Cotton T-Shirt')
        ->assertNoJavaScriptErrors()
        ->select('select[wire\\:model\\.live="statusFilter"]', 'active')
        ->wait(1)
        ->fill('input[wire\\:model\\.live\\.debounce\\.300ms="search"]', 'Classic')
        ->wait(1)
        ->assertSee('Classic Cotton T-Shirt')
        ->assertDontSee('Unreleased Winter Jacket')
        ->assertNoJavaScriptErrors();
});
