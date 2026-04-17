<?php

use App\Models\Store;
use App\Models\User;
use App\Policies\CollectionPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\DiscountPolicy;
use App\Policies\FulfillmentPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PagePolicy;
use App\Policies\ProductPolicy;
use App\Policies\RefundPolicy;
use App\Policies\StorePolicy;
use App\Policies\ThemePolicy;

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
});

function createUserWithRole(Store $store, string $role): User
{
    $user = User::factory()->create();
    $store->users()->attach($user->id, ['role' => $role]);

    return $user;
}

function makeModel(int $storeId): object
{
    return (object) ['store_id' => $storeId];
}

// ProductPolicy
it('ProductPolicy viewAny - any role can list', function (string $role) {
    $user = createUserWithRole($this->store, $role);
    $policy = new ProductPolicy;
    expect($policy->viewAny($user))->toBeTrue();
})->with(['owner', 'admin', 'staff', 'support']);

it('ProductPolicy viewAny - no role is denied', function () {
    $user = User::factory()->create();
    $policy = new ProductPolicy;
    expect($policy->viewAny($user))->toBeFalse();
});

it('ProductPolicy create - owner, admin, staff can create', function (string $role, bool $expected) {
    $user = createUserWithRole($this->store, $role);
    $policy = new ProductPolicy;
    expect($policy->create($user))->toBe($expected);
})->with([
    ['owner', true],
    ['admin', true],
    ['staff', true],
    ['support', false],
]);

it('ProductPolicy delete - only owner and admin', function (string $role, bool $expected) {
    $user = createUserWithRole($this->store, $role);
    $policy = new ProductPolicy;
    expect($policy->delete($user, makeModel($this->store->id)))->toBe($expected);
})->with([
    ['owner', true],
    ['admin', true],
    ['staff', false],
    ['support', false],
]);

// OrderPolicy
it('OrderPolicy viewAny - any role can list', function (string $role) {
    $user = createUserWithRole($this->store, $role);
    $policy = new OrderPolicy;
    expect($policy->viewAny($user))->toBeTrue();
})->with(['owner', 'admin', 'staff', 'support']);

it('OrderPolicy update - owner, admin, staff can update', function (string $role, bool $expected) {
    $user = createUserWithRole($this->store, $role);
    $policy = new OrderPolicy;
    expect($policy->update($user, makeModel($this->store->id)))->toBe($expected);
})->with([
    ['owner', true],
    ['admin', true],
    ['staff', true],
    ['support', false],
]);

it('OrderPolicy cancel - only owner and admin', function (string $role, bool $expected) {
    $user = createUserWithRole($this->store, $role);
    $policy = new OrderPolicy;
    expect($policy->cancel($user, makeModel($this->store->id)))->toBe($expected);
})->with([
    ['owner', true],
    ['admin', true],
    ['staff', false],
    ['support', false],
]);

it('OrderPolicy createRefund - only owner and admin', function (string $role, bool $expected) {
    $user = createUserWithRole($this->store, $role);
    $policy = new OrderPolicy;
    expect($policy->createRefund($user, makeModel($this->store->id)))->toBe($expected);
})->with([
    ['owner', true],
    ['admin', true],
    ['staff', false],
    ['support', false],
]);

// StorePolicy
it('StorePolicy viewSettings - only owner and admin', function (string $role, bool $expected) {
    $user = createUserWithRole($this->store, $role);
    $policy = new StorePolicy;
    expect($policy->viewSettings($user, $this->store))->toBe($expected);
})->with([
    ['owner', true],
    ['admin', true],
    ['staff', false],
    ['support', false],
]);

it('StorePolicy delete - only owner', function (string $role, bool $expected) {
    $user = createUserWithRole($this->store, $role);
    $policy = new StorePolicy;
    expect($policy->delete($user, $this->store))->toBe($expected);
})->with([
    ['owner', true],
    ['admin', false],
    ['staff', false],
    ['support', false],
]);

