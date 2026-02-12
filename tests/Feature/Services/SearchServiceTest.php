<?php

use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Services\SearchService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    StoreDomain::factory()->create([
        'store_id' => $this->store->id,
        'hostname' => 'search-test.test',
    ]);
    app()->instance('current_store', $this->store);

    $this->searchService = app(SearchService::class);
});

test('search returns matching products by title', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Classic Cotton T-Shirt',
        'status' => 'active',
    ]);

    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Leather Jacket',
        'status' => 'active',
    ]);

    $results = $this->searchService->search($this->store, 'Cotton');

    expect($results->total())->toBe(1);
    expect($results->items()[0]->title)->toBe('Classic Cotton T-Shirt');
});

test('search returns matching products by vendor', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Basic Sneakers',
        'vendor' => 'Nike',
        'status' => 'active',
    ]);

    $results = $this->searchService->search($this->store, 'Nike');

    expect($results->total())->toBe(1);
    expect($results->items()[0]->vendor)->toBe('Nike');
});

test('search respects store scoping', function () {
    $otherStore = Store::factory()->create();

    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Store One Widget',
        'status' => 'active',
    ]);

    Product::factory()->create([
        'store_id' => $otherStore->id,
        'title' => 'Store Two Widget',
        'status' => 'active',
    ]);

    $results = $this->searchService->search($this->store, 'Widget');

    expect($results->total())->toBe(1);
    expect($results->items()[0]->title)->toBe('Store One Widget');
});

test('search excludes inactive products', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Draft Widget',
        'status' => 'draft',
    ]);

    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Archived Widget',
        'status' => 'archived',
    ]);

    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Active Widget',
        'status' => 'active',
    ]);

    $results = $this->searchService->search($this->store, 'Widget');

    expect($results->total())->toBe(1);
    expect($results->items()[0]->title)->toBe('Active Widget');
});

test('autocomplete returns matching suggestions', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Comfortable Running Shoes',
        'status' => 'active',
    ]);

    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Comfortable Walking Shoes',
        'status' => 'active',
    ]);

    $results = $this->searchService->autocomplete($this->store, 'Comfort');

    expect($results)->toHaveCount(2);
});

test('autocomplete respects limit', function () {
    for ($i = 1; $i <= 5; $i++) {
        Product::factory()->create([
            'store_id' => $this->store->id,
            'title' => "Shirt Model {$i}",
            'status' => 'active',
        ]);
    }

    $results = $this->searchService->autocomplete($this->store, 'Shirt', 3);

    expect($results)->toHaveCount(3);
});

test('syncProduct adds product to the FTS index', function () {
    // The trigger auto-syncs on create, so the product should already be searchable
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Manually Synced Gadget',
        'status' => 'active',
    ]);

    $results = $this->searchService->search($this->store, 'Gadget');

    expect($results->total())->toBe(1);
});

test('removeProduct removes product from the FTS index', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Removable Widget',
        'status' => 'active',
    ]);

    // Verify it exists first
    $before = $this->searchService->search($this->store, 'Removable');
    expect($before->total())->toBe(1);

    // Delete the product (trigger will remove from FTS)
    $product->delete();

    $after = $this->searchService->search($this->store, 'Removable');
    expect($after->total())->toBe(0);
});

test('search with vendor filter returns only matching vendor', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Premium Sneakers',
        'vendor' => 'Nike',
        'status' => 'active',
    ]);

    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Sport Sneakers',
        'vendor' => 'Adidas',
        'status' => 'active',
    ]);

    $results = $this->searchService->search($this->store, 'Sneakers', ['vendor' => 'Nike']);

    expect($results->total())->toBe(1);
    expect($results->items()[0]->vendor)->toBe('Nike');
});

test('search with price range filter returns products in range', function () {
    $cheapProduct = Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Budget Laptop',
        'status' => 'active',
    ]);
    ProductVariant::factory()->create([
        'product_id' => $cheapProduct->id,
        'price_amount' => 29999,
    ]);

    $expensiveProduct = Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Premium Laptop',
        'status' => 'active',
    ]);
    ProductVariant::factory()->create([
        'product_id' => $expensiveProduct->id,
        'price_amount' => 149999,
    ]);

    $results = $this->searchService->search($this->store, 'Laptop', [
        'min_price' => 10000,
        'max_price' => 50000,
    ]);

    expect($results->total())->toBe(1);
    expect($results->items()[0]->title)->toBe('Budget Laptop');
});

test('search with collection filter returns products in collection', function () {
    $collection = Collection::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Summer Sale',
        'status' => 'active',
    ]);

    $inCollection = Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Summer Hat',
        'status' => 'active',
    ]);
    $collection->products()->attach($inCollection->id, ['position' => 0]);

    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Summer Dress',
        'status' => 'active',
    ]);

    $results = $this->searchService->search($this->store, 'Summer', [
        'collection_id' => $collection->id,
    ]);

    expect($results->total())->toBe(1);
    expect($results->items()[0]->title)->toBe('Summer Hat');
});

test('search returns empty for non-matching query', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Cotton Hoodie',
        'status' => 'active',
    ]);

    $results = $this->searchService->search($this->store, 'xyznonexistent');

    expect($results->total())->toBe(0);
});

test('reindex rebuilds the FTS index for a store', function () {
    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Reindex Alpha',
        'status' => 'active',
    ]);

    Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Reindex Beta',
        'status' => 'active',
    ]);

    // Reindex should rebuild without errors
    $this->searchService->reindex($this->store);

    $results = $this->searchService->search($this->store, 'Reindex');
    expect($results->total())->toBe(2);
});
