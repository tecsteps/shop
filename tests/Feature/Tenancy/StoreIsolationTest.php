<?php

use App\Models\Product;
use App\Models\Scopes\StoreScope;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('scopes product queries to the current store', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();

    Product::factory()->count(3)->for($storeA)->create();
    Product::factory()->count(5)->for($storeB)->create();

    app()->instance('current_store', $storeA);

    expect(Product::query()->count())->toBe(3);
});

it('auto assigns store_id when creating within store context', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    $product = Product::query()->create([
        'title' => 'Scoped Product',
        'handle' => 'scoped-product',
        'tags' => [],
    ]);

    expect($product->store_id)->toBe($store->id);
});

it('prevents direct access to another stores product', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();
    $product = Product::factory()->for($storeA)->create();

    app()->instance('current_store', $storeB);

    expect(Product::query()->find($product->id))->toBeNull();
});

it('allows explicit cross-store access when the global scope is removed', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();
    Product::factory()->count(2)->for($storeA)->create();
    Product::factory()->count(4)->for($storeB)->create();

    app()->instance('current_store', $storeA);

    expect(Product::query()->withoutGlobalScope(StoreScope::class)->count())->toBe(6);
});
