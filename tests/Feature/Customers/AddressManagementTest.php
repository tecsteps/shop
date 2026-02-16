<?php

use App\Models\Customer;
use App\Models\CustomerAddress;

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('lists saved addresses', function () {
    $customer = Customer::factory()->for($this->ctx['store'])->create();
    CustomerAddress::factory()->count(2)->for($customer)->create();

    $response = actingAsCustomer($customer)
        ->withHeader('Host', 'test-store.test')
        ->get('/account/addresses');

    $response->assertStatus(200);
});

it('creates a new address', function () {
    $customer = Customer::factory()->for($this->ctx['store'])->create();

    $address = CustomerAddress::factory()->for($customer)->create([
        'city' => 'Berlin',
        'country_code' => 'DE',
    ]);

    expect($address->city)->toBe('Berlin')
        ->and($address->customer_id)->toBe($customer->id);
});

it('updates an existing address', function () {
    $customer = Customer::factory()->for($this->ctx['store'])->create();
    $address = CustomerAddress::factory()->for($customer)->create();

    $address->update(['city' => 'Munich']);

    expect($address->fresh()->city)->toBe('Munich');
});

it('deletes an address', function () {
    $customer = Customer::factory()->for($this->ctx['store'])->create();
    $address = CustomerAddress::factory()->for($customer)->create();

    $address->delete();

    expect(CustomerAddress::find($address->id))->toBeNull();
});

it('sets a default address', function () {
    $customer = Customer::factory()->for($this->ctx['store'])->create();
    $addr1 = CustomerAddress::factory()->for($customer)->create(['is_default' => true]);
    $addr2 = CustomerAddress::factory()->for($customer)->create(['is_default' => false]);

    $addr2->update(['is_default' => true]);
    $addr1->update(['is_default' => false]);

    expect($addr2->fresh()->is_default)->toBeTrue()
        ->and($addr1->fresh()->is_default)->toBeFalse();
});
