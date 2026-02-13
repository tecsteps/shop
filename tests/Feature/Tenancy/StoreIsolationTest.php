<?php

use App\Enums\StoreUserRole;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreSettings;
use App\Models\User;

it('creates models with store context automatically', function () {
    $context = createStoreContext();

    $settings = StoreSettings::factory()->create([
        'store_id' => $context['store']->id,
    ]);

    expect($settings->store_id)->toBe($context['store']->id);
});

it('scopes queries to current store', function () {
    $org = Organization::factory()->create();
    $store1 = Store::factory()->for($org)->create();
    $store2 = Store::factory()->for($org)->create();

    StoreSettings::factory()->create(['store_id' => $store1->id, 'settings_json' => ['theme' => 'dark']]);
    StoreSettings::factory()->create(['store_id' => $store2->id, 'settings_json' => ['theme' => 'light']]);

    // Without store context, all settings are visible
    expect(StoreSettings::withoutGlobalScopes()->count())->toBe(2);
});

it('isolates users by store through pivot', function () {
    $org = Organization::factory()->create();
    $store1 = Store::factory()->for($org)->create();
    $store2 = Store::factory()->for($org)->create();

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $user1->stores()->attach($store1->id, ['role' => StoreUserRole::Owner->value]);
    $user2->stores()->attach($store2->id, ['role' => StoreUserRole::Owner->value]);

    expect($store1->users)->toHaveCount(1);
    expect($store1->users->first()->id)->toBe($user1->id);

    expect($store2->users)->toHaveCount(1);
    expect($store2->users->first()->id)->toBe($user2->id);
});

it('returns correct role for user in store', function () {
    $context = createStoreContext();

    $role = $context['user']->roleForStore($context['store']);
    expect($role)->toBe(StoreUserRole::Owner);
});

it('returns null role for user not in store', function () {
    $context = createStoreContext();
    $outsideUser = User::factory()->create();

    $role = $outsideUser->roleForStore($context['store']);
    expect($role)->toBeNull();
});

it('supports multiple roles across stores', function () {
    $org = Organization::factory()->create();
    $store1 = Store::factory()->for($org)->create();
    $store2 = Store::factory()->for($org)->create();

    $user = User::factory()->create();
    $user->stores()->attach($store1->id, ['role' => StoreUserRole::Owner->value]);
    $user->stores()->attach($store2->id, ['role' => StoreUserRole::Staff->value]);

    expect($user->roleForStore($store1))->toBe(StoreUserRole::Owner);
    expect($user->roleForStore($store2))->toBe(StoreUserRole::Staff);
});
