<?php

use App\Enums\StoreDomainType;
use App\Enums\StoreStatus;
use App\Enums\StoreUserRole;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create a full store context with Organization, Store, StoreDomain, and User with Owner role.
 *
 * @return array{organization: Organization, store: Store, domain: StoreDomain, user: User}
 */
function createStoreContext(string $hostname = 'acme-fashion.test'): array
{
    $organization = Organization::factory()->create();

    $store = Store::factory()->create([
        'organization_id' => $organization->id,
        'status' => StoreStatus::Active,
    ]);

    $domain = StoreDomain::factory()->create([
        'store_id' => $store->id,
        'hostname' => $hostname,
        'type' => StoreDomainType::Storefront,
        'is_primary' => true,
    ]);

    $user = User::factory()->create();

    $store->users()->attach($user->id, ['role' => StoreUserRole::Owner]);

    app()->instance('current_store', $store);

    return [
        'organization' => $organization,
        'store' => $store,
        'domain' => $domain,
        'user' => $user,
    ];
}

/**
 * Authenticate as an admin user and set the current_store_id in session.
 */
function actingAsAdmin(User $user, Store $store): void
{
    test()->actingAs($user);
    session(['current_store_id' => $store->id]);
}
