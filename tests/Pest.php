<?php

use App\Enums\StoreUserRole;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/**
 * @return array{organization: Organization, store: Store, domain: StoreDomain, user: User}
 */
function createStoreContext(string $hostname = 'test-store.test', StoreUserRole $role = StoreUserRole::Owner): array
{
    $organization = Organization::factory()->create();
    $store = Store::factory()->create(['organization_id' => $organization->id]);
    $domain = StoreDomain::factory()->create([
        'store_id' => $store->id,
        'hostname' => $hostname,
    ]);
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => $role->value]);

    app()->instance('current_store', $store);

    return compact('organization', 'store', 'domain', 'user');
}

function actingAsAdmin(User $user, Store $store): \Illuminate\Testing\TestResponse
{
    test()->actingAs($user);
    session(['current_store_id' => $store->id]);

    return test();
}

function actingAsCustomer(Customer $customer): void
{
    test()->actingAs($customer, 'customer');
}
