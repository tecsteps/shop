<?php

use App\Enums\StoreUserRole;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * Create a full store context: Organization, Store, StoreDomain, and a User
 * with the Owner role. Binds the store in the container as "current_store".
 *
 * @param  array<string, mixed>  $storeAttributes
 * @return array{organization: Organization, store: Store, domain: StoreDomain, user: User}
 */
function createStoreContext(array $storeAttributes = []): array
{
    $organization = Organization::factory()->create();
    $store = Store::factory()->for($organization)->create($storeAttributes);
    $domain = StoreDomain::factory()->for($store)->create();

    $user = User::factory()->create();

    StoreUser::query()->create([
        'store_id' => $store->getKey(),
        'user_id' => $user->getKey(),
        'role' => StoreUserRole::Owner,
    ]);

    app()->instance('current_store', $store);

    return [
        'organization' => $organization,
        'store' => $store,
        'domain' => $domain,
        'user' => $user,
    ];
}

/**
 * Authenticate as an admin user and put their store in the session.
 */
function actingAsAdmin(User $user, ?Store $store = null): TestCase
{
    $store ??= $user->stores()->first();

    return test()
        ->actingAs($user)
        ->withSession(['current_store_id' => $store?->getKey()]);
}

/**
 * Authenticate as a storefront customer via the customer guard.
 */
function actingAsCustomer(Customer $customer): TestCase
{
    return test()->actingAs($customer, 'customer');
}
