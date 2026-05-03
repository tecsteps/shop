<?php

use App\Models\Store;
use App\Models\User;
use App\Policies\DiscountPolicy;
use App\Policies\ProductPolicy;
use App\Policies\StorePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function policySubjectFor(Store $store): object
{
    return new class($store->getKey())
    {
        public function __construct(public int $store_id) {}
    };
}

test('store role helper returns the role for the selected store', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();

    $store->users()->attach($user, ['role' => 'staff', 'created_at' => now()]);

    expect($user->roleForStore($store)?->value)->toBe('staff');
});

test('product policy allows support to view but not mutate products', function () {
    $store = Store::factory()->create();
    $support = User::factory()->create();
    $store->users()->attach($support, ['role' => 'support', 'created_at' => now()]);

    app()->instance('current_store', $store);
    $product = policySubjectFor($store);
    $policy = new ProductPolicy;

    expect($policy->viewAny($support))->toBeTrue()
        ->and($policy->view($support, $product))->toBeTrue()
        ->and($policy->create($support))->toBeFalse()
        ->and($policy->update($support, $product))->toBeFalse()
        ->and($policy->delete($support, $product))->toBeFalse();
});

test('staff can manage discounts but only owner or admin can delete them', function () {
    $store = Store::factory()->create();
    $staff = User::factory()->create();
    $store->users()->attach($staff, ['role' => 'staff', 'created_at' => now()]);

    app()->instance('current_store', $store);
    $discount = policySubjectFor($store);
    $policy = new DiscountPolicy;

    expect($policy->create($staff))->toBeTrue()
        ->and($policy->update($staff, $discount))->toBeTrue()
        ->and($policy->delete($staff, $discount))->toBeFalse();
});

test('only owners can delete stores', function () {
    $store = Store::factory()->create();
    $owner = User::factory()->create();
    $admin = User::factory()->create();

    $store->users()->attach($owner, ['role' => 'owner', 'created_at' => now()]);
    $store->users()->attach($admin, ['role' => 'admin', 'created_at' => now()]);

    $policy = new StorePolicy;

    expect($policy->delete($owner, $store))->toBeTrue()
        ->and($policy->delete($admin, $store))->toBeFalse()
        ->and($policy->update($admin, $store))->toBeTrue();
});
