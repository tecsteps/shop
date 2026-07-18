<?php

use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns a successful response', function () {
    $store = Store::factory()->create();
    StoreDomain::factory()->create(['store_id' => $store->id, 'hostname' => parse_url(config('app.url'), PHP_URL_HOST)]);

    $response = $this->get('/');

    $response->assertStatus(200);
});
