<?php

use App\Enums\CollectionStatus;
use App\Enums\PageStatus;
use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    StoreDomain::factory()->create([
        'store_id' => $this->store->id,
        'hostname' => 'shop.test',
        'type' => 'storefront',
        'is_primary' => true,
    ]);
});

test('home page loads', function () {
    $response = $this->withHeader('Host', 'shop.test')->get('/');

    $response->assertStatus(200);
});

test('collections index page loads', function () {
    $response = $this->withHeader('Host', 'shop.test')->get('/collections');

    $response->assertStatus(200);
});

test('collection show page loads with valid handle', function () {
    Collection::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'summer',
        'status' => CollectionStatus::Active,
    ]);

    $response = $this->withHeader('Host', 'shop.test')->get('/collections/summer');

    $response->assertStatus(200);
});

test('collection show page returns 404 for invalid handle', function () {
    $response = $this->withHeader('Host', 'shop.test')->get('/collections/nonexistent');

    $response->assertStatus(404);
});

test('product show page loads with valid handle', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'test-product',
        'status' => ProductStatus::Active,
    ]);
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_default' => true,
    ]);

    $response = $this->withHeader('Host', 'shop.test')->get('/products/test-product');

    $response->assertStatus(200);
});

test('product show page returns 404 for invalid handle', function () {
    $response = $this->withHeader('Host', 'shop.test')->get('/products/nonexistent');

    $response->assertStatus(404);
});

test('cart page loads', function () {
    $response = $this->withHeader('Host', 'shop.test')->get('/cart');

    $response->assertStatus(200);
});

test('search page loads', function () {
    $response = $this->withHeader('Host', 'shop.test')->get('/search');

    $response->assertStatus(200);
});

test('pages show loads with valid handle', function () {
    Page::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'about',
        'status' => PageStatus::Published,
    ]);

    $response = $this->withHeader('Host', 'shop.test')->get('/pages/about');

    $response->assertStatus(200);
});

test('pages show returns 404 for draft page', function () {
    Page::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'draft-page',
        'status' => PageStatus::Draft,
    ]);

    $response = $this->withHeader('Host', 'shop.test')->get('/pages/draft-page');

    $response->assertStatus(404);
});

test('pages show returns 404 for nonexistent page', function () {
    $response = $this->withHeader('Host', 'shop.test')->get('/pages/nonexistent');

    $response->assertStatus(404);
});
