<?php

use App\Enums\StoreUserRole;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
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
 * Creates a full store context for testing: Organization, Store, StoreDomain, User with Owner role.
 * Binds the store as 'current_store' in the container.
 *
 * @return array{organization: Organization, store: Store, domain: StoreDomain, user: User}
 */
function createStoreContext(string $hostname = 'acme-fashion.test'): array
{
    $organization = Organization::factory()->create();
    $store = Store::factory()->create(['organization_id' => $organization->id]);
    $domain = StoreDomain::factory()->create([
        'store_id' => $store->id,
        'hostname' => $hostname,
    ]);
    $user = User::factory()->create();
    $store->users()->attach($user->id, ['role' => StoreUserRole::Owner]);

    // Clear cached hostname lookup to prevent stale entries from prior tests
    Illuminate\Support\Facades\Cache::forget("store_domain:{$hostname}");

    app()->instance('current_store', $store);

    return compact('organization', 'store', 'domain', 'user');
}

/**
 * Authenticates as an admin user and sets the store in session.
 */
function actingAsAdmin(User $user, ?Store $store = null): \Illuminate\Testing\TestResponse
{
    $store = $store ?? app('current_store');
    test()->actingAs($user);
    session(['current_store_id' => $store->id]);

    return test();
}

/**
 * Authenticates as a customer using the customer guard.
 */
function actingAsCustomer(Customer $customer): \Illuminate\Testing\TestResponse
{
    test()->actingAs($customer, 'customer');

    return test();
}
