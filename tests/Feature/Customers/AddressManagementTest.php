<?php

use App\Livewire\Storefront\Account\Addresses\Index as AddressesIndex;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    StoreDomain::factory()->create([
        'store_id' => $this->store->id,
        'hostname' => 'shop.test',
        'type' => 'storefront',
        'is_primary' => true,
    ]);
    $this->customer = Customer::factory()->create([
        'store_id' => $this->store->id,
    ]);
    app()->instance('current_store', $this->store);
});

test('lists customer addresses', function () {
    $address = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address1' => '123 Main St',
        'city' => 'Springfield',
    ]);

    $this->actingAs($this->customer, 'customer');

    $response = Livewire::test(AddressesIndex::class)
        ->assertSee('John')
        ->assertSee('Doe')
        ->assertSee('123 Main St')
        ->assertSee('Springfield');
});

test('creates a new address', function () {
    $this->actingAs($this->customer, 'customer');

    Livewire::test(AddressesIndex::class)
        ->call('createAddress')
        ->set('first_name', 'Jane')
        ->set('last_name', 'Smith')
        ->set('address1', '456 Oak Ave')
        ->set('city', 'Portland')
        ->set('country_code', 'US')
        ->set('postal_code', '97201')
        ->call('saveAddress')
        ->assertHasNoErrors();

    $address = CustomerAddress::where('customer_id', $this->customer->id)->first();

    expect($address)->not->toBeNull();
    expect($address->first_name)->toBe('Jane');
    expect($address->last_name)->toBe('Smith');
    expect($address->address1)->toBe('456 Oak Ave');
    expect($address->city)->toBe('Portland');
});

test('updates an existing address', function () {
    $address = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
        'first_name' => 'Old',
        'last_name' => 'Name',
        'address1' => 'Old Address',
        'city' => 'Old City',
        'country_code' => 'US',
        'postal_code' => '12345',
    ]);

    $this->actingAs($this->customer, 'customer');

    Livewire::test(AddressesIndex::class)
        ->call('editAddress', $address->id)
        ->set('first_name', 'Updated')
        ->set('last_name', 'Person')
        ->set('address1', 'New Address')
        ->set('city', 'New City')
        ->call('saveAddress')
        ->assertHasNoErrors();

    $address->refresh();

    expect($address->first_name)->toBe('Updated');
    expect($address->last_name)->toBe('Person');
    expect($address->address1)->toBe('New Address');
    expect($address->city)->toBe('New City');
});

test('deletes an address', function () {
    $address = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
    ]);

    $this->actingAs($this->customer, 'customer');

    Livewire::test(AddressesIndex::class)
        ->call('deleteAddress', $address->id);

    expect(CustomerAddress::find($address->id))->toBeNull();
});

test('sets an address as default', function () {
    $address1 = CustomerAddress::factory()->default()->create([
        'customer_id' => $this->customer->id,
    ]);
    $address2 = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
    ]);

    $this->actingAs($this->customer, 'customer');

    Livewire::test(AddressesIndex::class)
        ->call('setDefault', $address2->id);

    $address1->refresh();
    $address2->refresh();

    expect($address1->is_default)->toBeFalse();
    expect($address2->is_default)->toBeTrue();
});

test('requires authentication for addresses page', function () {
    $response = $this->withHeader('Host', 'shop.test')
        ->get('/account/addresses');

    $response->assertRedirect();
});
