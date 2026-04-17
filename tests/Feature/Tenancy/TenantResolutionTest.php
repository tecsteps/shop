<?php

use App\Http\Middleware\ResolveStore;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', ResolveStore::class.':storefront'])
        ->get('/test-storefront', function () {
            $store = app('current_store');

            return response()->json(['store' => $store->name]);
        });

    Route::middleware(['web', 'auth', ResolveStore::class.':admin'])
        ->get('/test-admin', function () {
            $store = app('current_store');

            return response()->json(['store' => $store->name]);
        });
});

it('resolves store from hostname on storefront requests', function () {
    $store = Store::factory()->create(['name' => 'Acme Fashion']);
    StoreDomain::factory()->create([
        'store_id' => $store->id,
        'hostname' => 'acme-fashion.test',
        'type' => 'storefront',
    ]);

    $response = $this->get('https://acme-fashion.test/test-storefront');

    $response->assertOk();
    $response->assertJson(['store' => 'Acme Fashion']);
});

it('returns 404 for unknown hostname', function () {
    $response = $this->get('https://unknown-shop.test/test-storefront');

    $response->assertNotFound();
});

it('returns 503 for suspended store', function () {
    $store = Store::factory()->suspended()->create();
    StoreDomain::factory()->create([
        'store_id' => $store->id,
        'hostname' => 'suspended-shop.test',
    ]);

    $response = $this->get('https://suspended-shop.test/test-storefront');

    $response->assertStatus(503);
});

it('caches hostname-to-store mapping', function () {
    $store = Store::factory()->create();
    StoreDomain::factory()->create([
        'store_id' => $store->id,
        'hostname' => 'cached-shop.test',
    ]);

    $this->get('https://cached-shop.test/test-storefront');

    expect(Cache::has('store_domain:cached-shop.test'))->toBeTrue();
});

it('resolves store from session for admin requests', function () {
    $store = Store::factory()->create(['name' => 'Acme Fashion']);
    $user = User::factory()->create();
    $store->users()->attach($user->id, ['role' => 'owner']);

    $response = $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->get('/test-admin');

    $response->assertOk();
    $response->assertJson(['store' => 'Acme Fashion']);
});

it('returns 403 for admin request without store_users record', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->get('/test-admin');

    $response->assertForbidden();
});
