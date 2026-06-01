<?php

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Support\HandleGenerator;

beforeEach(function () {
    $context = createStoreContext();
    $this->store = $context['store'];
    $this->handles = app(HandleGenerator::class);
});

it('creates a collection with a unique handle', function () {
    $collection = Collection::create([
        'title' => 'Summer Sale',
        'handle' => $this->handles->generate('Summer Sale', 'collections', $this->store->id),
        'status' => CollectionStatus::Active->value,
    ]);

    expect($collection->handle)->toBe('summer-sale')
        ->and(Collection::whereKey($collection->id)->exists())->toBeTrue();
});

it('adds products to a collection', function () {
    $collection = Collection::factory()->create(['store_id' => $this->store->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $this->store->id]);

    $collection->products()->attach(
        $products->mapWithKeys(fn ($product, $i) => [$product->id => ['position' => $i]])->all(),
    );

    expect($collection->products()->count())->toBe(3);
});

it('removes products from a collection', function () {
    $collection = Collection::factory()->create(['store_id' => $this->store->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $this->store->id]);
    $collection->products()->attach($products->pluck('id')->all());

    $collection->products()->detach($products->first()->id);

    expect($collection->products()->count())->toBe(2);
});

it('reorders products within a collection', function () {
    $collection = Collection::factory()->create(['store_id' => $this->store->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $this->store->id]);

    [$a, $b, $c] = [$products[0], $products[1], $products[2]];
    $collection->products()->attach([
        $a->id => ['position' => 0],
        $b->id => ['position' => 1],
        $c->id => ['position' => 2],
    ]);

    // Reorder to c, a, b.
    foreach ([$c->id => 0, $a->id => 1, $b->id => 2] as $productId => $position) {
        $collection->products()->updateExistingPivot($productId, ['position' => $position]);
    }

    $ordered = $collection->products()->pluck('products.id')->all();

    expect($ordered)->toBe([$c->id, $a->id, $b->id]);
});

it('transitions collection from draft to active', function () {
    $collection = Collection::factory()->draft()->create(['store_id' => $this->store->id]);

    $collection->update(['status' => CollectionStatus::Active->value]);

    expect($collection->fresh()->status)->toBe(CollectionStatus::Active);
});

it('lists collections with product count', function () {
    $collectionA = Collection::factory()->create(['store_id' => $this->store->id]);
    $collectionB = Collection::factory()->create(['store_id' => $this->store->id]);

    $collectionA->products()->attach(Product::factory()->count(5)->create(['store_id' => $this->store->id])->pluck('id')->all());
    $collectionB->products()->attach(Product::factory()->count(3)->create(['store_id' => $this->store->id])->pluck('id')->all());

    $collections = Collection::withCount('products')->get()->keyBy('id');

    expect($collections[$collectionA->id]->products_count)->toBe(5)
        ->and($collections[$collectionB->id]->products_count)->toBe(3);
});

it('scopes collections to current store', function () {
    $otherContext = createStoreContext(['hostname' => 'other.test', 'handle' => 'other-store', 'bind' => false]);

    Collection::factory()->count(2)->create(['store_id' => $this->store->id]);
    Collection::factory()->count(4)->create(['store_id' => $otherContext['store']->id]);

    // current_store is still $this->store (bound in beforeEach).
    expect(Collection::count())->toBe(2);
});
