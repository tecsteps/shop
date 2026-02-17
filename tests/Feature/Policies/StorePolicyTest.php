<?php

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;
use App\Policies\StorePolicy;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->policy = new StorePolicy;
});

test('owner and admin can view settings', function (StoreUserRole $role) {
    $user = User::factory()->create();
    $user->stores()->attach($this->store->id, ['role' => $role]);

    expect($this->policy->viewSettings($user, $this->store))->toBeTrue();
})->with([
    StoreUserRole::Owner,
    StoreUserRole::Admin,
]);

test('staff and support cannot view settings', function (StoreUserRole $role) {
    $user = User::factory()->create();
    $user->stores()->attach($this->store->id, ['role' => $role]);

    expect($this->policy->viewSettings($user, $this->store))->toBeFalse();
})->with([
    StoreUserRole::Staff,
    StoreUserRole::Support,
]);

test('only owner can delete store', function () {
    $owner = User::factory()->create();
    $owner->stores()->attach($this->store->id, ['role' => StoreUserRole::Owner]);

    $admin = User::factory()->create();
    $admin->stores()->attach($this->store->id, ['role' => StoreUserRole::Admin]);

    expect($this->policy->delete($owner, $this->store))->toBeTrue();
    expect($this->policy->delete($admin, $this->store))->toBeFalse();
});
