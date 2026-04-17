<?php

use App\Enums\StoreDomainType;
use App\Models\Customer;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('logs out the customer and redirects home', function () {
    $store = Store::factory()->create(['name' => 'Shop']);
    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => 'shop.test',
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);
    $customer = Customer::factory()->create([
        'store_id' => $store->getKey(),
        'email' => 'me@example.com',
        'password_hash' => Hash::make('secret123'),
    ]);

    $this->get('http://shop.test/');
    Auth::guard('customer')->login($customer);

    $response = $this->post('http://shop.test/account/logout');

    $response->assertRedirect('/');
    expect(Auth::guard('customer')->check())->toBeFalse();
});
