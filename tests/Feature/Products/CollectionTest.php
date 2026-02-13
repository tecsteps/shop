<?php

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->handleGenerator = new HandleGenerator;
});

it('creates a collection with a unique handle', function () {
    $handle = $this->handleGenerator->generate('Summer Sale', 'collections', $this->store->id);

    $collection = Collection::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'title' => 'Summer Sale',
        'handle' => $handle,
    ]);

    expect($collection->title)->toBe('Summer Sale')
        ->and($collection->handle)->toBe('summer-sale');

    // Second one should get a suffix
    $handle2 = $this->handleGenerator->generate('Summer Sale', 'collections', $this->store->id);
    expect($handle2)->toBe('summer-sale-1');
});

it('adds products to a collection', function () {
    $collection = Collection::factory()->create(['store_id' => $this->store->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $this->store->id]);

    foreach ($products as $index => $product) {
        $collection->products()->attach($product->id, ['position' => $index]);
    }

    expect($collection->products)->toHaveCount(3);
});

it('removes products from a collection', function () {
    $collection = Collection::factory()->create(['store_id' => $this->store->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $this->store->id]);

    $collection->products()->attach($products->pluck('id'));
    $collection->products()->detach($products->first()->id);

    expect($collection->fresh()->products)->toHaveCount(2);
});

it('reorders products in a collection', function () {
    $collection = Collection::factory()->create(['store_id' => $this->store->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $this->store->id]);

    foreach ($products as $index => $product) {
        $collection->products()->attach($product->id, ['position' => $index]);
    }

    // Reorder: move last to first
    $collection->products()->updateExistingPivot($products->last()->id, ['position' => 0]);
    $collection->products()->updateExistingPivot($products->first()->id, ['position' => 2]);

    $ordered = $collection->fresh()->products()->orderBy('collection_products.position')->get();
    expect($ordered->first()->id)->toBe($products->last()->id);
});

it('transitions collection from draft to active', function () {
    $collection = Collection::factory()->draft()->create(['store_id' => $this->store->id]);

    expect($collection->status)->toBe(CollectionStatus::Draft);

    $collection->update(['status' => CollectionStatus::Active]);

    expect($collection->fresh()->status)->toBe(CollectionStatus::Active);
});

it('lists collections with product count', function () {
    $collection = Collection::factory()->create(['store_id' => $this->store->id]);
    Product::factory()->count(5)->create(['store_id' => $this->store->id])
        ->each(fn ($product) => $collection->products()->attach($product->id));

    $result = Collection::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->withCount('products')
        ->first();

    expect($result->products_count)->toBe(5);
});

it('scopes collections to store', function () {
    $org = Organization::factory()->create();
    $otherStore = Store::factory()->for($org)->create();

    Collection::factory()->count(2)->create(['store_id' => $this->store->id]);
    Collection::factory()->count(3)->create(['store_id' => $otherStore->id]);

    $storeCollections = Collection::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->get();

    expect($storeCollections)->toHaveCount(2);
});
