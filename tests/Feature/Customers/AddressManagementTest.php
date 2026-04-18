<?php

use App\Livewire\Storefront\Account\Addresses\Index as AddressesIndex;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'addr-'.uniqid().'.test']);

    $this->customer = Customer::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'addr-'.uniqid().'@example.com',
        'password' => Hash::make('password'),
        'state' => 'active',
    ]);

    $this->actingAsCustomer($this->customer);
});

it('creates the first address and marks it default', function (): void {
    Livewire::test(AddressesIndex::class)
        ->call('openCreate')
        ->set('first_name', 'Billy')
        ->set('last_name', 'Buyer')
        ->set('address1', '1 Main St')
        ->set('city', 'Berlin')
        ->set('postal_code', '10115')
        ->set('country_code', 'DE')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    $address = CustomerAddress::query()->where('customer_id', $this->customer->id)->first();
    expect($address)->not->toBeNull();
    expect($address->is_default)->toBeTrue();
});

it('enforces only one default address per customer', function (): void {
    $first = CustomerAddress::query()->create([
        'customer_id' => $this->customer->id,
        'label' => 'First',
        'address_json' => ['first_name' => 'A', 'last_name' => 'B', 'address1' => 'x', 'city' => 'c', 'postal_code' => 'p', 'country_code' => 'DE'],
        'is_default' => true,
    ]);

    Livewire::test(AddressesIndex::class)
        ->call('openCreate')
        ->set('first_name', 'New')
        ->set('last_name', 'One')
        ->set('address1', '2 Elm St')
        ->set('city', 'Hamburg')
        ->set('postal_code', '20095')
        ->set('country_code', 'DE')
        ->set('is_default', true)
        ->call('save')
        ->assertHasNoErrors();

    $addresses = CustomerAddress::query()->where('customer_id', $this->customer->id)->get();
    expect($addresses)->toHaveCount(2);
    expect($addresses->where('is_default', true))->toHaveCount(1);
    expect($first->fresh()->is_default)->toBeFalse();
});

it('validates required fields', function (): void {
    Livewire::test(AddressesIndex::class)
        ->call('openCreate')
        ->call('save')
        ->assertHasErrors(['first_name', 'last_name', 'address1', 'city', 'postal_code', 'country_code']);
});

it('promotes an existing address to default', function (): void {
    $a = CustomerAddress::query()->create([
        'customer_id' => $this->customer->id,
        'address_json' => ['first_name' => 'A', 'last_name' => 'A', 'address1' => '1', 'city' => 'c', 'postal_code' => 'p', 'country_code' => 'DE'],
        'is_default' => true,
    ]);

    $b = CustomerAddress::query()->create([
        'customer_id' => $this->customer->id,
        'address_json' => ['first_name' => 'B', 'last_name' => 'B', 'address1' => '2', 'city' => 'c', 'postal_code' => 'p', 'country_code' => 'DE'],
        'is_default' => false,
    ]);

    Livewire::test(AddressesIndex::class)
        ->call('setDefault', $b->id);

    expect($a->fresh()->is_default)->toBeFalse();
    expect($b->fresh()->is_default)->toBeTrue();
});

it('deletes an address and promotes another to default if needed', function (): void {
    $default = CustomerAddress::query()->create([
        'customer_id' => $this->customer->id,
        'address_json' => ['first_name' => 'D', 'last_name' => 'D', 'address1' => '1', 'city' => 'c', 'postal_code' => 'p', 'country_code' => 'DE'],
        'is_default' => true,
    ]);

    $other = CustomerAddress::query()->create([
        'customer_id' => $this->customer->id,
        'address_json' => ['first_name' => 'O', 'last_name' => 'O', 'address1' => '2', 'city' => 'c', 'postal_code' => 'p', 'country_code' => 'DE'],
        'is_default' => false,
    ]);

    Livewire::test(AddressesIndex::class)
        ->call('delete', $default->id);

    expect(CustomerAddress::query()->find($default->id))->toBeNull();
    expect($other->fresh()->is_default)->toBeTrue();
});

it('prevents customers from touching addresses they do not own', function (): void {
    $other = Customer::withoutGlobalScopes()->create([
        'store_id' => $this->customer->store_id,
        'email' => 'other-'.uniqid().'@example.com',
        'password' => Hash::make('password'),
        'state' => 'active',
    ]);

    $otherAddress = CustomerAddress::query()->create([
        'customer_id' => $other->id,
        'address_json' => ['first_name' => 'X'],
        'is_default' => true,
    ]);

    Livewire::test(AddressesIndex::class)
        ->call('delete', $otherAddress->id);

    expect(CustomerAddress::query()->find($otherAddress->id))->not->toBeNull();
});
