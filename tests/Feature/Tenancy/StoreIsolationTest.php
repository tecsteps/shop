<?php

use App\Models\Order;
use App\Models\Product;

it('scopes product queries to the current store', function () {
    $ctx = createStoreContext();
    Product::factory()->count(3)->for($ctx['store'])->create();

    $org2 = \App\Models\Organization::factory()->create();
    $store2 = \App\Models\Store::factory()->for($org2)->create();
    Product::factory()->count(5)->for($store2)->create();

    app()->instance('current_store', $ctx['store']);
    expect(Product::count())->toBe(3);
});

it('scopes order queries to the current store', function () {
    $ctx = createStoreContext();
    Order::factory()->count(2)->for($ctx['store'])->create();

    $org2 = \App\Models\Organization::factory()->create();
    $store2 = \App\Models\Store::factory()->for($org2)->create();
    Order::factory()->count(7)->for($store2)->create();

    app()->instance('current_store', $ctx['store']);
    expect(Order::count())->toBe(2);
});

it('automatically sets store_id on model creation', function () {
    $ctx = createStoreContext();
    $product = Product::create([
        'title' => 'Test Product',
        'handle' => 'test-product',
        'status' => 'draft',
    ]);

    expect($product->store_id)->toBe($ctx['store']->id);
});

it('prevents accessing another stores records via direct ID', function () {
    $ctx = createStoreContext();

    $org2 = \App\Models\Organization::factory()->create();
    $store2 = \App\Models\Store::factory()->for($org2)->create();
    $product = Product::factory()->for($store2)->create();

    app()->instance('current_store', $ctx['store']);
    expect(Product::find($product->id))->toBeNull();
});

it('allows cross-store access when global scope is removed', function () {
    $ctx = createStoreContext();
    Product::factory()->count(3)->for($ctx['store'])->create();

    $org2 = \App\Models\Organization::factory()->create();
    $store2 = \App\Models\Store::factory()->for($org2)->create();
    Product::factory()->count(5)->for($store2)->create();

    expect(Product::withoutGlobalScopes()->count())->toBe(8);
});
