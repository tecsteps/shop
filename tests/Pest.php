<?php

use App\Enums\StoreUserRole;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

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
 * Create a full store context: an organization, a store, a storefront domain,
 * and an owner user, then bind the store as the container's `current_store`.
 *
 * @param  array{
 *     hostname?: string,
 *     handle?: string,
 *     status?: \App\Enums\StoreStatus|string,
 *     ownerRole?: \App\Enums\StoreUserRole,
 *     bind?: bool,
 * }  $attributes
 * @return array{
 *     organization: \App\Models\Organization,
 *     store: \App\Models\Store,
 *     domain: \App\Models\StoreDomain,
 *     owner: \App\Models\User,
 * }
 */
function createStoreContext(array $attributes = []): array
{
    $hostname = $attributes['hostname'] ?? 'acme-fashion.test';

    $organization = Organization::factory()->create();

    $storeState = ['organization_id' => $organization->id];

    if (isset($attributes['handle'])) {
        $storeState['handle'] = $attributes['handle'];
    }

    if (isset($attributes['status'])) {
        $storeState['status'] = $attributes['status'] instanceof BackedEnum
            ? $attributes['status']->value
            : $attributes['status'];
    }

    $store = Store::factory()->create($storeState);

    $domain = StoreDomain::factory()->create([
        'store_id' => $store->id,
        'hostname' => $hostname,
    ]);

    $owner = User::factory()->create();

    $store->users()->attach($owner->id, [
        'role' => ($attributes['ownerRole'] ?? StoreUserRole::Owner)->value,
    ]);

    if ($attributes['bind'] ?? true) {
        bindCurrentStore($store);
    }

    return [
        'organization' => $organization,
        'store' => $store,
        'domain' => $domain,
        'owner' => $owner,
    ];
}

/**
 * Bind a store as the container's `current_store` (mimics ResolveStore).
 */
function bindCurrentStore(Store $store): Store
{
    app()->instance('current_store', $store);

    return $store;
}

/**
 * Build an absolute storefront URL for a hostname.
 *
 * The test client derives the request host from the URL (not from a Host
 * header), so storefront requests must target the full hostname for
 * ResolveStore to match a store_domains row.
 */
function storefrontUrl(string $hostname, string $path = '/'): string
{
    return 'http://'.$hostname.'/'.ltrim($path, '/');
}

/**
 * Authenticate as an admin via the web guard and set the session's current
 * store so admin (session-based) store resolution succeeds.
 */
function actingAsAdmin(User $user, ?Store $store = null): User
{
    test()->actingAs($user, 'web');

    $store ??= app()->bound('current_store') ? app('current_store') : null;

    if ($store !== null) {
        session()->put('current_store_id', $store->id);
    }

    return $user;
}

/**
 * Authenticate as a customer via the customer guard.
 */
function actingAsCustomer(Customer $customer): Customer
{
    test()->actingAs($customer, 'customer');

    return $customer;
}
