<?php

use App\Models\Collection;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('creates a manual collection with products', function (): void {
    $collection = Collection::factory()->for($this->store)->create();
    $productA = Product::factory()->for($this->store)->create();
    $productB = Product::factory()->for($this->store)->create();

    $collection->products()->attach([
        $productA->id => ['position' => 0],
        $productB->id => ['position' => 1],
    ]);

    expect($collection->fresh()->products)->toHaveCount(2);
});

it('maintains product position in collection', function (): void {
    $collection = Collection::factory()->for($this->store)->create();
    $productA = Product::factory()->for($this->store)->create();
    $productB = Product::factory()->for($this->store)->create();

    $collection->products()->attach([
        $productA->id => ['position' => 1],
        $productB->id => ['position' => 0],
    ]);

    $ordered = $collection->products()->orderBy('collection_products.position')->get();

    expect($ordered->first()->id)->toBe($productB->id)
        ->and($ordered->last()->id)->toBe($productA->id);
});

it('scopes collections to current store', function (): void {
    $storeA = $this->store;
    $storeB = Store::factory()->create();

    app()->instance('current_store', $storeA);
    Collection::factory()->for($storeA)->create(['title' => 'Spring']);

    app()->instance('current_store', $storeB);
    Collection::factory()->for($storeB)->create(['title' => 'Autumn']);

    expect(Collection::count())->toBe(1)
        ->and(Collection::first()->title)->toBe('Autumn');

    app()->instance('current_store', $storeA);
    expect(Collection::count())->toBe(1)
        ->and(Collection::first()->title)->toBe('Spring');
});
