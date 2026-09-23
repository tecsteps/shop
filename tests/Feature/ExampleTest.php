<?php

use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Support\Facades\Cache;

it('returns a successful response', function () {
    $organization = Organization::query()->create([
        'name' => 'Example organization',
        'billing_email' => 'billing@example.test',
    ]);
    $store = Store::query()->create([
        'organization_id' => $organization->id,
        'name' => 'Example store',
        'handle' => 'example-store',
        'status' => 'active',
        'default_currency' => 'EUR',
        'default_locale' => 'en',
        'timezone' => 'Europe/Berlin',
    ]);
    StoreDomain::query()->create([
        'store_id' => $store->id,
        'hostname' => 'shop.test',
        'type' => 'storefront',
        'is_primary' => true,
    ]);
    Cache::flush();

    $response = $this->get('http://shop.test/');

    $response->assertStatus(200);
});
