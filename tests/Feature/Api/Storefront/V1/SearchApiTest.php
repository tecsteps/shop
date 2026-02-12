<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreDomain;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    StoreDomain::factory()->create([
        'store_id' => $this->store->id,
        'hostname' => 'shop.test',
    ]);
});

test('search suggest returns matching products', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Classic T-Shirt',
        'status' => 'active',
    ]);
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
    ]);

    $response = $this->getJson('http://shop.test/api/storefront/v1/search/suggest?q=T-Shirt');

    $response->assertOk()
        ->assertJsonPath('query', 'T-Shirt')
        ->assertJsonCount(1, 'suggestions')
        ->assertJsonPath('suggestions.0.title', 'Classic T-Shirt')
        ->assertJsonPath('suggestions.0.type', 'product');
});

test('search suggest returns empty for no matches', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Classic T-Shirt',
        'status' => 'active',
    ]);

    $response = $this->getJson('http://shop.test/api/storefront/v1/search/suggest?q=nonexistent');

    $response->assertOk()
        ->assertJsonCount(0, 'suggestions');
});

test('search suggest validates query parameter', function () {
    $response = $this->getJson('http://shop.test/api/storefront/v1/search/suggest');

    $response->assertUnprocessable();
});

test('search suggest respects limit parameter', function () {
    for ($i = 1; $i <= 5; $i++) {
        Product::factory()->create([
            'store_id' => $this->store->id,
            'title' => "Shirt {$i}",
            'status' => 'active',
        ]);
    }

    $response = $this->getJson('http://shop.test/api/storefront/v1/search/suggest?q=Shirt&limit=2');

    $response->assertOk()
        ->assertJsonCount(2, 'suggestions');
});

test('search suggest excludes inactive products', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Draft Product',
        'status' => 'draft',
    ]);

    $response = $this->getJson('http://shop.test/api/storefront/v1/search/suggest?q=Draft');

    $response->assertOk()
        ->assertJsonCount(0, 'suggestions');
});
