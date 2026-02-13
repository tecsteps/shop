<?php

use App\Enums\StoreUserRole;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/**
 * Create a full store context: Organization, Store, StoreDomain, User with Owner role, and bind current_store.
 *
 * @return array{organization: Organization, store: Store, domain: StoreDomain, user: User}
 */
function createStoreContext(): array
{
    $organization = Organization::factory()->create();
    $store = Store::factory()->for($organization)->create();
    $domain = StoreDomain::factory()->for($store)->create([
        'hostname' => 'test-store.example.com',
        'is_primary' => true,
    ]);

    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => StoreUserRole::Owner->value]);

    app()->singleton('current_store', fn () => $store);

    return compact('organization', 'store', 'domain', 'user');
}

/**
 * Authenticate the given user and set the current store in session.
 */
function actingAsAdmin(User $user, ?Store $store = null): Tests\TestCase
{
    $store ??= app()->bound('current_store') ? app('current_store') : null;

    $testCase = test()->actingAs($user);

    if ($store) {
        session(['current_store_id' => $store->id]);
    }

    return $testCase;
}
