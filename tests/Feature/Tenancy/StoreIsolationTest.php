<?php

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Scopes\StoreScope;
use App\Models\Store;

it('scopes queries to the current store', function () {
    $contextA = createStoreContext('store-a.test');

    $customerA1 = Customer::factory()->create(['store_id' => $contextA['store']->id]);
    $customerA2 = Customer::factory()->create(['store_id' => $contextA['store']->id]);
    $customerA3 = Customer::factory()->create(['store_id' => $contextA['store']->id]);

    $orgB = Organization::factory()->create();
    $storeB = Store::factory()->create(['organization_id' => $orgB->id]);
    $customerB1 = Customer::factory()->create(['store_id' => $storeB->id]);
    $customerB2 = Customer::factory()->create(['store_id' => $storeB->id]);

    app()->instance('current_store', $contextA['store']);

    expect(Customer::count())->toBe(3);
});

it('automatically sets store_id on model creation', function () {
    $context = createStoreContext();

    $customer = Customer::create([
        'email' => 'test@example.com',
        'name' => 'Test Customer',
        'password' => 'password',
    ]);

    expect($customer->store_id)->toBe($context['store']->id);
});

it('prevents accessing another stores records via direct ID', function () {
    $context = createStoreContext('store-a.test');

    $orgB = Organization::factory()->create();
    $storeB = Store::factory()->create(['organization_id' => $orgB->id]);
    $customerInStoreB = Customer::factory()->create(['store_id' => $storeB->id]);

    app()->instance('current_store', $context['store']);

    expect(Customer::find($customerInStoreB->id))->toBeNull();
});

it('allows cross-store access when global scope is removed', function () {
    $context = createStoreContext('store-a.test');

    Customer::factory()->count(2)->create(['store_id' => $context['store']->id]);

    $orgB = Organization::factory()->create();
    $storeB = Store::factory()->create(['organization_id' => $orgB->id]);
    Customer::factory()->count(3)->create(['store_id' => $storeB->id]);

    app()->instance('current_store', $context['store']);

    $allCustomers = Customer::withoutGlobalScope(StoreScope::class)->count();

    expect($allCustomers)->toBe(5);
});
