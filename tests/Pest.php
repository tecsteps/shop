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
 * Create a full store context with Organization, Store, StoreDomain, and an Owner user.
 * Binds 'current_store' in the container.
 *
 * @return array{organization: Organization, store: Store, domain: StoreDomain, user: User}
 */
function createStoreContext(): array
{
    $organization = Organization::factory()->create();

    $store = Store::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $domain = StoreDomain::factory()->create([
        'store_id' => $store->id,
        'hostname' => 'test-store.example.com',
        'is_primary' => true,
    ]);

    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => StoreUserRole::Owner->value]);

    app()->instance('current_store', $store);

    return compact('organization', 'store', 'domain', 'user');
}

/**
 * Authenticate as an admin user and set the store in session.
 */
function actingAsAdmin(User $user, ?Store $store = null): \Illuminate\Testing\TestCase
{
    $store = $store ?? app('current_store');

    return test()->actingAs($user, 'web')
        ->withSession(['current_store_id' => $store->id]);
}

/**
 * Authenticate as a customer user using the customer guard.
 */
function actingAsCustomer(Customer $customer): \Illuminate\Testing\TestCase
{
    return test()->actingAs($customer, 'customer');
}
