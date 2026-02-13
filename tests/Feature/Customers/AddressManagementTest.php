<?php

use App\Livewire\Storefront\Account\Addresses\Index as AddressesIndex;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Livewire\Livewire;

it('lists saved addresses', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->withPassword()->create(['store_id' => $ctx['store']->id]);

    CustomerAddress::factory()->count(2)->create(['customer_id' => $customer->id]);

    actingAsCustomer($customer, $ctx['store']);

    Livewire::test(AddressesIndex::class)
        ->assertOk()
        ->assertSee('Address Book');
});

it('creates a new address', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->withPassword()->create(['store_id' => $ctx['store']->id]);

    actingAsCustomer($customer, $ctx['store']);

    Livewire::test(AddressesIndex::class)
        ->call('openForm')
        ->set('label', 'Home')
        ->set('firstName', 'John')
        ->set('lastName', 'Doe')
        ->set('address1', '123 Main St')
        ->set('city', 'Los Angeles')
        ->set('provinceCode', 'CA')
        ->set('countryCode', 'US')
        ->set('postalCode', '90001')
        ->call('saveAddress')
        ->assertHasNoErrors();

    expect($customer->addresses()->count())->toBe(1);

    $address = $customer->addresses()->first();
    expect($address->label)->toBe('Home')
        ->and($address->address_json['first_name'])->toBe('John');
});

it('updates an existing address', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->withPassword()->create(['store_id' => $ctx['store']->id]);

    $address = CustomerAddress::factory()->create([
        'customer_id' => $customer->id,
        'label' => 'Home',
    ]);

    actingAsCustomer($customer, $ctx['store']);

    Livewire::test(AddressesIndex::class)
        ->call('openForm', $address->id)
        ->set('label', 'Work')
        ->set('firstName', 'Jane')
        ->set('lastName', 'Smith')
        ->set('address1', '456 Office Blvd')
        ->set('city', 'San Francisco')
        ->set('provinceCode', 'CA')
        ->set('countryCode', 'US')
        ->set('postalCode', '94102')
        ->call('saveAddress')
        ->assertHasNoErrors();

    $address->refresh();
    expect($address->label)->toBe('Work')
        ->and($address->address_json['first_name'])->toBe('Jane');
});

it('deletes an address', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->withPassword()->create(['store_id' => $ctx['store']->id]);

    $address = CustomerAddress::factory()->create(['customer_id' => $customer->id]);

    actingAsCustomer($customer, $ctx['store']);

    Livewire::test(AddressesIndex::class)
        ->call('deleteAddress', $address->id);

    expect($customer->addresses()->count())->toBe(0);
});

it('sets a default address', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->withPassword()->create(['store_id' => $ctx['store']->id]);

    $address1 = CustomerAddress::factory()->create([
        'customer_id' => $customer->id,
        'is_default' => true,
    ]);
    $address2 = CustomerAddress::factory()->create([
        'customer_id' => $customer->id,
        'is_default' => false,
    ]);

    actingAsCustomer($customer, $ctx['store']);

    Livewire::test(AddressesIndex::class)
        ->call('setDefault', $address2->id);

    $address1->refresh();
    $address2->refresh();

    expect($address1->is_default)->toBeFalse()
        ->and($address2->is_default)->toBeTrue();
});

it('validates required address fields', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->withPassword()->create(['store_id' => $ctx['store']->id]);

    actingAsCustomer($customer, $ctx['store']);

    Livewire::test(AddressesIndex::class)
        ->call('openForm')
        ->set('label', '')
        ->set('firstName', '')
        ->set('lastName', '')
        ->set('address1', '')
        ->set('city', '')
        ->set('provinceCode', '')
        ->set('countryCode', '')
        ->set('postalCode', '')
        ->call('saveAddress')
        ->assertHasErrors(['label', 'firstName', 'lastName', 'address1', 'city', 'provinceCode', 'countryCode', 'postalCode']);
});

it('prevents managing another customers addresses', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->withPassword()->create(['store_id' => $ctx['store']->id]);
    $otherCustomer = Customer::factory()->withPassword()->create(['store_id' => $ctx['store']->id]);

    $otherAddress = CustomerAddress::factory()->create(['customer_id' => $otherCustomer->id]);

    actingAsCustomer($customer, $ctx['store']);

    // Attempting to edit another customer's address should not load it
    Livewire::test(AddressesIndex::class)
        ->call('openForm', $otherAddress->id)
        ->assertSet('editingAddressId', null);

    // Attempting to delete another customer's address should not delete it
    Livewire::test(AddressesIndex::class)
        ->call('deleteAddress', $otherAddress->id);

    expect(CustomerAddress::where('id', $otherAddress->id)->exists())->toBeTrue();
});
