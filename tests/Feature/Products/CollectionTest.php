<?php

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\Product;

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('creates a collection with a unique handle', function () {
    $collection = Collection::factory()->for($this->ctx['store'])->create([
        'title' => 'Summer Sale',
        'handle' => 'summer-sale',
    ]);

    expect($collection->handle)->toBe('summer-sale');
});

it('adds products to a collection', function () {
    $collection = Collection::factory()->for($this->ctx['store'])->create();
    $products = Product::factory()->count(3)->for($this->ctx['store'])->create();

    $collection->products()->attach($products->pluck('id'));

    expect($collection->products()->count())->toBe(3);
});

it('removes products from a collection', function () {
    $collection = Collection::factory()->for($this->ctx['store'])->create();
    $products = Product::factory()->count(3)->for($this->ctx['store'])->create();
    $collection->products()->attach($products->pluck('id'));

    $collection->products()->detach($products->first()->id);

    expect($collection->products()->count())->toBe(2);
});

it('transitions collection from draft to active', function () {
    $collection = Collection::factory()->draft()->for($this->ctx['store'])->create();
    $collection->update(['status' => CollectionStatus::Active]);

    expect($collection->status)->toBe(CollectionStatus::Active);
});

it('scopes collections to current store', function () {
    Collection::factory()->count(2)->for($this->ctx['store'])->create();

    $org2 = \App\Models\Organization::factory()->create();
    $store2 = \App\Models\Store::factory()->for($org2)->create();
    Collection::factory()->count(4)->for($store2)->create();

    app()->instance('current_store', $this->ctx['store']);
    expect(Collection::count())->toBe(2);
});
