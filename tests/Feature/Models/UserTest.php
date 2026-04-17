<?php

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;

it('belongs to many stores through store_users', function () {
    $user = User::factory()->create();
    $stores = Store::factory()->count(2)->create();

    $user->stores()->attach($stores[0]->id, ['role' => 'owner']);
    $user->stores()->attach($stores[1]->id, ['role' => 'staff']);

    $user->refresh();
    expect($user->stores)->toHaveCount(2);
});

it('can get role for a specific store', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();

    $user->stores()->attach($store->id, ['role' => 'admin']);

    expect($user->roleForStore($store))->toBe(StoreUserRole::Admin);
});

it('returns null when user has no role in store', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();

    expect($user->roleForStore($store))->toBeNull();
});

it('can have different roles in different stores', function () {
    $user = User::factory()->create();
    $store1 = Store::factory()->create();
    $store2 = Store::factory()->create();

    $user->stores()->attach($store1->id, ['role' => 'owner']);
    $user->stores()->attach($store2->id, ['role' => 'staff']);

    expect($user->roleForStore($store1))->toBe(StoreUserRole::Owner);
    expect($user->roleForStore($store2))->toBe(StoreUserRole::Staff);
});
