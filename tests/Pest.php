<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
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

function createStoreContext(): array
{
    $org = \App\Models\Organization::factory()->create();
    $store = \App\Models\Store::factory()->for($org)->create();
    $domain = \App\Models\StoreDomain::factory()->for($store)->create(['domain' => 'test-store.test', 'is_primary' => true]);
    $user = \App\Models\User::factory()->create();
    $store->users()->attach($user, ['role' => \App\Enums\StoreUserRole::Owner->value]);
    $settings = \App\Models\StoreSettings::factory()->create(['store_id' => $store->id]);
    app()->instance('current_store', $store);

    return compact('org', 'store', 'domain', 'user', 'settings');
}

function actingAsAdmin(\App\Models\User $user)
{
    return test()->actingAs($user);
}

function actingAsCustomer(\App\Models\Customer $customer)
{
    return test()->actingAs($customer, 'customer');
}
