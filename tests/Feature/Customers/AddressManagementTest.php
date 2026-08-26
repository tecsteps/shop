<?php

use App\Models\Customer;
use App\Models\CustomerAddress;

it('lists saved addresses', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);
    CustomerAddress::factory()->count(2)->create(['customer_id' => $customer->id]);

    $this->actingAs($customer, 'customer')->get('/account/addresses')
        ->assertStatus(200);
});

it('creates a new address', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    $address = CustomerAddress::create([
        'customer_id' => $customer->id,
        'label' => 'Home',
        'address_json' => ['first_name' => 'Jane', 'last_name' => 'Doe', 'address1' => 'Main 1', 'city' => 'Berlin', 'country_code' => 'DE', 'postal_code' => '10115'],
        'is_default' => true,
    ]);

    expect(CustomerAddress::where('customer_id', $customer->id)->count())->toBe(1);
    expect($address->address_json['city'])->toBe('Berlin');
});

it('updates an existing address', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);
    $address = CustomerAddress::factory()->create(['customer_id' => $customer->id, 'address_json' => ['city' => 'Berlin']]);

    $address->update(['address_json' => array_merge($address->address_json, ['city' => 'Munich'])]);

    expect($address->fresh()->address_json['city'])->toBe('Munich');
});

it('deletes an address', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);
    $address = CustomerAddress::factory()->create(['customer_id' => $customer->id]);

    $address->delete();

    expect(CustomerAddress::where('customer_id', $customer->id)->exists())->toBeFalse();
});

it('sets a default address', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);
    $a = CustomerAddress::factory()->create(['customer_id' => $customer->id, 'is_default' => true]);
    $b = CustomerAddress::factory()->create(['customer_id' => $customer->id, 'is_default' => false]);

    CustomerAddress::where('customer_id', $customer->id)->update(['is_default' => false]);
    $b->update(['is_default' => true]);

    expect($b->fresh()->is_default)->toBeTrue();
    expect($a->fresh()->is_default)->toBeFalse();
});
