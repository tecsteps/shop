<?php

use App\Enums\StoreUserRole;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function makeStoreUser(StoreUserRole $role): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    DB::table('store_users')->insert([
        'store_id' => $store->getKey(),
        'user_id' => $user->getKey(),
        'role' => $role->value,
        'created_at' => now(),
    ]);

    session(['current_store_id' => $store->getKey()]);
    app()->instance('current_store', $store);

    return [$store, $user];
}

it('ProductPolicy allows owner to create and denies support', function (): void {
    [$store, $owner] = makeStoreUser(StoreUserRole::Owner);
    expect($owner->can('create', Product::class))->toBeTrue();

    [$store2, $support] = makeStoreUser(StoreUserRole::Support);
    expect($support->can('create', Product::class))->toBeFalse();
});

it('OrderPolicy allows owner to create refunds and denies staff', function (): void {
    [$store, $owner] = makeStoreUser(StoreUserRole::Owner);
    $order = Order::factory()->create(['store_id' => $store->getKey()]);
    expect($owner->can('createRefund', $order))->toBeTrue();

    [$store2, $staff] = makeStoreUser(StoreUserRole::Staff);
    $order2 = Order::factory()->create(['store_id' => $store2->getKey()]);
    expect($staff->can('createRefund', $order2))->toBeFalse();
});

it('PagePolicy allows staff to update pages', function (): void {
    [$store, $staff] = makeStoreUser(StoreUserRole::Staff);
    $page = Page::factory()->create(['store_id' => $store->getKey()]);
    expect($staff->can('update', $page))->toBeTrue();
});

it('DiscountPolicy denies support user to create a discount', function (): void {
    [$store, $support] = makeStoreUser(StoreUserRole::Support);
    expect($support->can('create', Discount::class))->toBeFalse();
});
