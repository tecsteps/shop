<?php

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('creates a collection with a unique handle', function () {
    $generator = app(HandleGenerator::class);
    $handle = $generator->generate('Summer Sale', 'collections', $this->ctx['store']->id);

    $collection = Collection::create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Summer Sale',
        'handle' => $handle,
        'status' => CollectionStatus::Draft,
    ]);

    expect($collection->handle)->toBe('summer-sale');
    expect($collection->exists)->toBeTrue();
});

it('adds products to a collection', function () {
    $collection = Collection::factory()->create(['store_id' => $this->ctx['store']->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $this->ctx['store']->id]);

    foreach ($products as $i => $product) {
        $collection->products()->attach($product->id, ['position' => $i]);
    }

    expect($collection->products()->count())->toBe(3);
});

it('removes products from a collection', function () {
    $collection = Collection::factory()->create(['store_id' => $this->ctx['store']->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $this->ctx['store']->id]);

    foreach ($products as $i => $product) {
        $collection->products()->attach($product->id, ['position' => $i]);
    }

    $collection->products()->detach($products->first()->id);

    expect($collection->products()->count())->toBe(2);
});

it('reorders products within a collection', function () {
    $collection = Collection::factory()->create(['store_id' => $this->ctx['store']->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $this->ctx['store']->id]);

    foreach ($products as $i => $product) {
        $collection->products()->attach($product->id, ['position' => $i]);
    }

    // Reorder: move last product to first
    $collection->products()->updateExistingPivot($products[0]->id, ['position' => 2]);
    $collection->products()->updateExistingPivot($products[1]->id, ['position' => 0]);
    $collection->products()->updateExistingPivot($products[2]->id, ['position' => 1]);

    $ordered = $collection->products()->orderBy('collection_products.position')->pluck('products.id')->all();

    expect($ordered[0])->toBe($products[1]->id);
    expect($ordered[1])->toBe($products[2]->id);
    expect($ordered[2])->toBe($products[0]->id);
});

it('transitions collection from draft to active', function () {
    $collection = Collection::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'status' => CollectionStatus::Draft,
    ]);

    $collection->update([
        'status' => CollectionStatus::Active,
        'published_at' => now(),
    ]);

    $collection->refresh();
    expect($collection->status)->toBe(CollectionStatus::Active);
    expect($collection->published_at)->not->toBeNull();
});

it('lists collections with product count', function () {
    $collectionA = Collection::factory()->create(['store_id' => $this->ctx['store']->id]);
    $collectionB = Collection::factory()->create(['store_id' => $this->ctx['store']->id]);

    $productsA = Product::factory()->count(5)->create(['store_id' => $this->ctx['store']->id]);
    $productsB = Product::factory()->count(3)->create(['store_id' => $this->ctx['store']->id]);

    foreach ($productsA as $i => $p) {
        $collectionA->products()->attach($p->id, ['position' => $i]);
    }
    foreach ($productsB as $i => $p) {
        $collectionB->products()->attach($p->id, ['position' => $i]);
    }

    $collections = Collection::withCount('products')->get();

    $a = $collections->firstWhere('id', $collectionA->id);
    $b = $collections->firstWhere('id', $collectionB->id);

    expect($a->products_count)->toBe(5);
    expect($b->products_count)->toBe(3);
});

it('scopes collections to current store', function () {
    Collection::factory()->count(2)->create(['store_id' => $this->ctx['store']->id]);

    $otherStore = Store::factory()->create();
    Collection::factory()->count(4)->create(['store_id' => $otherStore->id]);

    expect(Collection::count())->toBe(2);
});
