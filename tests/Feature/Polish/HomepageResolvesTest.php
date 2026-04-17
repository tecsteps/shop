<?php

use App\Enums\StoreDomainType;
use App\Models\Store;
use App\Models\StoreDomain;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('resolves http://shop.test/ to 200 for a seeded store domain', function () {
    $store = Store::factory()->create(['name' => 'Shop']);
    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => 'shop.test',
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);

    $response = $this->get('http://shop.test/');

    $response->assertOk();
    $response->assertSee($store->name);
});
