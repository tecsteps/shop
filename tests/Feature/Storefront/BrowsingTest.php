<?php

use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->registerStoreDomain = function (Store $store, string $hostname): void {
        StoreDomain::factory()->create(['store_id' => $store->id, 'hostname' => $hostname]);
    };
});

it('renders the storefront home page with featured products and collections', function () {
    $store = Store::factory()->create(['name' => 'Acme Fashion']);
    ($this->registerStoreDomain)($store, 'acme-home.test');

    $collection = Collection::factory()->create(['store_id' => $store->id, 'title' => 'Summer Picks']);
    $product = Product::factory()->withDefaultVariant(4200)->create(['store_id' => $store->id, 'title' => 'Classic Tee']);
    $collection->products()->attach($product);

    $this->get('http://acme-home.test/')
        ->assertOk()
        ->assertSee('Acme Fashion')
        ->assertSee('Classic Tee')
        ->assertSee('Summer Picks');
});

it('renders the collection listing and a single collection page with its products', function () {
    $store = Store::factory()->create();
    ($this->registerStoreDomain)($store, 'acme-collections.test');

    $collection = Collection::factory()->create(['store_id' => $store->id, 'title' => 'Winter Collection', 'handle' => 'winter-collection']);
    $product = Product::factory()->withDefaultVariant(3500)->create(['store_id' => $store->id, 'title' => 'Wool Sweater']);
    $collection->products()->attach($product);

    $this->get('http://acme-collections.test/collections')
        ->assertOk()
        ->assertSee('Winter Collection');

    $this->get('http://acme-collections.test/collections/winter-collection')
        ->assertOk()
        ->assertSee('Winter Collection')
        ->assertSee('Wool Sweater');
});

it('renders a single product page with variant options and price', function () {
    $store = Store::factory()->create();
    ($this->registerStoreDomain)($store, 'acme-product.test');

    $product = Product::factory()->create(['store_id' => $store->id, 'title' => 'Running Shoes', 'handle' => 'running-shoes']);
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 8900,
        'is_default' => true,
    ]);

    $this->get('http://acme-product.test/products/running-shoes')
        ->assertOk()
        ->assertSee('Running Shoes')
        ->assertSee('89.00 EUR');
});

it('returns a 404 for an unknown product handle', function () {
    $store = Store::factory()->create();
    ($this->registerStoreDomain)($store, 'acme-404.test');

    $this->get('http://acme-404.test/products/does-not-exist')
        ->assertNotFound();
});
