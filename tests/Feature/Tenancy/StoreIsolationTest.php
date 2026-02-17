<?php

use App\Models\Product;
use App\Models\Scopes\StoreScope;
use App\Models\Store;

it('scopes product queries to current store', function (): void {
    $ctxA = createStoreContext('store-a.test');
    $ctxB = createStoreContext('store-b.test');

    // Create products bypassing the global scope by setting store_id explicitly
    Product::withoutGlobalScope(StoreScope::class)->insert([
        ['store_id' => $ctxA['store']->id, 'title' => 'A1', 'handle' => 'a1', 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()],
        ['store_id' => $ctxA['store']->id, 'title' => 'A2', 'handle' => 'a2', 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()],
        ['store_id' => $ctxA['store']->id, 'title' => 'A3', 'handle' => 'a3', 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()],
        ['store_id' => $ctxB['store']->id, 'title' => 'B1', 'handle' => 'b1', 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()],
        ['store_id' => $ctxB['store']->id, 'title' => 'B2', 'handle' => 'b2', 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()],
    ]);

    // Set current store to A
    app()->instance('current_store', $ctxA['store']);

    expect(Product::count())->toBe(3);
});

it('scopes queries to current store', function (): void {
    $ctxA = createStoreContext('scope-a.test');
    $ctxB = createStoreContext('scope-b.test');

    Product::withoutGlobalScope(StoreScope::class)->insert([
        ['store_id' => $ctxA['store']->id, 'title' => 'PA1', 'handle' => 'pa1', 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()],
        ['store_id' => $ctxA['store']->id, 'title' => 'PA2', 'handle' => 'pa2', 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()],
        ['store_id' => $ctxB['store']->id, 'title' => 'PB1', 'handle' => 'pb1', 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()],
        ['store_id' => $ctxB['store']->id, 'title' => 'PB2', 'handle' => 'pb2', 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()],
        ['store_id' => $ctxB['store']->id, 'title' => 'PB3', 'handle' => 'pb3', 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()],
        ['store_id' => $ctxB['store']->id, 'title' => 'PB4', 'handle' => 'pb4', 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()],
        ['store_id' => $ctxB['store']->id, 'title' => 'PB5', 'handle' => 'pb5', 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()],
    ]);

    app()->instance('current_store', $ctxB['store']);

    expect(Product::count())->toBe(5);
});

it('automatically sets store_id on model creation', function (): void {
    $ctx = createStoreContext('autoset.test');

    $product = Product::create([
        'title' => 'Auto Store Product',
        'handle' => 'auto-store-product',
        'status' => 'draft',
    ]);

    expect($product->store_id)->toBe($ctx['store']->id);
});

it('prevents accessing another stores records via direct ID', function (): void {
    $ctxA = createStoreContext('prevent-a.test');
    $ctxB = createStoreContext('prevent-b.test');

    // Create product in store A (bypass global scope)
    app()->instance('current_store', $ctxA['store']);
    $productA = Product::create([
        'title' => 'Store A Only',
        'handle' => 'store-a-only',
        'status' => 'draft',
    ]);

    // Switch to store B
    app()->instance('current_store', $ctxB['store']);

    expect(Product::find($productA->id))->toBeNull();
});

it('allows cross-store access when global scope removed', function (): void {
    $ctxA = createStoreContext('cross-a.test');
    $ctxB = createStoreContext('cross-b.test');

    Product::withoutGlobalScope(StoreScope::class)->insert([
        ['store_id' => $ctxA['store']->id, 'title' => 'X1', 'handle' => 'x1', 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()],
        ['store_id' => $ctxB['store']->id, 'title' => 'X2', 'handle' => 'x2', 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()],
    ]);

    app()->instance('current_store', $ctxA['store']);

    $allProducts = Product::withoutGlobalScope(StoreScope::class)->count();

    expect($allProducts)->toBe(2);
});
