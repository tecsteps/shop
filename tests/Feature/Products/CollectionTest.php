<?php

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Models\Collection;
use App\Models\Product;

test('products attach to a collection with positions', function () {
    $store = $this->createStore();
    $collection = Collection::factory()->create(['store_id' => $store->id]);

    $first = Product::factory()->create(['store_id' => $store->id]);
    $second = Product::factory()->create(['store_id' => $store->id]);
    $third = Product::factory()->create(['store_id' => $store->id]);

    $collection->products()->attach($first->id, ['position' => 2]);
    $collection->products()->attach($second->id, ['position' => 0]);
    $collection->products()->attach($third->id, ['position' => 1]);

    $ordered = $collection->products()->pluck('products.id')->all();

    expect($ordered)->toBe([$second->id, $third->id, $first->id]);
});

test('products detach from a collection', function () {
    $store = $this->createStore();
    $collection = Collection::factory()->create(['store_id' => $store->id]);
    $product = Product::factory()->create(['store_id' => $store->id]);

    $collection->products()->attach($product->id, ['position' => 0]);

    expect($collection->products()->count())->toBe(1);

    $collection->products()->detach($product->id);

    expect($collection->products()->count())->toBe(0);
});

test('the inverse relation lists collections of a product ordered by position', function () {
    $store = $this->createStore();
    $product = Product::factory()->create(['store_id' => $store->id]);
    $collectionA = Collection::factory()->create(['store_id' => $store->id]);
    $collectionB = Collection::factory()->create(['store_id' => $store->id]);

    $product->collections()->attach($collectionA->id, ['position' => 1]);
    $product->collections()->attach($collectionB->id, ['position' => 0]);

    expect($product->collections()->pluck('collections.id')->all())
        ->toBe([$collectionB->id, $collectionA->id]);
});

test('collections are scoped to the bound store', function () {
    $storeA = $this->createStore();
    $storeB = $this->createStore();

    Collection::factory()->count(2)->create(['store_id' => $storeA->id]);
    Collection::factory()->count(3)->create(['store_id' => $storeB->id]);

    $this->bindStore($storeA);

    expect(Collection::query()->count())->toBe(2);
});

test('collection casts expose enums', function () {
    $collection = Collection::factory()->automated()->create();

    expect($collection->type)->toBe(CollectionType::Automated)
        ->and($collection->status)->toBe(CollectionStatus::Active);
});

test('pivot position is exposed on the relation', function () {
    $store = $this->createStore();
    $collection = Collection::factory()->create(['store_id' => $store->id]);
    $product = Product::factory()->create(['store_id' => $store->id]);

    $collection->products()->attach($product->id, ['position' => 5]);

    expect($collection->products->first()->pivot->position)->toBe(5);
});
