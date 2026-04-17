<?php

use App\Models\Customer;
use App\Models\Scopes\StoreScope;
use App\Models\Store;

it('scopes customer queries to the current store', function () {
    $ctxA = createStoreContext('store-a.test');
    Customer::factory()->count(3)->create(['store_id' => $ctxA['store']->id]);

    $storeB = Store::factory()->create(['organization_id' => $ctxA['organization']->id]);
    Customer::factory()->count(5)->create(['store_id' => $storeB->id]);

    app()->instance('current_store', $ctxA['store']);

    expect(Customer::count())->toBe(3);
});

it('automatically sets store_id on model creation', function () {
    $ctx = createStoreContext('auto-store.test');

    $customer = Customer::create([
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    expect($customer->store_id)->toBe($ctx['store']->id);
});

it('prevents accessing another stores records via direct ID', function () {
    $ctxA = createStoreContext('isolation-a.test');
    $customerA = Customer::factory()->create(['store_id' => $ctxA['store']->id]);

    $storeB = Store::factory()->create(['organization_id' => $ctxA['organization']->id]);
    app()->instance('current_store', $storeB);

    expect(Customer::find($customerA->id))->toBeNull();
});

it('allows cross-store access when global scope is removed', function () {
    $ctxA = createStoreContext('cross-a.test');
    Customer::factory()->count(3)->create(['store_id' => $ctxA['store']->id]);

    $storeB = Store::factory()->create(['organization_id' => $ctxA['organization']->id]);
    Customer::factory()->count(5)->create(['store_id' => $storeB->id]);

    app()->instance('current_store', $ctxA['store']);

    $allCount = Customer::withoutGlobalScope(StoreScope::class)->count();
    expect($allCount)->toBe(8);
});

it('scopes queries to the correct store when store changes', function () {
    $ctxA = createStoreContext('switch-a.test');
    Customer::factory()->count(2)->create(['store_id' => $ctxA['store']->id]);

    $storeB = Store::factory()->create(['organization_id' => $ctxA['organization']->id]);
    Customer::factory()->count(4)->create(['store_id' => $storeB->id]);

    app()->instance('current_store', $ctxA['store']);
    expect(Customer::count())->toBe(2);

    app()->instance('current_store', $storeB);
    expect(Customer::count())->toBe(4);
});
