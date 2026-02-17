<?php

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $ctx = createStoreContext('address-test.test');
    $this->store = $ctx['store'];

    $this->customer = Customer::create([
        'store_id' => $this->store->id,
        'email' => 'addr-customer@example.com',
        'password_hash' => Hash::make('password'),
        'name' => 'Address Customer',
        'marketing_opt_in' => false,
    ]);
});

test('customer can view address book', function () {
    $this->actingAs($this->customer, 'customer')
        ->get('http://address-test.test/account/addresses')
        ->assertOk()
        ->assertSee('Address Book');
});

test('customer can add a new address', function () {
    Livewire::actingAs($this->customer, 'customer')
        ->test(\App\Livewire\Storefront\Account\Addresses\Index::class)
        ->call('openNewForm')
        ->set('firstName', 'John')
        ->set('lastName', 'Doe')
        ->set('address1', '123 Main St')
        ->set('city', 'Berlin')
        ->set('countryCode', 'DE')
        ->set('zip', '10115')
        ->call('save');

    $this->assertDatabaseHas('customer_addresses', [
        'customer_id' => $this->customer->id,
    ]);
});

test('customer can edit an existing address', function () {
    $address = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
    ]);

    Livewire::actingAs($this->customer, 'customer')
        ->test(\App\Livewire\Storefront\Account\Addresses\Index::class)
        ->call('editAddress', $address->id)
        ->set('firstName', 'Updated')
        ->set('lastName', 'Name')
        ->set('address1', '456 Oak Ave')
        ->set('city', 'Munich')
        ->set('countryCode', 'DE')
        ->set('zip', '80331')
        ->call('save');

    $address->refresh();
    expect($address->address_json['first_name'])->toBe('Updated');
});

test('customer can delete an address', function () {
    $address = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
    ]);

    Livewire::actingAs($this->customer, 'customer')
        ->test(\App\Livewire\Storefront\Account\Addresses\Index::class)
        ->call('deleteAddress', $address->id);

    $this->assertDatabaseMissing('customer_addresses', ['id' => $address->id]);
});

test('customer can set default address', function () {
    $address1 = CustomerAddress::factory()->default()->create([
        'customer_id' => $this->customer->id,
    ]);
    $address2 = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
    ]);

    Livewire::actingAs($this->customer, 'customer')
        ->test(\App\Livewire\Storefront\Account\Addresses\Index::class)
        ->call('setDefault', $address2->id);

    $address1->refresh();
    $address2->refresh();
    expect($address1->is_default)->toBeFalse();
    expect($address2->is_default)->toBeTrue();
});

test('address form validation requires mandatory fields', function () {
    Livewire::actingAs($this->customer, 'customer')
        ->test(\App\Livewire\Storefront\Account\Addresses\Index::class)
        ->call('openNewForm')
        ->set('firstName', '')
        ->set('lastName', '')
        ->set('address1', '')
        ->set('city', '')
        ->set('countryCode', '')
        ->set('zip', '')
        ->call('save')
        ->assertHasErrors(['firstName', 'lastName', 'address1', 'city', 'countryCode', 'zip']);
});

test('customer cannot access another customers address', function () {
    $otherCustomer = Customer::create([
        'store_id' => $this->store->id,
        'email' => 'other-addr@example.com',
        'password_hash' => Hash::make('password'),
        'name' => 'Other',
        'marketing_opt_in' => false,
    ]);
    $otherAddress = CustomerAddress::factory()->create([
        'customer_id' => $otherCustomer->id,
    ]);

    Livewire::actingAs($this->customer, 'customer')
        ->test(\App\Livewire\Storefront\Account\Addresses\Index::class)
        ->call('deleteAddress', $otherAddress->id);

    $this->assertDatabaseHas('customer_addresses', ['id' => $otherAddress->id]);
});
