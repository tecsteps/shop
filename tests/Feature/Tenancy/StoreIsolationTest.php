<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Scopes\StoreScope;
use App\Tenancy\CurrentStore;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('scopes product queries to the current store', function () {
    ['store' => $storeA] = $this->createStoreContext();
    ['store' => $storeB] = $this->createStoreContext();
    Product::factory()->count(3)->create(['store_id' => $storeA->id]);
    Product::factory()->count(5)->create(['store_id' => $storeB->id]);

    app(CurrentStore::class)->set($storeA);

    expect(Product::all())->toHaveCount(3);
});

it('scopes order queries to the current store', function () {
    ['store' => $storeA] = $this->createStoreContext();
    ['store' => $storeB] = $this->createStoreContext();
    Order::factory()->count(2)->create(['store_id' => $storeA->id]);
    Order::factory()->count(7)->create(['store_id' => $storeB->id]);

    app(CurrentStore::class)->set($storeA);

    expect(Order::query()->count())->toBe(2);
});

it('automatically sets store_id on model creation', function () {
    ['store' => $store] = $this->createStoreContext();

    $product = Product::query()->create([
        'title' => 'Auto-scoped product',
        'handle' => 'auto-scoped-product',
    ]);

    expect($product->store_id)->toBe($store->id);
});

it('prevents accessing another stores records via direct id', function () {
    ['store' => $storeA] = $this->createStoreContext();
    ['store' => $storeB] = $this->createStoreContext();
    $product = Product::factory()->create(['store_id' => $storeA->id]);

    app(CurrentStore::class)->set($storeB);

    expect(Product::query()->find($product->id))->toBeNull();
});

it('allows cross-store access when global scope is removed', function () {
    ['store' => $storeA] = $this->createStoreContext();
    ['store' => $storeB] = $this->createStoreContext();
    Product::factory()->count(3)->create(['store_id' => $storeA->id]);
    Product::factory()->count(5)->create(['store_id' => $storeB->id]);

    app(CurrentStore::class)->set($storeA);

    expect(Product::withoutGlobalScope(StoreScope::class)->count())->toBe(8);
});

it('scopes customers sharing the same email across stores', function () {
    ['store' => $storeA] = $this->createStoreContext();
    ['store' => $storeB] = $this->createStoreContext();
    Customer::factory()->create(['store_id' => $storeA->id, 'email' => 'sam@example.com']);
    Customer::factory()->create(['store_id' => $storeB->id, 'email' => 'sam@example.com']);

    app(CurrentStore::class)->set($storeB);

    $customers = Customer::all();

    expect($customers)->toHaveCount(1);
    expect($customers->first()->store_id)->toBe($storeB->id);
});

it('does not filter queries when no store is bound', function () {
    ['store' => $storeA] = $this->createStoreContext();
    ['store' => $storeB] = $this->createStoreContext();
    Product::factory()->count(2)->create(['store_id' => $storeA->id]);
    Product::factory()->count(2)->create(['store_id' => $storeB->id]);

    app(CurrentStore::class)->forget();

    expect(Product::query()->count())->toBe(4);
});

it('does not overwrite an explicit store_id on creation', function () {
    ['store' => $storeA] = $this->createStoreContext();
    ['store' => $storeB] = $this->createStoreContext();

    app(CurrentStore::class)->set($storeA);

    $product = Product::query()->create([
        'store_id' => $storeB->id,
        'title' => 'Explicit store product',
        'handle' => 'explicit-store-product',
    ]);

    expect($product->store_id)->toBe($storeB->id);
});
