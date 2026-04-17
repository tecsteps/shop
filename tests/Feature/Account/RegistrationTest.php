<?php

use App\Enums\StoreDomainType;
use App\Livewire\Storefront\Account\Auth\Register;
use App\Models\Customer;
use App\Models\Store;
use App\Models\StoreDomain;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function seedAccountStore(string $hostname = 'shop.test'): Store
{
    $store = Store::factory()->create(['name' => 'Shop']);

    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => $hostname,
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);

    return $store;
}

it('creates a customer scoped to the current store on registration', function () {
    $store = seedAccountStore();

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])->get('http://shop.test/account/register');

    Livewire::withQueryParams([])
        ->test(Register::class)
        ->set('name', 'Jane Shopper')
        ->set('email', 'jane@example.com')
        ->set('password', 'secret123')
        ->set('password_confirmation', 'secret123')
        ->call('register');

    $customer = Customer::query()->withoutGlobalScopes()->where('email', 'jane@example.com')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->store_id)->toBe($store->getKey())
        ->and($customer->password_hash)->not->toBeNull();
});

it('allows the same email to register on two different stores', function () {
    $storeA = seedAccountStore('shop-a.test');
    $storeB = Store::factory()->create(['name' => 'Shop B']);
    StoreDomain::factory()->create([
        'store_id' => $storeB->getKey(),
        'hostname' => 'shop-b.test',
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);

    $this->get('http://shop-a.test/account/register');
    Livewire::test(Register::class)
        ->set('name', 'Dual User')
        ->set('email', 'dual@example.com')
        ->set('password', 'secret123')
        ->set('password_confirmation', 'secret123')
        ->call('register');

    $this->get('http://shop-b.test/account/register');
    Livewire::test(Register::class)
        ->set('name', 'Dual User B')
        ->set('email', 'dual@example.com')
        ->set('password', 'secret456')
        ->set('password_confirmation', 'secret456')
        ->call('register');

    $aCount = Customer::query()->withoutGlobalScopes()->where('store_id', $storeA->getKey())->where('email', 'dual@example.com')->count();
    $bCount = Customer::query()->withoutGlobalScopes()->where('store_id', $storeB->getKey())->where('email', 'dual@example.com')->count();

    expect($aCount)->toBe(1)
        ->and($bCount)->toBe(1);
});
