<?php

use App\Enums\StoreUserRole;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Policies\ProductPolicy;
use App\Policies\StorePolicy;
use Tests\TestCase;

uses(TestCase::class, \Illuminate\Foundation\Testing\LazilyRefreshDatabase::class);

beforeEach(function (): void {
    app()->forgetInstance('current_store');
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

test('product permissions are based on the users role in the current store', function () {
    $store = Store::factory()->create();
    $otherStore = Store::factory()->create();
    $staff = User::factory()->create();

    $store->users()->attach($staff, ['role' => StoreUserRole::Staff]);
    app()->instance('current_store', $store);

    $policy = new ProductPolicy;

    expect($policy->viewAny($staff))->toBeTrue()
        ->and($policy->create($staff))->toBeTrue()
        ->and($policy->delete($staff, Product::make(['store_id' => $store->getKey()])))->toBeFalse()
        ->and($policy->view($staff, Product::make(['store_id' => $otherStore->getKey()])))->toBeFalse();
});

test('store deletion is restricted to owners', function () {
    $store = Store::factory()->create();
    $owner = User::factory()->create();
    $admin = User::factory()->create();

    $store->users()->attach($owner, ['role' => StoreUserRole::Owner]);
    $store->users()->attach($admin, ['role' => StoreUserRole::Admin]);

    $policy = new StorePolicy;

    expect($policy->delete($owner, $store))->toBeTrue()
        ->and($policy->delete($admin, $store))->toBeFalse()
        ->and($policy->update($admin, $store))->toBeTrue();
});
