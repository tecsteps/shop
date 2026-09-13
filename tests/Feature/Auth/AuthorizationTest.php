<?php

use App\Enums\StoreUserRole;
use App\Models\Product;
use App\Models\StoreUser;
use App\Models\User;
use App\Tenancy\CurrentStore;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('resolves the role for a store', function () {
    ['store' => $storeA, 'user' => $user] = $this->createStoreContext();
    ['store' => $storeB] = $this->createStoreContext();
    StoreUser::factory()->create([
        'store_id' => $storeB->id, 'user_id' => $user->id, 'role' => StoreUserRole::Staff,
    ]);

    expect($user->roleForStore($storeA))->toBe(StoreUserRole::Owner);
    expect($user->roleForStore($storeB))->toBe(StoreUserRole::Staff);
    expect(User::factory()->create()->roleForStore($storeA))->toBeNull();
});

it('grants owners full product access', function () {
    ['store' => $store, 'user' => $owner] = $this->createStoreContext();
    $product = Product::factory()->create(['store_id' => $store->id]);

    expect($owner->can('viewAny', Product::class))->toBeTrue();
    expect($owner->can('view', $product))->toBeTrue();
    expect($owner->can('create', Product::class))->toBeTrue();
    expect($owner->can('update', $product))->toBeTrue();
    expect($owner->can('delete', $product))->toBeTrue();
    expect($owner->can('archive', $product))->toBeTrue();
    expect($owner->can('restore', $product))->toBeTrue();
});

it('lets staff manage but not delete products', function () {
    ['store' => $store, 'user' => $staff] = $this->createStoreContext(role: StoreUserRole::Staff);
    $product = Product::factory()->create(['store_id' => $store->id]);

    expect($staff->can('view', $product))->toBeTrue();
    expect($staff->can('create', Product::class))->toBeTrue();
    expect($staff->can('update', $product))->toBeTrue();
    expect($staff->can('delete', $product))->toBeFalse();
    expect($staff->can('archive', $product))->toBeFalse();
});

it('restricts support to read-only product access', function () {
    ['store' => $store, 'user' => $support] = $this->createStoreContext(role: StoreUserRole::Support);
    $product = Product::factory()->create(['store_id' => $store->id]);

    expect($support->can('view', $product))->toBeTrue();
    expect($support->can('create', Product::class))->toBeFalse();
    expect($support->can('update', $product))->toBeFalse();
    expect($support->can('delete', $product))->toBeFalse();
});

it('denies policy access across stores', function () {
    ['store' => $storeA, 'user' => $ownerA] = $this->createStoreContext();
    ['store' => $storeB] = $this->createStoreContext();
    $productB = Product::factory()->create(['store_id' => $storeB->id]);

    app(CurrentStore::class)->set($storeA);

    expect($ownerA->can('view', $productB))->toBeFalse();
    expect($ownerA->can('update', $productB))->toBeFalse();
});

it('enforces store deletion as owner-only', function () {
    ['store' => $store, 'user' => $owner] = $this->createStoreContext();
    $admin = User::factory()->create();
    StoreUser::factory()->create([
        'store_id' => $store->id, 'user_id' => $admin->id, 'role' => StoreUserRole::Admin,
    ]);

    expect($owner->can('delete', $store))->toBeTrue();
    expect($admin->can('delete', $store))->toBeFalse();
    expect($owner->can('updateSettings', $store))->toBeTrue();
    expect($admin->can('updateSettings', $store))->toBeTrue();
});

it('checks management gates by role', function () {
    ['store' => $store] = $this->createStoreContext();
    $admin = User::factory()->create();
    $staff = User::factory()->create();
    StoreUser::factory()->create([
        'store_id' => $store->id, 'user_id' => $admin->id, 'role' => StoreUserRole::Admin,
    ]);
    StoreUser::factory()->create([
        'store_id' => $store->id, 'user_id' => $staff->id, 'role' => StoreUserRole::Staff,
    ]);
    app(CurrentStore::class)->set($store);

    expect(Gate::forUser($admin)->allows('manage-store-settings'))->toBeTrue();
    expect(Gate::forUser($admin)->allows('manage-staff'))->toBeTrue();
    expect(Gate::forUser($admin)->allows('manage-shipping'))->toBeTrue();
    expect(Gate::forUser($staff)->allows('manage-store-settings'))->toBeFalse();
    expect(Gate::forUser($staff)->allows('manage-staff'))->toBeFalse();
    expect(Gate::forUser($staff)->allows('view-analytics'))->toBeTrue();
});

it('denies gates without a resolved store', function () {
    ['user' => $owner] = $this->createStoreContext();
    app(CurrentStore::class)->forget();

    expect(Gate::forUser($owner)->allows('manage-store-settings'))->toBeFalse();
});

it('grants the platform gate to owners only', function () {
    ['user' => $owner] = $this->createStoreContext();
    ['user' => $admin] = $this->createStoreContext(role: StoreUserRole::Admin);

    expect(Gate::forUser($owner)->allows('manage-platform'))->toBeTrue();
    expect(Gate::forUser($admin)->allows('manage-platform'))->toBeFalse();
});

it('enforces role middleware on admin routes', function () {
    ['store' => $store, 'user' => $support] = $this->createStoreContext(role: StoreUserRole::Support);

    $this->actingAsAdmin($support, $store)->get('/admin')->assertOk();

    Route::middleware(['web', 'auth', 'store.resolve', 'role.check:owner,admin'])
        ->get('/admin/settings-probe', fn () => 'settings');

    $this->actingAsAdmin($support, $store)->get('/admin/settings-probe')->assertForbidden();

    $owner = User::factory()->create();
    StoreUser::factory()->create([
        'store_id' => $store->id, 'user_id' => $owner->id, 'role' => StoreUserRole::Owner,
    ]);

    $this->actingAsAdmin($owner, $store)->get('/admin/settings-probe')->assertOk();
});
