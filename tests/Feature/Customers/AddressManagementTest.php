<?php

use App\Livewire\Storefront\Account\Addresses\Index as AddressesIndex;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Livewire\Livewire;

/**
 * A customer of the given store, authenticated via the customer guard.
 */
function actingCustomer(App\Models\Store $store): Customer
{
    $customer = Customer::factory()->create(['store_id' => $store->id]);

    test()->actingAs($customer, 'customer');

    return $customer;
}

test('creates a new address and makes the first one the default', function () {
    $store = $this->createStore();
    $this->bindStore($store);
    $customer = actingCustomer($store);

    Livewire::test(AddressesIndex::class)
        ->call('create')
        ->assertSet('showModal', true)
        ->set('label', 'Home')
        ->set('first_name', 'Jane')
        ->set('last_name', 'Doe')
        ->set('address1', '123 Main St')
        ->set('city', 'Berlin')
        ->set('country', 'Germany')
        ->set('country_code', 'DE')
        ->set('postal_code', '10115')
        ->call('save')
        ->assertSet('showModal', false)
        ->assertHasNoErrors();

    $address = $customer->addresses()->sole();

    expect($address->label)->toBe('Home')
        ->and($address->address_json['address1'])->toBe('123 Main St')
        ->and($address->address_json['city'])->toBe('Berlin')
        ->and($address->is_default)->toBeTrue();
});

test('updates an existing address', function () {
    $store = $this->createStore();
    $this->bindStore($store);
    $customer = actingCustomer($store);

    $address = CustomerAddress::factory()->create(['customer_id' => $customer->id]);

    Livewire::test(AddressesIndex::class)
        ->call('edit', $address->id)
        ->assertSet('editingId', $address->id)
        ->assertSet('showModal', true)
        ->set('city', 'Hamburg')
        ->call('save')
        ->assertSet('showModal', false)
        ->assertHasNoErrors();

    expect($address->refresh()->address_json['city'])->toBe('Hamburg');
});

test('deletes an address', function () {
    $store = $this->createStore();
    $this->bindStore($store);
    $customer = actingCustomer($store);

    $address = CustomerAddress::factory()->create(['customer_id' => $customer->id]);

    Livewire::test(AddressesIndex::class)
        ->call('delete', $address->id);

    $this->assertDatabaseMissing('customer_addresses', ['id' => $address->id]);
});

test('sets a default address and clears the flag on the rest', function () {
    $store = $this->createStore();
    $this->bindStore($store);
    $customer = actingCustomer($store);

    $default = CustomerAddress::factory()->default()->create(['customer_id' => $customer->id]);
    $other = CustomerAddress::factory()->create(['customer_id' => $customer->id]);

    Livewire::test(AddressesIndex::class)
        ->call('setDefault', $other->id);

    expect($other->refresh()->is_default)->toBeTrue()
        ->and($default->refresh()->is_default)->toBeFalse();
});

test('saving with the default checkbox unsets other defaults', function () {
    $store = $this->createStore();
    $this->bindStore($store);
    $customer = actingCustomer($store);

    $default = CustomerAddress::factory()->default()->create(['customer_id' => $customer->id]);

    Livewire::test(AddressesIndex::class)
        ->call('create')
        ->set('first_name', 'Jane')
        ->set('last_name', 'Doe')
        ->set('address1', '456 Oak Ave')
        ->set('city', 'Berlin')
        ->set('country', 'Germany')
        ->set('country_code', 'DE')
        ->set('postal_code', '10115')
        ->set('is_default', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($default->refresh()->is_default)->toBeFalse()
        ->and($customer->addresses()->where('is_default', true)->count())->toBe(1);
});

test('validates required address fields', function () {
    $store = $this->createStore();
    $this->bindStore($store);
    actingCustomer($store);

    Livewire::test(AddressesIndex::class)
        ->call('create')
        ->call('save')
        ->assertHasErrors(['first_name', 'last_name', 'address1', 'city', 'country', 'country_code', 'postal_code'])
        ->assertSet('showModal', true);
});

test('prevents managing another customers addresses', function () {
    $store = $this->createStore();
    $this->bindStore($store);
    actingCustomer($store);

    $otherCustomer = Customer::factory()->create(['store_id' => $store->id]);
    $address = CustomerAddress::factory()->create(['customer_id' => $otherCustomer->id]);

    // Scoped to own addresses, so the lookup fails. Laravel renders this
    // ModelNotFoundException as a 404 at HTTP level.
    expect(fn () => Livewire::test(AddressesIndex::class)->call('edit', $address->id))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(fn () => Livewire::test(AddressesIndex::class)->call('delete', $address->id))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);

    $this->assertDatabaseHas('customer_addresses', ['id' => $address->id]);
});
