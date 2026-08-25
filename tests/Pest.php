<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithStore;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class, InteractsWithStore::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * @return array{store: \App\Models\Store, user: \App\Models\User, organization: \App\Models\Organization, domain: \App\Models\StoreDomain}
 */
function createStoreContext(): array
{
    $organization = \App\Models\Organization::factory()->create();
    $store = \App\Models\Store::factory()->create(['organization_id' => $organization->id]);
    $domain = \App\Models\StoreDomain::factory()->create(['store_id' => $store->id]);
    $user = \App\Models\User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    app()->instance('current_store', $store);

    return ['store' => $store, 'user' => $user, 'organization' => $organization, 'domain' => $domain];
}

function bindCurrentStore(\App\Models\Store $store): \App\Models\Store
{
    app()->instance('current_store', $store);

    return $store;
}
