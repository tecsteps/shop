<?php

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\StoreUser;
use App\Models\User;
use App\Traits\ChecksStoreRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

class StoreRoleCheckerForTest
{
    use ChecksStoreRole;

    public function role(User $user, int $storeId): ?StoreUserRole
    {
        return $this->getStoreRole($user, $storeId);
    }

    public function ownerOrAdmin(User $user, int $storeId): bool
    {
        return $this->isOwnerOrAdmin($user, $storeId);
    }

    public function ownerAdminOrStaff(User $user, int $storeId): bool
    {
        return $this->isOwnerAdminOrStaff($user, $storeId);
    }

    public function anyRole(User $user, int $storeId): bool
    {
        return $this->isAnyRole($user, $storeId);
    }
}

beforeEach(function () {
    Route::middleware(['web', 'auth', 'store.resolve', 'role.check:owner,admin'])
        ->get('/admin/_test/authorized', function (Request $request) {
            return response()->json([
                'role' => $request->attributes->get('store_user')->role->value,
            ]);
        })->name('admin.test.authorized');
});

test('allowed store roles may continue and receive the pivot record', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    StoreUser::factory()->create([
        'store_id' => $store->id,
        'user_id' => $user->id,
        'role' => StoreUserRole::Admin,
    ]);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->get('/admin/_test/authorized')
        ->assertSuccessful()
        ->assertJson(['role' => 'admin']);
});

test('roles outside the allowed set are forbidden', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    StoreUser::factory()->create([
        'store_id' => $store->id,
        'user_id' => $user->id,
        'role' => StoreUserRole::Staff,
    ]);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->get('/admin/_test/authorized')
        ->assertForbidden();
});

test('role checking helpers implement the permission group shorthands', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    StoreUser::factory()->create([
        'store_id' => $store->id,
        'user_id' => $user->id,
        'role' => StoreUserRole::Staff,
    ]);
    $checker = new StoreRoleCheckerForTest;

    expect($checker->role($user, $store->id))->toBe(StoreUserRole::Staff)
        ->and($checker->ownerOrAdmin($user, $store->id))->toBeFalse()
        ->and($checker->ownerAdminOrStaff($user, $store->id))->toBeTrue()
        ->and($checker->anyRole($user, $store->id))->toBeTrue();
});
