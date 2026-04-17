<?php

use App\Models\Customer;
use App\Models\Store;

it('StoreScope filters queries by current store', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();

    Customer::withoutGlobalScopes()->create([
        'store_id' => $storeA->id,
        'email' => 'a@example.com',
        'name' => 'Customer A',
    ]);
    Customer::withoutGlobalScopes()->create([
        'store_id' => $storeB->id,
        'email' => 'b@example.com',
        'name' => 'Customer B',
    ]);

    app()->instance('current_store', $storeA);

    $customers = Customer::all();
    expect($customers)->toHaveCount(1);
    expect($customers->first()->email)->toBe('a@example.com');
});

it('auto-sets store_id on creating when current_store is bound', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    $customer = Customer::create([
        'email' => 'test@example.com',
        'name' => 'Test',
        'password' => 'password',
    ]);

    expect($customer->store_id)->toBe($store->id);
});

it('does not filter by store_id when no current store is bound', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();

    Customer::withoutGlobalScopes()->create([
        'store_id' => $storeA->id,
        'email' => 'a@example.com',
        'name' => 'A',
    ]);
    Customer::withoutGlobalScopes()->create([
        'store_id' => $storeB->id,
        'email' => 'b@example.com',
        'name' => 'B',
    ]);

    // No current_store bound
    $customers = Customer::withoutGlobalScopes()->get();
    expect($customers)->toHaveCount(2);
});
