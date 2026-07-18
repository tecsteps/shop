<?php

use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Route::middleware(['web', 'storefront'])
        ->get('/_testing/storefront', fn (): array => [
            'store_id' => app('current_store')->id,
        ]);

    Route::middleware(['web', 'auth', 'admin'])
        ->get('/_testing/admin', fn (): array => [
            'store_id' => app('current_store')->id,
        ]);
});

it('resolves the store from the request hostname for storefront requests', function () {
    $store = Store::factory()->create(['name' => 'Acme Fashion']);
    StoreDomain::factory()->create([
        'store_id' => $store->id,
        'hostname' => 'acme-fashion.test',
        'is_primary' => true,
    ]);

    $this->get('http://acme-fashion.test/_testing/storefront')
        ->assertSuccessful()
        ->assertJsonPath('store_id', $store->id);

    expect(app('current_store')->is($store))->toBeTrue();
});

it('returns 404 for unknown hostnames', function () {
    $this->get('http://unknown-store.test/_testing/storefront')
        ->assertNotFound();
});

it('returns 503 for suspended stores on the storefront', function () {
    $store = Store::factory()->suspended()->create();
    StoreDomain::factory()->create([
        'store_id' => $store->id,
        'hostname' => 'suspended.test',
    ]);

    $this->get('http://suspended.test/_testing/storefront')
        ->assertServiceUnavailable();
});

it('resolves the admin store from the session for authenticated users', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->get('/_testing/admin')
        ->assertSuccessful()
        ->assertJsonPath('store_id', $store->id);
});

it('denies admin access when the user is not assigned to the session store', function () {
    $assignedStore = Store::factory()->create();
    $unassignedStore = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($assignedStore, ['role' => 'owner']);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $unassignedStore->id])
        ->get('/_testing/admin')
        ->assertForbidden();
});

it('caches hostname resolution for five minutes', function () {
    $store = Store::factory()->create();
    StoreDomain::factory()->create([
        'store_id' => $store->id,
        'hostname' => 'cached-shop.test',
    ]);

    $this->get('http://cached-shop.test/_testing/storefront')
        ->assertSuccessful();

    expect(Cache::get('store_domain:cached-shop.test'))->toBe($store->id);

    StoreDomain::query()->where('hostname', 'cached-shop.test')->delete();

    $this->get('http://cached-shop.test/_testing/storefront')
        ->assertSuccessful()
        ->assertJsonPath('store_id', $store->id);
});
