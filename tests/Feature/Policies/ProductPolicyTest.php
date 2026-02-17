<?php

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;
use App\Policies\ProductPolicy;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->policy = new ProductPolicy;
});

test('any role can view products', function (StoreUserRole $role) {
    $user = User::factory()->create();
    $user->stores()->attach($this->store->id, ['role' => $role]);

    expect($this->policy->viewAny($user))->toBeTrue();
})->with([
    StoreUserRole::Owner,
    StoreUserRole::Admin,
    StoreUserRole::Staff,
    StoreUserRole::Support,
]);

test('owner, admin, and staff can create products', function (StoreUserRole $role) {
    $user = User::factory()->create();
    $user->stores()->attach($this->store->id, ['role' => $role]);

    expect($this->policy->create($user))->toBeTrue();
})->with([
    StoreUserRole::Owner,
    StoreUserRole::Admin,
    StoreUserRole::Staff,
]);

test('support cannot create products', function () {
    $user = User::factory()->create();
    $user->stores()->attach($this->store->id, ['role' => StoreUserRole::Support]);

    expect($this->policy->create($user))->toBeFalse();
});

test('user without store access cannot view products', function () {
    $user = User::factory()->create();

    expect($this->policy->viewAny($user))->toBeFalse();
});
