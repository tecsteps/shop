<?php

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Store;

it('creates a collection with a unique handle', function () {
    $context = createStoreContext();

    $collection = Collection::factory()->create([
        'store_id' => $context['store']->id,
        'title' => 'Summer Sale',
        'handle' => 'summer-sale',
    ]);

    expect($collection->handle)->toBe('summer-sale');
    $this->assertDatabaseHas('collections', [
        'id' => $collection->id,
        'handle' => 'summer-sale',
    ]);
});

it('adds products to a collection', function () {
    $context = createStoreContext();

    $collection = Collection::factory()->create(['store_id' => $context['store']->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $context['store']->id]);

    foreach ($products as $index => $product) {
        $collection->products()->attach($product->id, ['position' => $index]);
    }

    expect($collection->products()->count())->toBe(3);
});

it('removes products from a collection', function () {
    $context = createStoreContext();

    $collection = Collection::factory()->create(['store_id' => $context['store']->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $context['store']->id]);

    foreach ($products as $index => $product) {
        $collection->products()->attach($product->id, ['position' => $index]);
    }

    $collection->products()->detach($products->first()->id);

    expect($collection->products()->count())->toBe(2);
});

it('reorders products within a collection', function () {
    $context = createStoreContext();

    $collection = Collection::factory()->create(['store_id' => $context['store']->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $context['store']->id]);

    foreach ($products as $index => $product) {
        $collection->products()->attach($product->id, ['position' => $index]);
    }

    // Reorder: move the last product to position 0
    $collection->products()->updateExistingPivot($products[2]->id, ['position' => 0]);
    $collection->products()->updateExistingPivot($products[0]->id, ['position' => 1]);
    $collection->products()->updateExistingPivot($products[1]->id, ['position' => 2]);

    $reordered = $collection->products()->orderByPivot('position')->get();

    expect($reordered[0]->id)->toBe($products[2]->id);
    expect($reordered[1]->id)->toBe($products[0]->id);
    expect($reordered[2]->id)->toBe($products[1]->id);
});

it('transitions collection from draft to active', function () {
    $context = createStoreContext();

    $collection = Collection::factory()->draft()->create(['store_id' => $context['store']->id]);

    expect($collection->status)->toBe(CollectionStatus::Draft);

    $collection->update(['status' => CollectionStatus::Active]);

    expect($collection->fresh()->status)->toBe(CollectionStatus::Active);
});

it('lists collections with product count', function () {
    $context = createStoreContext();

    $collectionA = Collection::factory()->create(['store_id' => $context['store']->id]);
    $collectionB = Collection::factory()->create(['store_id' => $context['store']->id]);

    $productsA = Product::factory()->count(5)->create(['store_id' => $context['store']->id]);
    foreach ($productsA as $i => $product) {
        $collectionA->products()->attach($product->id, ['position' => $i]);
    }

    $productsB = Product::factory()->count(3)->create(['store_id' => $context['store']->id]);
    foreach ($productsB as $i => $product) {
        $collectionB->products()->attach($product->id, ['position' => $i]);
    }

    $collections = Collection::withCount('products')->get();

    expect($collections->find($collectionA->id)->products_count)->toBe(5);
    expect($collections->find($collectionB->id)->products_count)->toBe(3);
});

it('scopes collections to current store', function () {
    $context = createStoreContext();

    Collection::factory()->count(2)->create(['store_id' => $context['store']->id]);

    $orgB = Organization::factory()->create();
    $storeB = Store::factory()->create(['organization_id' => $orgB->id]);
    Collection::factory()->count(4)->create(['store_id' => $storeB->id]);

    app()->instance('current_store', $context['store']);

    expect(Collection::count())->toBe(2);
});
