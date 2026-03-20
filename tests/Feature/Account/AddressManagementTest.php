<?php

use App\Livewire\Storefront\Account\Addresses\Index as AddressesIndex;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->customer = Customer::factory()->create([
        'store_id' => $this->context['store']->id,
        'email' => 'customer@test.com',
        'password' => Hash::make('password'),
        'name' => 'Test Customer',
    ]);
});

it('renders the addresses page', function () {
    $hostname = $this->context['domain']->hostname;

    $this->actingAs($this->customer, 'customer')
        ->get("http://{$hostname}/account/addresses")
        ->assertOk()
        ->assertSeeLivewire(AddressesIndex::class);
});

it('creates a new address', function () {
    Livewire::actingAs($this->customer, 'customer')
        ->test(AddressesIndex::class)
        ->call('openCreateForm')
        ->set('label', 'Home')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('address1', '123 Main St')
        ->set('city', 'Springfield')
        ->set('province', 'IL')
        ->set('country', 'US')
        ->set('zip', '62701')
        ->call('saveAddress')
        ->assertSet('showForm', false);

    $address = CustomerAddress::where('customer_id', $this->customer->id)->first();
    expect($address)->not->toBeNull()
        ->and($address->label)->toBe('Home')
        ->and($address->address_json['first_name'])->toBe('John')
        ->and($address->address_json['city'])->toBe('Springfield');
});

it('validates required address fields', function () {
    Livewire::actingAs($this->customer, 'customer')
        ->test(AddressesIndex::class)
        ->call('openCreateForm')
        ->set('label', '')
        ->set('first_name', '')
        ->set('last_name', '')
        ->set('address1', '')
        ->set('city', '')
        ->set('zip', '')
        ->call('saveAddress')
        ->assertHasErrors(['label', 'first_name', 'last_name', 'address1', 'city', 'zip']);
});

it('edits an existing address', function () {
    $address = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
        'label' => 'Home',
    ]);

    Livewire::actingAs($this->customer, 'customer')
        ->test(AddressesIndex::class)
        ->call('editAddress', $address->id)
        ->assertSet('editingAddressId', $address->id)
        ->assertSet('label', 'Home')
        ->set('label', 'Work')
        ->set('first_name', 'Jane')
        ->set('last_name', 'Smith')
        ->set('address1', '456 Oak Ave')
        ->set('city', 'Portland')
        ->set('zip', '97201')
        ->call('saveAddress');

    $address->refresh();
    expect($address->label)->toBe('Work')
        ->and($address->address_json['first_name'])->toBe('Jane')
        ->and($address->address_json['city'])->toBe('Portland');
});

it('deletes an address', function () {
    $address = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
    ]);

    Livewire::actingAs($this->customer, 'customer')
        ->test(AddressesIndex::class)
        ->call('deleteAddress', $address->id);

    expect(CustomerAddress::find($address->id))->toBeNull();
});

it('sets an address as default', function () {
    $address1 = CustomerAddress::factory()->default()->create([
        'customer_id' => $this->customer->id,
    ]);
    $address2 = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
    ]);

    Livewire::actingAs($this->customer, 'customer')
        ->test(AddressesIndex::class)
        ->call('setDefault', $address2->id);

    $address1->refresh();
    $address2->refresh();

    expect($address1->is_default)->toBeFalse()
        ->and($address2->is_default)->toBeTrue();
});

it('clears other defaults when creating a default address', function () {
    $existing = CustomerAddress::factory()->default()->create([
        'customer_id' => $this->customer->id,
    ]);

    Livewire::actingAs($this->customer, 'customer')
        ->test(AddressesIndex::class)
        ->call('openCreateForm')
        ->set('label', 'Office')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('address1', '789 Elm St')
        ->set('city', 'Austin')
        ->set('country', 'US')
        ->set('zip', '73301')
        ->set('is_default', true)
        ->call('saveAddress');

    $existing->refresh();
    expect($existing->is_default)->toBeFalse();

    $newAddress = CustomerAddress::where('customer_id', $this->customer->id)
        ->where('label', 'Office')
        ->first();
    expect($newAddress->is_default)->toBeTrue();
});

it('cancels the address form', function () {
    Livewire::actingAs($this->customer, 'customer')
        ->test(AddressesIndex::class)
        ->call('openCreateForm')
        ->assertSet('showForm', true)
        ->set('label', 'Test')
        ->call('cancelForm')
        ->assertSet('showForm', false)
        ->assertSet('label', '');
});

it('prevents managing addresses of other customers', function () {
    $otherCustomer = Customer::factory()->create([
        'store_id' => $this->context['store']->id,
    ]);

    $otherAddress = CustomerAddress::factory()->create([
        'customer_id' => $otherCustomer->id,
    ]);

    expect(fn () => Livewire::actingAs($this->customer, 'customer')
        ->test(AddressesIndex::class)
        ->call('editAddress', $otherAddress->id)
    )->toThrow(ModelNotFoundException::class);
});
