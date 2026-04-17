<?php

use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns a successful response', function () {
    $store = Store::factory()->create();

    StoreDomain::factory()->create([
        'store_id' => $store->id,
        'hostname' => 'shop.test',
    ]);

    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/');

    $response->assertStatus(200);
});
