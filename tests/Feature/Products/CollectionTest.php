<?php

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Support\HandleGenerator;

it('creates a collection with a unique handle', function () {
    $context = createStoreContext();
    $generator = app(HandleGenerator::class);

    $handle = $generator->generate('Summer Sale', 'collections', $context['store']->id);

    $collection = Collection::withoutGlobalScopes()->create([
        'store_id' => $context['store']->id,
        'title' => 'Summer Sale',
        'handle' => $handle,
        'status' => CollectionStatus::Active,
    ]);

    expect($collection->handle)->toBe('summer-sale')
        ->and($collection->exists)->toBeTrue();
});

it('adds products to a collection', function () {
    $context = createStoreContext();

    $collection = Collection::factory()->create(['store_id' => $context['store']->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $context['store']->id]);

    $collection->products()->attach($products->pluck('id')->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]]));

    expect($collection->products()->count())->toBe(3);
});

it('removes products from a collection', function () {
    $context = createStoreContext();

    $collection = Collection::factory()->create(['store_id' => $context['store']->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $context['store']->id]);

    $collection->products()->attach($products->pluck('id')->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]]));

    $collection->products()->detach($products->first()->id);

    expect($collection->products()->count())->toBe(2);
});

it('reorders products within a collection', function () {
    $context = createStoreContext();

    $collection = Collection::factory()->create(['store_id' => $context['store']->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $context['store']->id]);

    $collection->products()->attach([
        $products[0]->id => ['position' => 0],
        $products[1]->id => ['position' => 1],
        $products[2]->id => ['position' => 2],
    ]);

    // Reorder to 2, 0, 1
    $collection->products()->updateExistingPivot($products[0]->id, ['position' => 2]);
    $collection->products()->updateExistingPivot($products[1]->id, ['position' => 0]);
    $collection->products()->updateExistingPivot($products[2]->id, ['position' => 1]);

    $ordered = $collection->products()->orderBy('collection_products.position')->get();

    expect($ordered[0]->id)->toBe($products[1]->id)
        ->and($ordered[1]->id)->toBe($products[2]->id)
        ->and($ordered[2]->id)->toBe($products[0]->id);
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
    $productsB = Product::factory()->count(3)->create(['store_id' => $context['store']->id]);

    $collectionA->products()->attach($productsA->pluck('id')->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]]));
    $collectionB->products()->attach($productsB->pluck('id')->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]]));

    $collections = Collection::withCount('products')->get();

    expect($collections->firstWhere('id', $collectionA->id)->products_count)->toBe(5)
        ->and($collections->firstWhere('id', $collectionB->id)->products_count)->toBe(3);
});

it('scopes collections to current store', function () {
    $contextA = createStoreContext('storeA.test');
    Collection::factory()->count(2)->create(['store_id' => $contextA['store']->id]);

    $contextB = createStoreContext('storeB.test');
    Collection::factory()->count(4)->create(['store_id' => $contextB['store']->id]);

    app()->instance('current_store', $contextA['store']);

    expect(Collection::count())->toBe(2);
});
