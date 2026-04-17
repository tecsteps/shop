<?php

use App\Enums\StoreDomainType;
use App\Models\Store;
use App\Models\StoreDomain;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function seedStorefrontStore(string $hostname = 'shop.test', string $name = 'Shop'): Store
{
    $store = Store::factory()->create(['name' => $name]);

    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => $hostname,
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);

    return $store;
}

it('renders the storefront home page', function () {
    $store = seedStorefrontStore('shop.test', 'Shop');

    $response = $this->get('http://shop.test/');

    $response->assertOk();
    $response->assertSee($store->name);
});
