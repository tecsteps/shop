<?php

use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns a successful response', function () {
    $store = Store::factory()->create();
    StoreDomain::factory()->for($store)->create(['hostname' => 'acme-fashion.test']);
    $response = $this->withServerVariables(['HTTP_HOST' => 'acme-fashion.test'])->get('/');

    $response->assertSuccessful();
});
