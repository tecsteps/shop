<?php

use App\Livewire\Storefront\Account\Auth\Login;
use App\Models\Customer;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('customers can log in and access the account route', function () {
    $store = Store::factory()->create();
    StoreDomain::factory()->create(['store_id' => $store->getKey(), 'hostname' => 'shop.test']);
    $customer = Customer::factory()->create(['store_id' => $store->getKey(), 'email' => 'customer@example.test']);

    app()->instance('current_store', $store);

    Livewire::test(Login::class)
        ->set('email', $customer->email)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('account.dashboard'));

    expect(auth('customer')->check())->toBeTrue();

    $this->get('http://shop.test/account')->assertOk();
});

test('customer sessions are available to storefront API requests', function () {
    $store = Store::factory()->create();
    StoreDomain::factory()->create(['store_id' => $store->getKey(), 'hostname' => 'shop.test']);
    $customer = Customer::factory()->create(['store_id' => $store->getKey(), 'email' => 'api-customer@example.test']);

    app()->instance('current_store', $store);

    $this->actingAs($customer, 'customer')
        ->postJson('http://shop.test/api/storefront/v1/carts')
        ->assertCreated()
        ->assertJsonPath('customer_id', $customer->getKey());
});
