<?php

use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

function makeStore(array $attributes = []): Store
{
    $org = Organization::create([
        'name' => $attributes['org_name'] ?? 'Test Org',
        'billing_email' => 'billing-'.uniqid().'@test.com',
    ]);

    $store = Store::create(array_merge([
        'organization_id' => $org->id,
        'name' => 'Test Store',
        'handle' => 'store-'.uniqid(),
        'status' => 'active',
        'default_currency' => 'EUR',
        'default_locale' => 'en',
        'timezone' => 'Europe/Berlin',
    ], array_diff_key($attributes, ['org_name' => null])));

    StoreDomain::create([
        'store_id' => $store->id,
        'hostname' => 'test-'.uniqid().'.test',
        'type' => 'storefront',
        'is_primary' => true,
        'tls_mode' => 'managed',
        'created_at' => now(),
    ]);

    return $store;
}

function bindCurrentStore(Store $store): Store
{
    app()->instance('current_store', $store);

    return $store;
}

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
