<?php

use App\Enums\StoreDomainType;
use App\Livewire\Storefront\Account\Addresses;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Store;
use App\Models\StoreDomain;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('creates, edits, and deletes customer addresses', function () {
    $store = Store::factory()->create(['name' => 'Shop']);
    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => 'shop.test',
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);
    $customer = Customer::factory()->create(['store_id' => $store->getKey()]);
    $this->actingAs($customer, 'customer');

    $this->get('http://shop.test/account/addresses');

    Livewire::test(Addresses::class)
        ->set('label', 'Home')
        ->set('first_name', 'Jane')
        ->set('last_name', 'Shopper')
        ->set('address1', '12 Main St')
        ->set('city', 'Townville')
        ->set('country_code', 'US')
        ->set('postal_code', '12345')
        ->set('is_default', true)
        ->call('save');

    $address = CustomerAddress::query()->where('customer_id', $customer->getKey())->firstOrFail();
    expect($address->is_default)->toBeTrue()
        ->and(($address->address_json ?? [])['address1'])->toBe('12 Main St');

    Livewire::test(Addresses::class)
        ->call('edit', $address->getKey())
        ->set('city', 'Newer Town')
        ->call('save');

    $address->refresh();
    expect(($address->address_json ?? [])['city'])->toBe('Newer Town');

    Livewire::test(Addresses::class)->call('delete', $address->getKey());

    expect(CustomerAddress::query()->where('customer_id', $customer->getKey())->count())->toBe(0);
});
