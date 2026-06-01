<?php

use App\Models\Customer;
use App\Models\Scopes\StoreScope;

/*
|--------------------------------------------------------------------------
| Store isolation
|--------------------------------------------------------------------------
|
| The StoreScope / BelongsToStore mechanism is exercised here through the
| Customer model, which is the store-scoped model available in Phase 1. Every
| Phase 2+ store-scoped model (products, orders, collections, ...) opts into the
| exact same trait, so it inherits identical isolation behavior.
|
*/

it('scopes queries to the current store', function () {
    $storeA = createStoreContext(['hostname' => 'a.test', 'bind' => false]);
    $storeB = createStoreContext(['hostname' => 'b.test', 'bind' => false]);

    Customer::factory()->count(3)->create(['store_id' => $storeA['store']->id]);
    Customer::factory()->count(5)->create(['store_id' => $storeB['store']->id]);

    bindCurrentStore($storeA['store']);

    expect(Customer::count())->toBe(3);
});

it('scopes queries to the current store for a second store', function () {
    $storeA = createStoreContext(['hostname' => 'a.test', 'bind' => false]);
    $storeB = createStoreContext(['hostname' => 'b.test', 'bind' => false]);

    Customer::factory()->count(2)->create(['store_id' => $storeA['store']->id]);
    Customer::factory()->count(7)->create(['store_id' => $storeB['store']->id]);

    bindCurrentStore($storeB['store']);

    expect(Customer::count())->toBe(7);
});

it('automatically sets store_id on model creation', function () {
    $storeA = createStoreContext(['bind' => true]);

    $customer = Customer::create([
        'email' => 'auto@example.test',
        'name' => 'Auto',
        'password_hash' => 'x',
    ]);

    expect($customer->store_id)->toBe($storeA['store']->id);
});

it('prevents accessing another stores records via direct ID', function () {
    $storeA = createStoreContext(['hostname' => 'a.test', 'bind' => false]);
    $storeB = createStoreContext(['hostname' => 'b.test', 'bind' => false]);

    $customer = Customer::factory()->create(['store_id' => $storeA['store']->id]);

    bindCurrentStore($storeB['store']);

    expect(Customer::find($customer->id))->toBeNull();
});

it('allows cross-store access when global scope is removed', function () {
    $storeA = createStoreContext(['hostname' => 'a.test', 'bind' => false]);
    $storeB = createStoreContext(['hostname' => 'b.test', 'bind' => false]);

    Customer::factory()->count(3)->create(['store_id' => $storeA['store']->id]);
    Customer::factory()->count(5)->create(['store_id' => $storeB['store']->id]);

    bindCurrentStore($storeA['store']);

    expect(Customer::withoutGlobalScope(StoreScope::class)->count())->toBe(8);
});
