<?php

use App\Livewire\Storefront\Account\Addresses\Index as AddressBook;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Store;
use App\Models\StoreDomain;
use Livewire\Livewire;

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->domain = StoreDomain::factory()->for($this->store)->create();
    $this->baseUrl = 'http://'.$this->domain->hostname;
    $this->customer = Customer::factory()->for($this->store)->create();

    app()->instance('current_store', $this->store);
});

it('lists saved addresses', function () {
    CustomerAddress::factory()->for($this->customer)->create([
        'label' => 'Home',
        'address_json' => addressJsonFixture(['address1' => 'Musterstrasse 1']),
        'is_default' => true,
    ]);

    CustomerAddress::factory()->for($this->customer)->create([
        'label' => 'Work',
        'address_json' => addressJsonFixture(['address1' => 'Friedrichstrasse 100']),
        'is_default' => false,
    ]);

    actingAsCustomer($this->customer)
        ->get($this->baseUrl.'/account/addresses')
        ->assertOk()
        ->assertSee('Musterstrasse 1')
        ->assertSee('Friedrichstrasse 100');
});

it('creates a new address', function () {
    actingAsCustomer($this->customer);

    Livewire::test(AddressBook::class)
        ->call('create')
        ->set('label', 'Home')
        ->set('form.first_name', 'Jane')
        ->set('form.last_name', 'Shopper')
        ->set('form.address1', 'Musterstrasse 1')
        ->set('form.city', 'Berlin')
        ->set('form.postal_code', '10115')
        ->set('form.country_code', 'DE')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false);

    $address = $this->customer->addresses()->sole();

    expect($address->label)->toBe('Home');
    expect($address->address_json['address1'])->toBe('Musterstrasse 1');
    expect($address->address_json['zip'])->toBe('10115');
    expect($address->address_json['country'])->toBe('Germany');
    expect($address->is_default)->toBeTrue();
});

it('updates an existing address', function () {
    $address = CustomerAddress::factory()->for($this->customer)->create([
        'address_json' => addressJsonFixture(['city' => 'Berlin']),
    ]);

    actingAsCustomer($this->customer);

    Livewire::test(AddressBook::class)
        ->call('edit', $address->getKey())
        ->assertSet('form.city', 'Berlin')
        ->set('form.city', 'Hamburg')
        ->call('save')
        ->assertHasNoErrors();

    expect($address->refresh()->address_json['city'])->toBe('Hamburg');
});

it('deletes an address', function () {
    $address = CustomerAddress::factory()->for($this->customer)->create();

    actingAsCustomer($this->customer);

    Livewire::test(AddressBook::class)
        ->call('delete', $address->getKey());

    $this->assertDatabaseMissing('customer_addresses', ['id' => $address->getKey()]);
});

it('sets a default address', function () {
    $defaultAddress = CustomerAddress::factory()->for($this->customer)->create(['is_default' => true]);
    $otherAddress = CustomerAddress::factory()->for($this->customer)->create(['is_default' => false]);

    actingAsCustomer($this->customer);

    Livewire::test(AddressBook::class)
        ->call('setDefault', $otherAddress->getKey());

    expect($otherAddress->refresh()->is_default)->toBeTrue();
    expect($defaultAddress->refresh()->is_default)->toBeFalse();
});

it('validates required address fields', function () {
    actingAsCustomer($this->customer);

    Livewire::test(AddressBook::class)
        ->call('create')
        ->set('form.first_name', 'Jane')
        ->set('form.last_name', 'Shopper')
        ->set('form.city', 'Berlin')
        ->set('form.postal_code', '10115')
        ->set('form.country_code', 'DE')
        ->call('save')
        ->assertHasErrors(['form.address1' => 'required']);

    expect($this->customer->addresses()->count())->toBe(0);
});

it('prevents managing another customers addresses', function () {
    $otherCustomer = Customer::factory()->for($this->store)->create();
    $otherAddress = CustomerAddress::factory()->for($otherCustomer)->create();

    actingAsCustomer($this->customer);

    Livewire::test(AddressBook::class)
        ->call('edit', $otherAddress->getKey())
        ->assertNotFound();

    Livewire::test(AddressBook::class)
        ->call('delete', $otherAddress->getKey())
        ->assertNotFound();

    expect(CustomerAddress::query()->whereKey($otherAddress->getKey())->exists())->toBeTrue();
});

/**
 * A complete spec 01 address JSON object with optional overrides.
 *
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function addressJsonFixture(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Jane',
        'last_name' => 'Shopper',
        'company' => '',
        'address1' => 'Musterstrasse 1',
        'address2' => '',
        'city' => 'Berlin',
        'province' => '',
        'province_code' => '',
        'country' => 'Germany',
        'country_code' => 'DE',
        'zip' => '10115',
        'phone' => '',
    ], $overrides);
}
