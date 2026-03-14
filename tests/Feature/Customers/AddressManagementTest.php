<?php

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->customer = Customer::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);
});

it('lists saved addresses', function () {
    CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'city' => 'Berlin',
    ]);

    Livewire::actingAs($this->customer, 'customer')
        ->test(\App\Livewire\Storefront\Account\Addresses\Index::class)
        ->assertSee('Jane')
        ->assertSee('Doe')
        ->assertSee('Berlin')
        ->assertStatus(200);
});

it('creates a new address', function () {
    Livewire::actingAs($this->customer, 'customer')
        ->test(\App\Livewire\Storefront\Account\Addresses\Index::class)
        ->call('showAddForm')
        ->assertSet('showForm', true)
        ->set('first_name', 'Max')
        ->set('last_name', 'Mustermann')
        ->set('address1', 'Hauptstr. 1')
        ->set('city', 'Munich')
        ->set('postal_code', '80331')
        ->set('country_code', 'DE')
        ->call('saveAddress')
        ->assertSet('showForm', false);

    expect($this->customer->addresses()->count())->toBe(1);
    expect($this->customer->addresses()->first()->city)->toBe('Munich');
});

it('updates an existing address', function () {
    $address = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
        'city' => 'Berlin',
    ]);

    Livewire::actingAs($this->customer, 'customer')
        ->test(\App\Livewire\Storefront\Account\Addresses\Index::class)
        ->call('editAddress', $address->id)
        ->assertSet('editingAddressId', $address->id)
        ->set('city', 'Hamburg')
        ->call('saveAddress')
        ->assertSet('showForm', false);

    expect($address->fresh()->city)->toBe('Hamburg');
});

it('deletes an address', function () {
    $address = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
    ]);

    Livewire::actingAs($this->customer, 'customer')
        ->test(\App\Livewire\Storefront\Account\Addresses\Index::class)
        ->call('deleteAddress', $address->id);

    expect($this->customer->addresses()->count())->toBe(0);
});

it('sets default address', function () {
    $addr1 = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
        'is_default' => false,
    ]);
    $addr2 = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
        'is_default' => false,
    ]);

    Livewire::actingAs($this->customer, 'customer')
        ->test(\App\Livewire\Storefront\Account\Addresses\Index::class)
        ->call('setDefault', $addr1->id);

    expect($addr1->fresh()->is_default)->toBeTrue();
    expect($addr2->fresh()->is_default)->toBeFalse();
});

it('validates required fields', function () {
    Livewire::actingAs($this->customer, 'customer')
        ->test(\App\Livewire\Storefront\Account\Addresses\Index::class)
        ->call('showAddForm')
        ->call('saveAddress')
        ->assertHasErrors(['first_name', 'last_name', 'address1', 'city', 'postal_code']);
});

it('prevents managing another customer addresses', function () {
    $otherCustomer = Customer::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);
    $otherAddress = CustomerAddress::factory()->create([
        'customer_id' => $otherCustomer->id,
    ]);

    expect(fn () => Livewire::actingAs($this->customer, 'customer')
        ->test(\App\Livewire\Storefront\Account\Addresses\Index::class)
        ->call('editAddress', $otherAddress->id)
    )->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});
