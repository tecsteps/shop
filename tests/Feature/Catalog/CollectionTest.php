<?php

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Models\Collection;
use App\Models\Product;
use App\Models\Store;

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
});

it('creates a collection', function () {
    $collection = Collection::create([
        'store_id' => $this->store->id,
        'title' => 'Summer Sale',
        'handle' => 'summer-sale',
        'type' => CollectionType::Manual,
        'status' => CollectionStatus::Active,
    ]);

    expect($collection->title)->toBe('Summer Sale')
        ->and($collection->handle)->toBe('summer-sale')
        ->and($collection->type)->toBe(CollectionType::Manual)
        ->and($collection->status)->toBe(CollectionStatus::Active);
});

it('attaches products to a collection with position', function () {
    $collection = Collection::factory()->create(['store_id' => $this->store->id]);
    $product1 = Product::factory()->create(['store_id' => $this->store->id]);
    $product2 = Product::factory()->create(['store_id' => $this->store->id]);

    $collection->products()->attach($product1->id, ['position' => 0]);
    $collection->products()->attach($product2->id, ['position' => 1]);

    $products = $collection->fresh()->products;
    expect($products)->toHaveCount(2)
        ->and($products->first()->pivot->position)->toBe(0)
        ->and($products->last()->pivot->position)->toBe(1);
});

it('lists collections for a product', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $collection1 = Collection::factory()->create(['store_id' => $this->store->id]);
    $collection2 = Collection::factory()->create(['store_id' => $this->store->id]);

    $collection1->products()->attach($product->id);
    $collection2->products()->attach($product->id);

    expect($product->fresh()->collections)->toHaveCount(2);
});

it('scopes collections to the current store', function () {
    Collection::factory()->create(['store_id' => $this->store->id]);
    $otherStore = Store::factory()->create();
    Collection::factory()->create(['store_id' => $otherStore->id]);

    expect(Collection::count())->toBe(1);
});

it('creates a collection using the factory', function () {
    $collection = Collection::factory()->create(['store_id' => $this->store->id]);

    expect($collection)->toBeInstanceOf(Collection::class)
        ->and($collection->store_id)->toBe($this->store->id);
});

it('creates a draft collection using factory state', function () {
    $collection = Collection::factory()->draft()->create(['store_id' => $this->store->id]);

    expect($collection->status)->toBe(CollectionStatus::Draft);
});

it('supports automated collection type', function () {
    $collection = Collection::factory()->automated()->create(['store_id' => $this->store->id]);

    expect($collection->type)->toBe(CollectionType::Automated);
});

it('orders products within a collection by position', function () {
    $collection = Collection::factory()->create(['store_id' => $this->store->id]);
    $product1 = Product::factory()->create(['store_id' => $this->store->id]);
    $product2 = Product::factory()->create(['store_id' => $this->store->id]);
    $product3 = Product::factory()->create(['store_id' => $this->store->id]);

    $collection->products()->attach($product3->id, ['position' => 2]);
    $collection->products()->attach($product1->id, ['position' => 0]);
    $collection->products()->attach($product2->id, ['position' => 1]);

    $ordered = $collection->fresh()->products;
    expect($ordered->first()->id)->toBe($product1->id)
        ->and($ordered->last()->id)->toBe($product3->id);
});
