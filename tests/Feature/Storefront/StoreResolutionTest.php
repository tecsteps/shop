<?php

use App\Enums\StoreDomainType;
use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\StoreDomain;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns 404 for an unknown hostname', function () {
    $response = $this->get('http://unknown.test/');

    $response->assertNotFound();
});

it('returns 503 when the store is suspended', function () {
    $store = Store::factory()->create([
        'name' => 'Paused Shop',
        'status' => StoreStatus::Suspended->value,
    ]);

    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => 'paused.test',
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);

    $response = $this->get('http://paused.test/');

    $response->assertStatus(503);
});
