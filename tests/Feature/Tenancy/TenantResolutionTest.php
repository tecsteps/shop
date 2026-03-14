<?php

use App\Http\Middleware\ResolveStore;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Route::middleware(['web', ResolveStore::class])
        ->get('/storefront-test', function () {
            $store = app('current_store');

            return response()->json(['store_id' => $store->id, 'name' => $store->name]);
        })->name('storefront.test');

    Route::middleware(['web', ResolveStore::class])
        ->get('/admin/test', function () {
            if (app()->bound('current_store')) {
                return response()->json(['store_id' => app('current_store')->id]);
            }

            return response()->json(['store_id' => null]);
        })->name('admin.test');

});

test('resolves store from hostname for storefront requests', function () {
    $ctx = createStoreContext();
    $hostname = $ctx['domain']->hostname;

    // Verify the domain actually exists in DB
    expect(StoreDomain::where('hostname', $hostname)->exists())->toBeTrue();

    $response = $this->call('GET', 'http://'.$hostname.'/storefront-test');

    $response->assertOk()
        ->assertJson(['store_id' => $ctx['store']->id]);
});

test('returns 404 for unknown hostname', function () {
    $response = $this->call('GET', 'http://nonexistent.test/storefront-test');

    $response->assertNotFound();
});

test('returns 503 for suspended store on storefront', function () {
    $ctx = createStoreContext(['status' => \App\Enums\StoreStatus::Suspended]);
    $hostname = $ctx['domain']->hostname;

    $response = $this->call('GET', 'http://'.$hostname.'/storefront-test');

    $response->assertStatus(503);
});

test('resolves store from session for admin requests', function () {
    $ctx = createStoreContext();

    $response = $this->actingAs($ctx['user'])
        ->withSession(['current_store_id' => $ctx['store']->id])
        ->get('/admin/test');

    $response->assertOk()
        ->assertJson(['store_id' => $ctx['store']->id]);
});

test('denies admin access when user has no store_users record', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->get('/admin/test');

    $response->assertForbidden();
});

test('caches hostname lookup', function () {
    $ctx = createStoreContext();
    $hostname = $ctx['domain']->hostname;
    $cacheKey = "store_domain:{$hostname}";

    Cache::forget($cacheKey);

    $this->call('GET', 'http://'.$hostname.'/storefront-test')
        ->assertOk();

    expect(Cache::has($cacheKey))->toBeTrue();

    $this->call('GET', 'http://'.$hostname.'/storefront-test')
        ->assertOk();

    expect(Cache::get($cacheKey))->toBe($ctx['store']->id);
});
