<?php

use App\Enums\StoreUserRole;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\User;

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * Create a full store context: Organization, Store, StoreDomain, User with Owner role, and bind current_store.
 *
 * @return array{organization: Organization, store: Store, domain: StoreDomain, user: User, settings: StoreSettings}
 */
function createStoreContext(string $hostname = 'test-store.test'): array
{
    $organization = Organization::factory()->create();
    $store = Store::factory()->create(['organization_id' => $organization->id]);
    $domain = StoreDomain::factory()->create([
        'store_id' => $store->id,
        'hostname' => $hostname,
    ]);
    $settings = StoreSettings::factory()->create(['store_id' => $store->id]);
    $user = User::factory()->create();
    $store->users()->attach($user, ['role' => StoreUserRole::Owner]);

    app()->instance('current_store', $store);

    return compact('organization', 'store', 'domain', 'user', 'settings');
}

/**
 * Authenticate as admin and set store in session.
 */
function actingAsAdmin(User $user, ?Store $store = null): mixed
{
    $store = $store ?? app('current_store');

    return test()->actingAs($user, 'web')
        ->withSession(['current_store_id' => $store->id]);
}

/**
 * Authenticate as customer with customer guard.
 */
function actingAsCustomer(Customer $customer): mixed
{
    return test()->actingAs($customer, 'customer');
}