// ThemePolicy
it('ThemePolicy viewAny - only owner and admin', function (string $role, bool $expected) {
    $user = createUserWithRole($this->store, $role);
    $policy = new ThemePolicy;
    expect($policy->viewAny($user))->toBe($expected);
})->with([
    ['owner', true],
    ['admin', true],
    ['staff', false],
    ['support', false],
]);

// PagePolicy
it('PagePolicy viewAny - owner, admin, staff', function (string $role, bool $expected) {
    $user = createUserWithRole($this->store, $role);
    $policy = new PagePolicy;
    expect($policy->viewAny($user))->toBe($expected);
})->with([
    ['owner', true],
    ['admin', true],
    ['staff', true],
    ['support', false],
]);

it('PagePolicy delete - only owner and admin', function (string $role, bool $expected) {
    $user = createUserWithRole($this->store, $role);
    $policy = new PagePolicy;
    expect($policy->delete($user, makeModel($this->store->id)))->toBe($expected);
})->with([
    ['owner', true],
    ['admin', true],
    ['staff', false],
    ['support', false],
]);

// CollectionPolicy
it('CollectionPolicy viewAny - any role can list', function (string $role) {
    $user = createUserWithRole($this->store, $role);
    $policy = new CollectionPolicy;
    expect($policy->viewAny($user))->toBeTrue();
})->with(['owner', 'admin', 'staff', 'support']);

it('CollectionPolicy delete - only owner and admin', function (string $role, bool $expected) {
    $user = createUserWithRole($this->store, $role);
    $policy = new CollectionPolicy;
    expect($policy->delete($user, makeModel($this->store->id)))->toBe($expected);
})->with([
    ['owner', true],
    ['admin', true],
    ['staff', false],
    ['support', false],
]);

// DiscountPolicy
it('DiscountPolicy viewAny - any role can list', function (string $role) {
    $user = createUserWithRole($this->store, $role);
    $policy = new DiscountPolicy;
    expect($policy->viewAny($user))->toBeTrue();
})->with(['owner', 'admin', 'staff', 'support']);

it('DiscountPolicy delete - only owner and admin', function (string $role, bool $expected) {
    $user = createUserWithRole($this->store, $role);
    $policy = new DiscountPolicy;
    expect($policy->delete($user, makeModel($this->store->id)))->toBe($expected);
})->with([
    ['owner', true],
    ['admin', true],
    ['staff', false],
    ['support', false],
]);

// CustomerPolicy
it('CustomerPolicy viewAny - any role can list', function (string $role) {
    $user = createUserWithRole($this->store, $role);
    $policy = new CustomerPolicy;
    expect($policy->viewAny($user))->toBeTrue();
})->with(['owner', 'admin', 'staff', 'support']);

it('CustomerPolicy update - owner, admin, staff can update', function (string $role, bool $expected) {
    $user = createUserWithRole($this->store, $role);
    $policy = new CustomerPolicy;
    expect($policy->update($user, makeModel($this->store->id)))->toBe($expected);
})->with([
    ['owner', true],
    ['admin', true],
    ['staff', true],
    ['support', false],
]);

// RefundPolicy
it('RefundPolicy create - only owner and admin', function (string $role, bool $expected) {
    $user = createUserWithRole($this->store, $role);
    $policy = new RefundPolicy;
    expect($policy->create($user, makeModel($this->store->id)))->toBe($expected);
})->with([
    ['owner', true],
    ['admin', true],
    ['staff', false],
    ['support', false],
]);

// FulfillmentPolicy
it('FulfillmentPolicy create - owner, admin, staff', function (string $role, bool $expected) {
    $user = createUserWithRole($this->store, $role);
    $policy = new FulfillmentPolicy;
    expect($policy->create($user, makeModel($this->store->id)))->toBe($expected);
})->with([
    ['owner', true],
    ['admin', true],
    ['staff', true],
    ['support', false],
]);
