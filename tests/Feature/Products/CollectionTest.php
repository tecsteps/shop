<?php

use App\Models\Collection;
use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
});

it('creates a collection with a store scoped unique handle', function () {
    $generator = app(HandleGenerator::class);
    $collection = Collection::factory()->for($this->store)->create([
        'title' => 'Summer Sale',
        'handle' => $generator->generate('Summer Sale', 'collections', $this->store->id),
    ]);

    expect($collection->handle)->toBe('summer-sale');
    $this->assertModelExists($collection);
});

it('adds removes and orders products through the collection pivot', function () {
    $collection = Collection::factory()->for($this->store)->create();
    $products = Product::factory()->for($this->store)->count(3)->create();
    $collection->products()->sync([
        $products[0]->id => ['position' => 2],
        $products[1]->id => ['position' => 0],
        $products[2]->id => ['position' => 1],
    ]);

    expect($collection->products()->pluck('products.id')->all())
        ->toBe([$products[1]->id, $products[2]->id, $products[0]->id]);

    $collection->products()->detach($products[2]);

    expect($collection->products()->count())->toBe(2);
});

it('reports product counts without loading product collections', function () {
    $collection = Collection::factory()->for($this->store)->create();
    $collection->products()->attach(Product::factory()->for($this->store)->count(3)->create());

    expect(Collection::withCount('products')->findOrFail($collection->id)->products_count)->toBe(3);
});

it('scopes collections to the current store', function () {
    Collection::factory()->for($this->store)->count(2)->create();
    $otherStore = Store::factory()->create();
    Collection::factory()->for($otherStore)->count(4)->create();

    expect(Collection::count())->toBe(2)
        ->and(Collection::withoutGlobalScopes()->count())->toBe(6);
});
