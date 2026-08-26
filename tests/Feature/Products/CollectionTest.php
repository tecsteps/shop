<?php

use App\Models\Collection;
use App\Models\Product;

it('creates a collection with a unique handle', function () {
    $ctx = createStoreContext();
    $collection = Collection::create(['store_id' => $ctx['store']->id, 'title' => 'Summer Sale', 'handle' => 'summer-sale', 'type' => 'manual']);

    expect($collection->handle)->toBe('summer-sale');
});

it('adds products to a collection', function () {
    $ctx = createStoreContext();
    $collection = Collection::factory()->create(['store_id' => $ctx['store']->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $ctx['store']->id]);

    $collection->products()->attach($products->pluck('id'));

    expect($collection->products()->count())->toBe(3);
});

it('removes products from a collection', function () {
    $ctx = createStoreContext();
    $collection = Collection::factory()->create(['store_id' => $ctx['store']->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $ctx['store']->id]);
    $collection->products()->attach($products->pluck('id'));

    $collection->products()->detach($products->first()->id);

    expect($collection->products()->count())->toBe(2);
});

it('scopes collections to current store', function () {
    $ctx = createStoreContext();
    $other = \App\Models\Store::factory()->create();
    Collection::factory()->count(2)->create(['store_id' => $ctx['store']->id]);
    Collection::factory()->count(4)->create(['store_id' => $other->id]);

    bindCurrentStore($ctx['store']);

    expect(Collection::count())->toBe(2);
});
