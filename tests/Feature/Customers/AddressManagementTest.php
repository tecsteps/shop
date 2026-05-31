<?php

use App\Livewire\Storefront\Account\Addresses\Index as AddressBook;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext(['hostname' => 'acme-fashion.test']);
    $this->store = $this->context['store'];
    $this->customer = Customer::factory()->create(['store_id' => $this->store->id]);
    actingAsCustomer($this->customer);
});

it('lists saved addresses', function () {
    CustomerAddress::factory()->for($this->customer)->create(['address_json' => ['first_name' => 'Anna', 'last_name' => 'Berg', 'city' => 'Berlin']]);
    CustomerAddress::factory()->for($this->customer)->create(['address_json' => ['first_name' => 'Carl', 'last_name' => 'Stein', 'city' => 'Munich'], 'is_default' => false]);

    Livewire::test(AddressBook::class)
        ->assertSee('Anna')
        ->assertSee('Carl');
});

it('creates a new address', function () {
    Livewire::test(AddressBook::class)
        ->call('addAddress')
        ->set('form.first_name', 'Maria')
        ->set('form.last_name', 'Lopez')
        ->set('form.address1', 'Calle Mayor 1')
        ->set('form.city', 'Madrid')
        ->set('form.postal_code', '28013')
        ->set('form.country', 'ES')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->customer->addresses()->where('address_json->first_name', 'Maria')->exists())->toBeTrue();
});

it('updates an existing address', function () {
    $address = CustomerAddress::factory()->for($this->customer)->create([
        'address_json' => ['first_name' => 'Tom', 'last_name' => 'Hart', 'address1' => 'Old St 1', 'city' => 'Leeds', 'postal_code' => 'LS1', 'country' => 'GB'],
    ]);

    Livewire::test(AddressBook::class)
        ->call('edit', $address->id)
        ->set('form.city', 'York')
        ->call('save')
        ->assertHasNoErrors();

    expect($address->fresh()->address_json['city'])->toBe('York');
});

it('deletes an address', function () {
    $address = CustomerAddress::factory()->for($this->customer)->create();

    Livewire::test(AddressBook::class)->call('delete', $address->id);

    expect(CustomerAddress::query()->whereKey($address->id)->exists())->toBeFalse();
});

it('sets a default address', function () {
    $first = CustomerAddress::factory()->for($this->customer)->create(['is_default' => true]);
    $second = CustomerAddress::factory()->for($this->customer)->create(['is_default' => false]);

    Livewire::test(AddressBook::class)->call('setDefault', $second->id);

    expect($second->fresh()->is_default)->toBeTrue();
    expect($first->fresh()->is_default)->toBeFalse();
});

it('validates required address fields', function () {
    Livewire::test(AddressBook::class)
        ->call('addAddress')
        ->set('form.first_name', 'NoAddress')
        ->set('form.last_name', 'Person')
        ->set('form.city', 'Nowhere')
        ->set('form.postal_code', '00000')
        ->set('form.country', 'US')
        // address1 intentionally missing
        ->call('save')
        ->assertHasErrors('form.address1');
});

it('prevents managing another customers addresses', function () {
    $other = Customer::factory()->create(['store_id' => $this->store->id]);
    $otherAddress = CustomerAddress::factory()->for($other)->create();

    // The component scopes every lookup to the authenticated customer, so
    // editing another customer's address resolves nothing (404 in HTTP context).
    expect(fn () => Livewire::test(AddressBook::class)->call('edit', $otherAddress->id))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);

    // And it is never mutated by a delete attempt either.
    Livewire::test(AddressBook::class)->call('delete', $otherAddress->id);
    expect(CustomerAddress::query()->whereKey($otherAddress->id)->exists())->toBeTrue();
});
