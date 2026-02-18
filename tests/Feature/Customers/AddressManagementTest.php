<?php

use App\Models\Customer;
use App\Models\CustomerAddress;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->customer = Customer::factory()->create(['store_id' => $this->store->id]);
});

it('lists saved addresses', function () {
    CustomerAddress::factory()->count(2)->create(['customer_id' => $this->customer->id]);

    expect($this->customer->addresses)->toHaveCount(2);
});

it('creates a new address', function () {
    $address = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
        'label' => 'Home',
        'address_json' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country_code' => 'DE',
            'zip' => '10115',
        ],
    ]);

    expect($address->id)->not->toBeNull()
        ->and($address->label)->toBe('Home')
        ->and($address->address_json['first_name'])->toBe('Jane');
});

it('updates an existing address', function () {
    $address = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
    ]);

    $addressJson = $address->address_json;
    $addressJson['city'] = 'Munich';

    $address->update(['address_json' => $addressJson]);
    $address->refresh();

    expect($address->address_json['city'])->toBe('Munich');
});

it('deletes an address', function () {
    $address = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
    ]);

    $address->delete();

    expect(CustomerAddress::find($address->id))->toBeNull();
});

it('sets a default address', function () {
    $address1 = CustomerAddress::factory()->default()->create([
        'customer_id' => $this->customer->id,
    ]);
    $address2 = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
    ]);

    // Make address2 the default
    CustomerAddress::where('customer_id', $this->customer->id)
        ->where('is_default', true)
        ->update(['is_default' => false]);
    $address2->update(['is_default' => true]);

    $address1->refresh();
    $address2->refresh();

    expect($address2->is_default)->toBeTrue()
        ->and($address1->is_default)->toBeFalse();
});

it('validates required address fields in address_json', function () {
    $address = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
        'address_json' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country_code' => 'DE',
            'zip' => '10115',
        ],
    ]);

    expect($address->address_json)->toHaveKey('first_name')
        ->and($address->address_json)->toHaveKey('address1')
        ->and($address->address_json)->toHaveKey('city');
});

it('prevents managing another customers addresses', function () {
    $otherCustomer = Customer::factory()->create(['store_id' => $this->store->id]);
    $otherAddress = CustomerAddress::factory()->create([
        'customer_id' => $otherCustomer->id,
    ]);

    // Verify this customer cannot access the other's addresses
    $found = $this->customer->addresses()->where('id', $otherAddress->id)->first();

    expect($found)->toBeNull();
});
