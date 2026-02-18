<?php

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Models\Scopes\StoreScope;
use App\Models\Store;

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('creates a collection with a unique handle', function () {
    $collection = Collection::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Summer Sale',
        'handle' => 'summer-sale',
    ]);

    expect($collection->handle)->toBe('summer-sale')
        ->and($collection->exists)->toBeTrue();
});

it('adds products to a collection', function () {
    $collection = Collection::factory()->create(['store_id' => $this->ctx['store']->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $this->ctx['store']->id]);

    foreach ($products as $i => $product) {
        $collection->products()->attach($product->id, ['position' => $i]);
    }

    expect($collection->products)->toHaveCount(3);
});

it('removes products from a collection', function () {
    $collection = Collection::factory()->create(['store_id' => $this->ctx['store']->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $this->ctx['store']->id]);

    foreach ($products as $i => $product) {
        $collection->products()->attach($product->id, ['position' => $i]);
    }

    $collection->products()->detach($products->first()->id);

    expect($collection->fresh()->products)->toHaveCount(2);
});

it('reorders products within a collection', function () {
    $collection = Collection::factory()->create(['store_id' => $this->ctx['store']->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $this->ctx['store']->id]);

    foreach ($products as $i => $product) {
        $collection->products()->attach($product->id, ['position' => $i]);
    }

    $collection->products()->updateExistingPivot($products[0]->id, ['position' => 2]);
    $collection->products()->updateExistingPivot($products[1]->id, ['position' => 0]);
    $collection->products()->updateExistingPivot($products[2]->id, ['position' => 1]);

    $reordered = $collection->fresh()->products()->orderByPivot('position')->get();

    expect($reordered[0]->id)->toBe($products[1]->id)
        ->and($reordered[1]->id)->toBe($products[2]->id)
        ->and($reordered[2]->id)->toBe($products[0]->id);
});

it('transitions collection from draft to active', function () {
    $collection = Collection::factory()->draft()->create(['store_id' => $this->ctx['store']->id]);

    $collection->update(['status' => CollectionStatus::Active]);

    expect($collection->fresh()->status)->toBe(CollectionStatus::Active);
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

    expect($collections->firstWhere('id', $collectionA->id)->products_count)->toBe(5)
        ->and($collections->firstWhere('id', $collectionB->id)->products_count)->toBe(3);
});

it('scopes collections to current store', function () {
    Collection::factory()->count(2)->create(['store_id' => $this->ctx['store']->id]);

    $storeB = Store::factory()->create(['organization_id' => $this->ctx['organization']->id]);
    Collection::factory()->count(4)->create(['store_id' => $storeB->id]);

    expect(Collection::count())->toBe(2);

    $allCount = Collection::withoutGlobalScope(StoreScope::class)->count();
    expect($allCount)->toBe(6);
});
