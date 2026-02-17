<?php

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
});

test('owner can manage store settings', function () {
    $user = User::factory()->create();
    $user->stores()->attach($this->store->id, ['role' => StoreUserRole::Owner]);

    $this->actingAs($user);

    expect(Gate::allows('manage-store-settings'))->toBeTrue();
    expect(Gate::allows('manage-staff'))->toBeTrue();
    expect(Gate::allows('manage-developers'))->toBeTrue();
    expect(Gate::allows('view-analytics'))->toBeTrue();
});

test('staff can view analytics but not manage settings', function () {
    $user = User::factory()->create();
    $user->stores()->attach($this->store->id, ['role' => StoreUserRole::Staff]);

    $this->actingAs($user);

    expect(Gate::allows('view-analytics'))->toBeTrue();
    expect(Gate::allows('manage-store-settings'))->toBeFalse();
    expect(Gate::allows('manage-staff'))->toBeFalse();
});

test('support cannot access any gate', function () {
    $user = User::factory()->create();
    $user->stores()->attach($this->store->id, ['role' => StoreUserRole::Support]);

    $this->actingAs($user);

    expect(Gate::allows('manage-store-settings'))->toBeFalse();
    expect(Gate::allows('view-analytics'))->toBeFalse();
    expect(Gate::allows('manage-staff'))->toBeFalse();
});

test('user without store access cannot pass any gate', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    expect(Gate::allows('manage-store-settings'))->toBeFalse();
    expect(Gate::allows('view-analytics'))->toBeFalse();
});
