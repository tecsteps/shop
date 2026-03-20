<?php

use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
});

function createGateUser(Store $store, string $role): User
{
    $user = User::factory()->create();
    $store->users()->attach($user->id, ['role' => $role]);

    return $user;
}

it('manage-store-settings gate allows only owner and admin', function (string $role, bool $expected) {
    $user = createGateUser($this->store, $role);

    $this->actingAs($user);
    expect(Gate::allows('manage-store-settings'))->toBe($expected);
})->with([
    ['owner', true],
    ['admin', true],
    ['staff', false],
    ['support', false],
]);

it('manage-staff gate allows only owner and admin', function (string $role, bool $expected) {
    $user = createGateUser($this->store, $role);

    $this->actingAs($user);
    expect(Gate::allows('manage-staff'))->toBe($expected);
})->with([
    ['owner', true],
    ['admin', true],
    ['staff', false],
    ['support', false],
]);

it('manage-developers gate allows only owner and admin', function (string $role, bool $expected) {
    $user = createGateUser($this->store, $role);

    $this->actingAs($user);
    expect(Gate::allows('manage-developers'))->toBe($expected);
})->with([
    ['owner', true],
    ['admin', true],
    ['staff', false],
    ['support', false],
]);

it('view-analytics gate allows owner, admin, and staff', function (string $role, bool $expected) {
    $user = createGateUser($this->store, $role);

    $this->actingAs($user);
    expect(Gate::allows('view-analytics'))->toBe($expected);
})->with([
    ['owner', true],
    ['admin', true],
    ['staff', true],
    ['support', false],
]);

it('manage-shipping gate allows only owner and admin', function (string $role, bool $expected) {
    $user = createGateUser($this->store, $role);

    $this->actingAs($user);
    expect(Gate::allows('manage-shipping'))->toBe($expected);
})->with([
    ['owner', true],
    ['admin', true],
    ['staff', false],
    ['support', false],
]);

it('manage-taxes gate allows only owner and admin', function (string $role, bool $expected) {
    $user = createGateUser($this->store, $role);

    $this->actingAs($user);
    expect(Gate::allows('manage-taxes'))->toBe($expected);
})->with([
    ['owner', true],
    ['admin', true],
    ['staff', false],
    ['support', false],
]);

it('manage-search-settings gate allows only owner and admin', function (string $role, bool $expected) {
    $user = createGateUser($this->store, $role);

    $this->actingAs($user);
    expect(Gate::allows('manage-search-settings'))->toBe($expected);
})->with([
    ['owner', true],
    ['admin', true],
    ['staff', false],
    ['support', false],
]);

it('manage-navigation gate allows only owner and admin', function (string $role, bool $expected) {
    $user = createGateUser($this->store, $role);

    $this->actingAs($user);
    expect(Gate::allows('manage-navigation'))->toBe($expected);
})->with([
    ['owner', true],
    ['admin', true],
    ['staff', false],
    ['support', false],
]);
