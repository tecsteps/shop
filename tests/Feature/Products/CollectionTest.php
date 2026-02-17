<?php

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Models\Store;
use App\Services\HandleGenerator;

beforeEach(function () {
    $ctx = createStoreContext();
    $this->store = $ctx['store'];
});

test('creates a collection with a unique handle', function () {
    $generator = app(HandleGenerator::class);
    $handle = $generator->generate('Summer Sale', 'collections', $this->store->id);

    $collection = Collection::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Summer Sale',
        'handle' => $handle,
    ]);

    expect($collection->handle)->toBe('summer-sale')
        ->and($collection)->toBeInstanceOf(Collection::class);
});

test('adds products to a collection', function () {
    $collection = Collection::factory()->create(['store_id' => $this->store->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $this->store->id]);

    foreach ($products as $i => $product) {
        $collection->products()->attach($product->id, ['position' => $i]);
    }

    expect($collection->products()->count())->toBe(3);
});

test('removes products from a collection', function () {
    $collection = Collection::factory()->create(['store_id' => $this->store->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $this->store->id]);

    foreach ($products as $i => $product) {
        $collection->products()->attach($product->id, ['position' => $i]);
    }

    $collection->products()->detach($products->first()->id);

    expect($collection->products()->count())->toBe(2);
});

test('reorders products within a collection', function () {
    $collection = Collection::factory()->create(['store_id' => $this->store->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $this->store->id]);

    foreach ($products as $i => $product) {
        $collection->products()->attach($product->id, ['position' => $i]);
    }

    // Reorder: move last product to first position
    $collection->products()->updateExistingPivot($products[2]->id, ['position' => 0]);
    $collection->products()->updateExistingPivot($products[0]->id, ['position' => 1]);
    $collection->products()->updateExistingPivot($products[1]->id, ['position' => 2]);

    $ordered = $collection->products()->orderBy('collection_products.position')->get();

    expect($ordered[0]->id)->toBe($products[2]->id)
        ->and($ordered[1]->id)->toBe($products[0]->id)
        ->and($ordered[2]->id)->toBe($products[1]->id);
});

test('transitions collection from draft to active', function () {
    $collection = Collection::factory()->draft()->create(['store_id' => $this->store->id]);

    $collection->update(['status' => CollectionStatus::Active]);

    expect($collection->fresh()->status)->toBe(CollectionStatus::Active);
});

test('lists collections with product count', function () {
    $collectionA = Collection::factory()->create(['store_id' => $this->store->id]);
    $collectionB = Collection::factory()->create(['store_id' => $this->store->id]);

    $productsA = Product::factory()->count(5)->create(['store_id' => $this->store->id]);
    $productsB = Product::factory()->count(3)->create(['store_id' => $this->store->id]);

    foreach ($productsA as $i => $product) {
        $collectionA->products()->attach($product->id, ['position' => $i]);
    }
    foreach ($productsB as $i => $product) {
        $collectionB->products()->attach($product->id, ['position' => $i]);
    }

    $collections = Collection::withCount('products')->get();

    expect($collections->firstWhere('id', $collectionA->id)->products_count)->toBe(5)
        ->and($collections->firstWhere('id', $collectionB->id)->products_count)->toBe(3);
});

test('scopes collections to current store', function () {
    $otherStore = Store::factory()->create();

    Collection::factory()->count(2)->create(['store_id' => $this->store->id]);
    Collection::factory()->count(4)->create(['store_id' => $otherStore->id]);

    expect(Collection::count())->toBe(2);
});
