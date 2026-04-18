<?php

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Models\Collection;
use App\Models\Product;
use App\Support\HandleGenerator;

beforeEach(function (): void {
    $context = $this->createStoreContext();
    $this->store = $context['store'];
});

it('creates a collection with a unique handle', function (): void {
    $handle = HandleGenerator::generate('Summer Sale', 'collections', $this->store->id);

    $collection = Collection::query()->create([
        'store_id' => $this->store->id,
        'title' => 'Summer Sale',
        'handle' => $handle,
        'type' => CollectionType::Manual,
        'status' => CollectionStatus::Active,
    ]);

    expect($collection->handle)->toBe('summer-sale');
});

it('adds products to a collection', function (): void {
    $collection = Collection::factory()->create(['store_id' => $this->store->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $this->store->id]);

    $collection->products()->sync(
        $products->mapWithKeys(fn ($p, $i) => [$p->id => ['position' => $i]])->all()
    );

    expect($collection->products)->toHaveCount(3);
});

it('removes products from a collection', function (): void {
    $collection = Collection::factory()->create(['store_id' => $this->store->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $this->store->id]);
    $collection->products()->sync(
        $products->mapWithKeys(fn ($p, $i) => [$p->id => ['position' => $i]])->all()
    );

    $collection->products()->detach($products->first()->id);

    expect($collection->fresh()->products)->toHaveCount(2);
});

it('reorders products within a collection', function (): void {
    $collection = Collection::factory()->create(['store_id' => $this->store->id]);
    $products = Product::factory()->count(3)->create(['store_id' => $this->store->id]);
    $collection->products()->sync(
        $products->mapWithKeys(fn ($p, $i) => [$p->id => ['position' => $i]])->all()
    );

    $reorder = [
        $products[2]->id => ['position' => 0],
        $products[0]->id => ['position' => 1],
        $products[1]->id => ['position' => 2],
    ];
    $collection->products()->sync($reorder);

    $ordered = $collection->fresh()->products;
    expect($ordered->first()->id)->toBe($products[2]->id);
    expect($ordered->last()->id)->toBe($products[1]->id);
});

it('transitions collection from draft to active', function (): void {
    $collection = Collection::factory()->draft()->create(['store_id' => $this->store->id]);

    $collection->update(['status' => CollectionStatus::Active]);

    expect($collection->fresh()->status)->toBe(CollectionStatus::Active);
});

it('lists collections with product count', function (): void {
    $a = Collection::factory()->create(['store_id' => $this->store->id]);
    $b = Collection::factory()->create(['store_id' => $this->store->id]);

    $productsA = Product::factory()->count(5)->create(['store_id' => $this->store->id]);
    $productsB = Product::factory()->count(3)->create(['store_id' => $this->store->id]);

    $a->products()->sync($productsA->mapWithKeys(fn ($p, $i) => [$p->id => ['position' => $i]])->all());
    $b->products()->sync($productsB->mapWithKeys(fn ($p, $i) => [$p->id => ['position' => $i]])->all());

    $collections = Collection::query()->withCount('products')->get();
    $aRow = $collections->firstWhere('id', $a->id);
    $bRow = $collections->firstWhere('id', $b->id);

    expect($aRow->products_count)->toBe(5);
    expect($bRow->products_count)->toBe(3);
});

it('scopes collections to the current store', function (): void {
    Collection::factory()->count(2)->create(['store_id' => $this->store->id]);

    $other = $this->createStoreContext(['hostname' => 'other.test']);
    Collection::factory()->count(4)->create(['store_id' => $other['store']->id]);

    app()->instance('current_store', $this->store->fresh());
    expect(Collection::query()->count())->toBe(2);

    app()->instance('current_store', $other['store']->fresh());
    expect(Collection::query()->count())->toBe(4);
});
