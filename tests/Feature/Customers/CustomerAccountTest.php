<?php

use App\Models\Customer;
use App\Models\Order;

it('creates a customer with basic fields', function () {
    $ctx = createStoreContext();

    $customer = Customer::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'new@example.com',
        'name' => 'New Customer',
        'marketing_opt_in' => true,
    ]);

    expect($customer->email)->toBe('new@example.com')
        ->and($customer->name)->toBe('New Customer')
        ->and($customer->marketing_opt_in)->toBeTrue();
});

it('enforces unique email per store', function () {
    $ctx = createStoreContext();

    Customer::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'dupe@example.com',
        'name' => 'First',
    ]);

    expect(fn () => Customer::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'dupe@example.com',
        'name' => 'Second',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

it('has orders relationship', function () {
    $ctx = createStoreContext();

    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);
    Order::factory()->count(3)->create([
        'store_id' => $ctx['store']->id,
        'customer_id' => $customer->id,
    ]);

    expect($customer->orders)->toHaveCount(3);
});

it('has addresses relationship', function () {
    $ctx = createStoreContext();

    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);
    \App\Models\CustomerAddress::factory()->count(2)->create([
        'customer_id' => $customer->id,
    ]);

    expect($customer->addresses)->toHaveCount(2);
});

it('has carts relationship', function () {
    $ctx = createStoreContext();

    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);
    \App\Models\Cart::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'customer_id' => $customer->id,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => 'active',
    ]);

    expect($customer->carts)->toHaveCount(1);
});
