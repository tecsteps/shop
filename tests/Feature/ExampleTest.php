<?php

use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\StoreDomain;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns a successful response', function () {
    $store = Store::factory()->create(['status' => StoreStatus::Active]);
    StoreDomain::factory()->create(['store_id' => $store->id, 'hostname' => 'acme-fashion.test']);

    $response = $this->get('http://acme-fashion.test/');

    $response->assertOk();
});
