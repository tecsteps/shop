<?php

use App\Models\Product;
use App\Models\Scopes\StoreScope;

it('scopes product queries to the current store', function () {
    $ctx = createStoreContext();
    $storeA = $ctx['store'];
    $storeB = \App\Models\Store::factory()->create();

    Product::factory()->count(3)->create(['store_id' => $storeA->id]);
    Product::factory()->count(5)->create(['store_id' => $storeB->id]);

    bindCurrentStore($storeA);

    expect(Product::count())->toBe(3);
});

it('automatically sets store_id on model creation', function () {
    $ctx = createStoreContext();
    bindCurrentStore($ctx['store']);

    $product = Product::create(['title' => 'Auto Scoped', 'handle' => 'auto-scoped', 'status' => 'draft']);

    expect($product->store_id)->toBe($ctx['store']->id);
});

it('prevents accessing another stores records via direct ID', function () {
    $ctx = createStoreContext();
    $other = \App\Models\Store::factory()->create();
    $otherProduct = Product::factory()->create(['store_id' => $other->id]);

    bindCurrentStore($ctx['store']);

    expect(Product::find($otherProduct->id))->toBeNull();
});

it('allows cross-store access when global scope is removed', function () {
    $ctx = createStoreContext();
    $other = \App\Models\Store::factory()->create();
    Product::factory()->create(['store_id' => $ctx['store']->id]);
    Product::factory()->create(['store_id' => $other->id]);

    expect(Product::withoutGlobalScope(StoreScope::class)->count())->toBe(2);
});
