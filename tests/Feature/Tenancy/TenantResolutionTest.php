<?php

use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::clear();

    Route::middleware('store.resolve')
        ->get('/_test/storefront', function () {
            return response()->json([
                'store_id' => app('current_store')->id,
                'shared_store_id' => View::shared('currentStore')->id,
            ]);
        });

    Route::middleware(['web', 'auth', 'store.resolve'])
        ->match(['GET', 'POST'], '/admin/_test/store', function (Request $request) {
            return response()->json([
                'store_id' => app('current_store')->id,
                'method' => $request->method(),
            ]);
        })->name('admin.test.store');
});

test('storefront requests resolve and share the store by hostname', function () {
    $store = Store::factory()->create();
    StoreDomain::factory()->for($store)->create(['hostname' => 'tenant.test']);

    $this->get('http://tenant.test/_test/storefront')
        ->assertSuccessful()
        ->assertJson([
            'store_id' => $store->id,
            'shared_store_id' => $store->id,
        ]);

    expect(Cache::get('store_domain:tenant.test'))->toBe($store->id);
});

test('unknown and suspended storefronts are rejected', function () {
    $this->get('http://unknown.test/_test/storefront')->assertNotFound();

    $store = Store::factory()->suspended()->create();
    StoreDomain::factory()->for($store)->create(['hostname' => 'suspended.test']);

    $this->get('http://suspended.test/_test/storefront')
        ->assertServiceUnavailable();
});

test('admin requests resolve the selected store from the session', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    StoreUser::factory()->create([
        'store_id' => $store->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->get('/admin/_test/store')
        ->assertSuccessful()
        ->assertJson(['store_id' => $store->id]);
});

test('admin requests cannot select a store the user cannot access', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->get('/admin/_test/store')
        ->assertForbidden();
});

test('suspended stores allow admin reads but reject mutations', function () {
    $store = Store::factory()->suspended()->create();
    $user = User::factory()->create();
    StoreUser::factory()->create([
        'store_id' => $store->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)->withSession(['current_store_id' => $store->id]);

    $this->get('/admin/_test/store')->assertSuccessful();
    $this->post('/admin/_test/store')->assertForbidden();
});
