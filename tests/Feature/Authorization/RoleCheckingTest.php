<?php

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;
use App\Traits\ChecksStoreRole;

beforeEach(function () {
    $this->checker = new class
    {
        use ChecksStoreRole;

        public function publicGetStoreRole(User $user, int $storeId): ?StoreUserRole
        {
            return $this->getStoreRole($user, $storeId);
        }

        public function publicHasRole(User $user, int $storeId, array $roles): bool
        {
            return $this->hasRole($user, $storeId, $roles);
        }

        public function publicIsOwnerOrAdmin(User $user, int $storeId): bool
        {
            return $this->isOwnerOrAdmin($user, $storeId);
        }

        public function publicIsOwnerAdminOrStaff(User $user, int $storeId): bool
        {
            return $this->isOwnerAdminOrStaff($user, $storeId);
        }

        public function publicIsAnyRole(User $user, int $storeId): bool
        {
            return $this->isAnyRole($user, $storeId);
        }
    };
});

it('getStoreRole returns the user role for a store', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'admin']);

    expect($this->checker->publicGetStoreRole($user, $store->id))->toBe(StoreUserRole::Admin);
});

it('getStoreRole returns null when user has no role', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();

    expect($this->checker->publicGetStoreRole($user, $store->id))->toBeNull();
});

it('hasRole returns true when role matches', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'staff']);

    expect($this->checker->publicHasRole($user, $store->id, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]))->toBeTrue();
});

it('hasRole returns false when role does not match', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'support']);

    expect($this->checker->publicHasRole($user, $store->id, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]))->toBeFalse();
});

it('hasRole returns false when user has no role', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();

    expect($this->checker->publicHasRole($user, $store->id, [StoreUserRole::Owner]))->toBeFalse();
});

it('isOwnerOrAdmin returns true for Owner', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    expect($this->checker->publicIsOwnerOrAdmin($user, $store->id))->toBeTrue();
});

it('isOwnerOrAdmin returns true for Admin', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'admin']);

    expect($this->checker->publicIsOwnerOrAdmin($user, $store->id))->toBeTrue();
});

it('isOwnerOrAdmin returns false for Staff', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'staff']);

    expect($this->checker->publicIsOwnerOrAdmin($user, $store->id))->toBeFalse();
});

it('isOwnerAdminOrStaff returns true for Staff', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'staff']);

    expect($this->checker->publicIsOwnerAdminOrStaff($user, $store->id))->toBeTrue();
});

it('isOwnerAdminOrStaff returns false for Support', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'support']);

    expect($this->checker->publicIsOwnerAdminOrStaff($user, $store->id))->toBeFalse();
});

it('isAnyRole returns true for any valid role', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'support']);

    expect($this->checker->publicIsAnyRole($user, $store->id))->toBeTrue();
});

it('isAnyRole returns false when user has no role', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();

    expect($this->checker->publicIsAnyRole($user, $store->id))->toBeFalse();
});
