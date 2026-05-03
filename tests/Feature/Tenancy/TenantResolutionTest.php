<?php

use App\Enums\StoreStatus;
use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();

    Route::middleware('storefront')->get('/tenant-probe', function () {
        return app('current_store')->handle;
    });

    Route::middleware(['web', 'auth', 'admin'])->get('/admin/tenant-probe', function () {
        return app('current_store')->handle;
    });
});

test('storefront routes resolve the current store from the request hostname', function () {
    $store = Store::factory()->create(['handle' => 'acme']);
    StoreDomain::factory()->for($store)->create(['hostname' => 'acme.test']);

    $this->get('http://acme.test/tenant-probe')
        ->assertOk()
        ->assertSee('acme');
});

test('storefront routes return not found for unknown hostnames', function () {
    $this->get('http://missing.test/tenant-probe')
        ->assertNotFound();
});

test('storefront routes reject suspended stores', function () {
    $store = Store::factory()->create([
        'status' => StoreStatus::Suspended,
    ]);
    StoreDomain::factory()->for($store)->create(['hostname' => 'suspended.test']);

    $this->get('http://suspended.test/tenant-probe')
        ->assertStatus(503)
        ->assertSee("We'll be back soon", false)
        ->assertSee($store->name);
});

test('admin routes resolve the current store from the authenticated session', function () {
    $store = Store::factory()->create(['handle' => 'admin-store']);
    $user = User::factory()->create();

    DB::table('store_users')->insert([
        'store_id' => $store->id,
        'user_id' => $user->id,
        'role' => StoreUserRole::Admin->value,
        'created_at' => now(),
    ]);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->get('/admin/tenant-probe')
        ->assertOk()
        ->assertSee('admin-store');
});

test('admin routes reject stores outside the authenticated user membership', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->get('/admin/tenant-probe')
        ->assertForbidden();
});
