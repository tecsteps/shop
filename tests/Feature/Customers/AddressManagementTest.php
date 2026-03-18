<?php

use App\Models\Customer;
use App\Models\CustomerAddress;

it('creates a customer address', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    $address = CustomerAddress::create([
        'customer_id' => $customer->id,
        'label' => 'Home',
        'address_json' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
        'is_default' => true,
    ]);

    expect($address->label)->toBe('Home')
        ->and($address->address_json['city'])->toBe('Berlin')
        ->and($address->is_default)->toBeTrue();
});

it('lists addresses for a customer', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    CustomerAddress::factory()->count(3)->create(['customer_id' => $customer->id]);

    expect($customer->addresses)->toHaveCount(3);
});

it('updates an existing address', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    $address = CustomerAddress::factory()->create([
        'customer_id' => $customer->id,
        'label' => 'Home',
    ]);

    $address->update(['label' => 'Work']);

    expect($address->fresh()->label)->toBe('Work');
});

it('deletes an address', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    $address = CustomerAddress::factory()->create(['customer_id' => $customer->id]);
    $id = $address->id;

    $address->delete();

    expect(CustomerAddress::find($id))->toBeNull();
});

it('cascades delete when customer is deleted', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    CustomerAddress::factory()->count(2)->create(['customer_id' => $customer->id]);

    expect(CustomerAddress::where('customer_id', $customer->id)->count())->toBe(2);

    $customer->delete();

    expect(CustomerAddress::where('customer_id', $customer->id)->count())->toBe(0);
});

it('sets default address', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    $addr1 = CustomerAddress::factory()->default()->create(['customer_id' => $customer->id]);
    $addr2 = CustomerAddress::factory()->create(['customer_id' => $customer->id]);

    expect($addr1->is_default)->toBeTrue()
        ->and($addr2->is_default)->toBeFalse();
});
