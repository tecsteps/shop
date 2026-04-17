<?php

use App\Enums\StoreDomainType;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Support\Facades\Hash;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('only lists the authenticated customers orders', function () {
    $store = Store::factory()->create(['name' => 'Shop']);
    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => 'shop.test',
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);

    $me = Customer::factory()->create([
        'store_id' => $store->getKey(),
        'email' => 'me@example.com',
        'password_hash' => Hash::make('secret123'),
    ]);
    $other = Customer::factory()->create([
        'store_id' => $store->getKey(),
        'email' => 'other@example.com',
        'password_hash' => Hash::make('secret123'),
    ]);

    $mine = Order::factory()->create([
        'store_id' => $store->getKey(),
        'customer_id' => $me->getKey(),
        'order_number' => '#1001',
    ]);
    Order::factory()->create([
        'store_id' => $store->getKey(),
        'customer_id' => $other->getKey(),
        'order_number' => '#1002',
    ]);

    $response = $this->actingAs($me, 'customer')->get('http://shop.test/account/orders');

    $response->assertOk();
    $response->assertSee('#1001');
    $response->assertDontSee('#1002');
});

it('404s when viewing another customers order', function () {
    $store = Store::factory()->create(['name' => 'Shop']);
    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => 'shop.test',
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);

    $me = Customer::factory()->create(['store_id' => $store->getKey()]);
    $other = Customer::factory()->create(['store_id' => $store->getKey()]);

    Order::factory()->create([
        'store_id' => $store->getKey(),
        'customer_id' => $other->getKey(),
        'order_number' => '#2002',
    ]);

    $response = $this->actingAs($me, 'customer')->get('http://shop.test/account/orders/%232002');

    $response->assertNotFound();
});
