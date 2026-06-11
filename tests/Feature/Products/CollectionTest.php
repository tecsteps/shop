<?php

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;

it('creates a collection with a unique handle', function () {
    $context = createStoreContext();

    $collection = Collection::query()->create([
        'title' => 'Summer Sale',
        'handle' => app(HandleGenerator::class)->generate('Summer Sale', 'collections', $context['store']->getKey()),
    ]);

    expect($collection->handle)->toBe('summer-sale');

    $this->assertDatabaseHas('collections', [
        'id' => $collection->getKey(),
        'store_id' => $context['store']->getKey(),
        'handle' => 'summer-sale',
    ]);
});

it('adds products to a collection', function () {
    $context = createStoreContext();

    $collection = Collection::factory()->for($context['store'])->create();
    $products = Product::factory()->count(3)->for($context['store'])->create();

    foreach ($products as $position => $product) {
        $collection->products()->attach($product, ['position' => $position]);
    }

    expect($collection->products()->count())->toBe(3);
    $this->assertDatabaseCount('collection_products', 3);
});

it('removes products from a collection', function () {
    $context = createStoreContext();

    $collection = Collection::factory()->for($context['store'])->create();
    $products = Product::factory()->count(3)->for($context['store'])->create();

    foreach ($products as $position => $product) {
        $collection->products()->attach($product, ['position' => $position]);
    }

    $collection->products()->detach($products->first());

    expect($collection->products()->count())->toBe(2);
});

it('reorders products within a collection', function () {
    $context = createStoreContext();

    $collection = Collection::factory()->for($context['store'])->create();
    $products = Product::factory()->count(3)->for($context['store'])->create();

    foreach ($products as $position => $product) {
        $collection->products()->attach($product, ['position' => $position]);
    }

    $newOrder = [
        $products[0]->getKey() => 2,
        $products[1]->getKey() => 0,
        $products[2]->getKey() => 1,
    ];

    foreach ($newOrder as $productId => $position) {
        $collection->products()->updateExistingPivot($productId, ['position' => $position]);
    }

    $orderedIds = $collection->products()->pluck('products.id')->all();

    expect($orderedIds)->toBe([
        $products[1]->getKey(),
        $products[2]->getKey(),
        $products[0]->getKey(),
    ]);
});

it('transitions collection from draft to active', function () {
    $context = createStoreContext();

    $collection = Collection::factory()->draft()->for($context['store'])->create();

    $collection->update(['status' => CollectionStatus::Active]);

    expect($collection->refresh()->status)->toBe(CollectionStatus::Active);
});

it('lists collections with product count', function () {
    $context = createStoreContext();

    $collectionA = Collection::factory()->for($context['store'])->create();
    $collectionB = Collection::factory()->for($context['store'])->create();

    foreach (Product::factory()->count(5)->for($context['store'])->create() as $position => $product) {
        $collectionA->products()->attach($product, ['position' => $position]);
    }

    foreach (Product::factory()->count(3)->for($context['store'])->create() as $position => $product) {
        $collectionB->products()->attach($product, ['position' => $position]);
    }

    $collections = Collection::query()->withCount('products')->get()->keyBy('id');

    expect($collections[$collectionA->getKey()]->products_count)->toBe(5);
    expect($collections[$collectionB->getKey()]->products_count)->toBe(3);
});

it('scopes collections to current store', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();

    Collection::factory()->count(2)->for($storeA)->create();
    Collection::factory()->count(4)->for($storeB)->create();

    app()->instance('current_store', $storeA);

    expect(Collection::query()->count())->toBe(2);
});
