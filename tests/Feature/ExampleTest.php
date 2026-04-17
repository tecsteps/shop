<?php

use App\Enums\StoreDomainType;
use App\Models\Store;
use App\Models\StoreDomain;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns a successful response', function () {
    $store = Store::factory()->create(['name' => 'Example']);

    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => 'example.test',
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);

    $response = $this->get('http://example.test/');

    $response->assertStatus(200);
});
