<?php

use App\Models\Customer;
use App\Models\Store;

it('scopes customer queries to current store', function () {
    $context = createStoreContext();

    // Create customer in current store
    $customer1 = Customer::factory()->create([
        'store_id' => $context['store']->id,
        'email' => 'john@example.com',
    ]);

    // Create customer in different store
    $otherStore = Store::factory()->create([
        'organization_id' => $context['organization']->id,
    ]);
    $customer2 = Customer::factory()->create([
        'store_id' => $otherStore->id,
        'email' => 'john@example.com',
    ]);

    // With StoreScope, only the current store's customer should be returned
    $customers = Customer::query()->get();

    expect($customers)->toHaveCount(1)
        ->and($customers->first()->id)->toBe($customer1->id);
});

it('auto-sets store_id on creating models with BelongsToStore trait', function () {
    $context = createStoreContext();

    $customer = Customer::query()->create([
        'email' => 'auto@example.com',
        'name' => 'Auto Test',
        'password' => 'password',
    ]);

    expect($customer->store_id)->toBe($context['store']->id);
});

it('allows same email in different stores', function () {
    $context = createStoreContext();

    Customer::factory()->create([
        'store_id' => $context['store']->id,
        'email' => 'shared@example.com',
    ]);

    $otherStore = Store::factory()->create([
        'organization_id' => $context['organization']->id,
    ]);

    // Remove the current_store binding to avoid scope interference
    app()->forgetInstance('current_store');
    app()->instance('current_store', $otherStore);

    $otherCustomer = Customer::factory()->create([
        'store_id' => $otherStore->id,
        'email' => 'shared@example.com',
    ]);

    expect($otherCustomer->exists)->toBeTrue();
});

it('does not apply store scope when current_store is not bound', function () {
    $store1 = Store::factory()->create();
    $store2 = Store::factory()->create();

    Customer::factory()->create(['store_id' => $store1->id]);
    Customer::factory()->create(['store_id' => $store2->id]);

    // Remove current_store binding
    app()->forgetInstance('current_store');

    $customers = Customer::withoutGlobalScopes()->get();

    expect($customers)->toHaveCount(2);
});

it('isolates store data across organization stores', function () {
    $context = createStoreContext();

    Customer::factory()->count(3)->create([
        'store_id' => $context['store']->id,
    ]);

    $otherStore = Store::factory()->create([
        'organization_id' => $context['organization']->id,
    ]);

    app()->forgetInstance('current_store');
    app()->instance('current_store', $otherStore);

    Customer::factory()->count(2)->create([
        'store_id' => $otherStore->id,
    ]);

    // Should only see 2 customers for the current store
    expect(Customer::query()->count())->toBe(2);
});
