<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Scopes\StoreScope;
use App\Models\Store;

it('scopes customer queries to the current store', function () {
    $ctx = createStoreContext();
    $storeA = $ctx['store'];

    Customer::factory()->count(3)->create(['store_id' => $storeA->id]);

    $orgB = Organization::factory()->create();
    $storeB = Store::factory()->create(['organization_id' => $orgB->id]);
    Customer::factory()->count(5)->create(['store_id' => $storeB->id]);

    app()->instance('current_store', $storeA);

    expect(Customer::count())->toBe(3);
});

it('automatically sets store_id on model creation', function () {
    $ctx = createStoreContext();
    $storeA = $ctx['store'];

    app()->instance('current_store', $storeA);

    $customer = Customer::create([
        'email' => 'test@example.com',
        'name' => 'Test Customer',
    ]);

    expect($customer->store_id)->toBe($storeA->id);
});

it('prevents accessing another stores records via direct ID', function () {
    $ctx = createStoreContext();
    $storeA = $ctx['store'];

    $orgB = Organization::factory()->create();
    $storeB = Store::factory()->create(['organization_id' => $orgB->id]);
    $customer = Customer::factory()->create(['store_id' => $storeB->id]);

    app()->instance('current_store', $storeA);

    expect(Customer::find($customer->id))->toBeNull();
});

it('allows cross-store access when global scope is removed', function () {
    $ctx = createStoreContext();
    $storeA = $ctx['store'];

    Customer::factory()->count(3)->create(['store_id' => $storeA->id]);

    $orgB = Organization::factory()->create();
    $storeB = Store::factory()->create(['organization_id' => $orgB->id]);
    Customer::factory()->count(5)->create(['store_id' => $storeB->id]);

    app()->instance('current_store', $storeA);

    expect(Customer::withoutGlobalScope(StoreScope::class)->count())->toBe(8);
});

it('scopes order queries to the current store', function () {
    $ctx = createStoreContext();
    $storeA = $ctx['store'];

    Order::factory()->count(2)->create(['store_id' => $storeA->id]);

    $orgB = Organization::factory()->create();
    $storeB = Store::factory()->create(['organization_id' => $orgB->id]);
    Order::factory()->count(4)->create(['store_id' => $storeB->id]);

    app()->instance('current_store', $storeA);

    expect(Order::count())->toBe(2);
});
