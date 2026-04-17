<?php

use App\Enums\StoreDomainType;
use App\Models\Customer;
use App\Models\Store;
use App\Models\StoreDomain;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('verifies the customer email from a signed link', function () {
    $store = Store::factory()->create(['name' => 'Shop']);
    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => 'shop.test',
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);
    $customer = Customer::factory()->create([
        'store_id' => $store->getKey(),
        'email' => 'verify@example.com',
        'email_verified_at' => null,
    ]);

    $hash = sha1('verify@example.com');
    $response = $this->actingAs($customer, 'customer')
        ->get('http://shop.test/account/email/verify/'.$customer->getKey().'/'.$hash);

    $response->assertRedirect('/account');
    $customer->refresh();
    expect($customer->email_verified_at)->not->toBeNull();
});

it('rejects a bad hash', function () {
    $store = Store::factory()->create(['name' => 'Shop']);
    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => 'shop.test',
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);
    $customer = Customer::factory()->create(['store_id' => $store->getKey(), 'email_verified_at' => null]);

    $response = $this->get('http://shop.test/account/email/verify/'.$customer->getKey().'/bad-hash');

    $response->assertForbidden();
});
