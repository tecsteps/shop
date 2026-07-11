<?php

use App\Enums\StoreUserRole;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Store;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

function policyUser(Store $store, StoreUserRole $role): User
{
    $user = User::factory()->create();
    StoreUser::query()->create(['store_id' => $store->id, 'user_id' => $user->id, 'role' => $role, 'created_at' => now()]);

    return $user;
}

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->order = Order::factory()->for($this->store)->create();
    $this->customer = Customer::factory()->for($this->store)->create();
});

it('gives support read only access', function () {
    $support = policyUser($this->store, StoreUserRole::Support);

    expect(Gate::forUser($support)->allows('view', $this->order))->toBeTrue()
        ->and(Gate::forUser($support)->allows('update', $this->order))->toBeFalse()
        ->and(Gate::forUser($support)->allows('view', $this->customer))->toBeTrue()
        ->and(Gate::forUser($support)->allows('update', $this->customer))->toBeFalse();
});

it('allows staff operations but blocks refunds and settings', function () {
    $staff = policyUser($this->store, StoreUserRole::Staff);

    expect(Gate::forUser($staff)->allows('update', $this->order))->toBeTrue()
        ->and(Gate::forUser($staff)->allows('createFulfillment', $this->order))->toBeTrue()
        ->and(Gate::forUser($staff)->allows('createRefund', $this->order))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('manage-store-settings'))->toBeFalse();
});

it('reserves destructive store operations for owners', function () {
    $owner = policyUser($this->store, StoreUserRole::Owner);
    $admin = policyUser($this->store, StoreUserRole::Admin);

    expect(Gate::forUser($owner)->allows('delete', $this->store))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', $this->store))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('createRefund', $this->order))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manage-store-settings'))->toBeTrue();
});
