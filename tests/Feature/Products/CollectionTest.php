<?php

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Support\HandleGenerator;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
});

it('creates a collection with a unique handle', function () {
    $generator = app(HandleGenerator::class);
    $handle = $generator->generate('Summer Sale', 'collections', $this->store->id);

    $collection = Collection::query()->create([
        'store_id' => $this->store->id,
        'title' => 'Summer Sale',
        'handle' => $handle,
    ]);

    expect($collection->handle)->toBe('summer-sale')
        ->and($collection)->toBeInstanceOf(Collection::class);
});

it('adds products to a collection', function () {
    $collection = Collection::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'test-collection',
    ]);

    $products = Product::factory()->count(3)->create([
        'store_id' => $this->store->id,
    ]);

    foreach ($products as $i => $product) {
        $collection->products()->attach($product->id, ['position' => $i]);
    }

    expect($collection->products)->toHaveCount(3);
});

it('removes products from a collection', function () {
    $collection = Collection::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'test-collection',
    ]);

    $products = Product::factory()->count(3)->create([
        'store_id' => $this->store->id,
    ]);

    foreach ($products as $i => $product) {
        $collection->products()->attach($product->id, ['position' => $i]);
    }

    $collection->products()->detach($products->first()->id);

    expect($collection->products()->count())->toBe(2);
});

it('reorders products within a collection', function () {
    $collection = Collection::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'test-collection',
    ]);

    $products = Product::factory()->count(3)->create([
        'store_id' => $this->store->id,
    ]);

    foreach ($products as $i => $product) {
        $collection->products()->attach($product->id, ['position' => $i]);
    }

    // Reorder: move last to first
    $collection->products()->updateExistingPivot($products[0]->id, ['position' => 2]);
    $collection->products()->updateExistingPivot($products[1]->id, ['position' => 0]);
    $collection->products()->updateExistingPivot($products[2]->id, ['position' => 1]);

    $ordered = $collection->products()->orderByPivot('position')->get();
    expect($ordered->first()->id)->toBe($products[1]->id)
        ->and($ordered->last()->id)->toBe($products[0]->id);
});

it('transitions collection from draft to active', function () {
    $collection = Collection::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'draft-collection',
        'status' => 'draft',
    ]);

    $collection->update(['status' => CollectionStatus::Active]);

    $collection->refresh();
    expect($collection->status)->toBe(CollectionStatus::Active);
});

it('lists collections with product count', function () {
    $collectionA = Collection::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'collection-a',
    ]);

    $collectionB = Collection::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'collection-b',
    ]);

    $productsA = Product::factory()->count(5)->create(['store_id' => $this->store->id]);
    foreach ($productsA as $i => $p) {
        $collectionA->products()->attach($p->id, ['position' => $i]);
    }

    $productsB = Product::factory()->count(3)->create(['store_id' => $this->store->id]);
    foreach ($productsB as $i => $p) {
        $collectionB->products()->attach($p->id, ['position' => $i]);
    }

    $collections = Collection::withCount('products')->get();
    $a = $collections->firstWhere('id', $collectionA->id);
    $b = $collections->firstWhere('id', $collectionB->id);

    expect($a->products_count)->toBe(5)
        ->and($b->products_count)->toBe(3);
});

it('scopes collections to current store', function () {
    Collection::factory()->count(2)->create([
        'store_id' => $this->store->id,
    ]);

    $contextB = createStoreContext();
    $storeB = $contextB['store'];

    Collection::factory()->count(4)->create([
        'store_id' => $storeB->id,
    ]);

    app()->instance('current_store', $this->store);
    expect(Collection::count())->toBe(2);

    app()->instance('current_store', $storeB);
    expect(Collection::count())->toBe(4);
});
