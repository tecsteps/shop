<?php

use App\Enums\StoreDomainType;
use App\Enums\StoreStatus;
use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use Illuminate\Support\Facades\Route;

uses(\Illuminate\Foundation\Testing\LazilyRefreshDatabase::class);

beforeEach(function (): void {
    config(['cache.default' => 'array']);

    Route::middleware(['web', 'storefront'])->get('/tenant-resolution-test', function () {
        return response()->json([
            'store_id' => app('current_store')->getKey(),
            'view_store_id' => view()->shared('currentStore')->getKey(),
        ]);
    });

    Route::middleware(['web', 'admin'])->get('/admin/tenant-resolution-test', function () {
        return response()->json(['store_id' => app('current_store')->getKey()]);
    });
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

test('a storefront request resolves and shares the store by hostname', function () {
    $store = Store::factory()->create();

    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => 'fashion.example.test',
        'type' => StoreDomainType::Storefront,
    ]);

    $this->get('http://fashion.example.test/tenant-resolution-test')
        ->assertSuccessful()
        ->assertJson([
            'store_id' => $store->getKey(),
            'view_store_id' => $store->getKey(),
        ]);
});

test('a storefront hostname is cached as a store id', function () {
    $store = Store::factory()->create();

    $domain = StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => 'cached.example.test',
    ]);

    $this->get('http://cached.example.test/tenant-resolution-test')->assertSuccessful();

    $domain->delete();

    $this->get('http://cached.example.test/tenant-resolution-test')
        ->assertSuccessful()
        ->assertJson(['store_id' => $store->getKey()]);
});

test('unknown storefront hosts return not found', function () {
    $this->get('http://missing.example.test/tenant-resolution-test')->assertNotFound();
});

test('suspended storefronts return service unavailable', function () {
    $store = Store::factory()->create(['status' => StoreStatus::Suspended]);

    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => 'suspended.example.test',
    ]);

    $this->get('http://suspended.example.test/tenant-resolution-test')
        ->assertServiceUnavailable();
});

test('admin requests resolve only the session store the user belongs to', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();

    $store->users()->attach($user, ['role' => StoreUserRole::Owner]);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->getKey()])
        ->get('/admin/tenant-resolution-test')
        ->assertSuccessful()
        ->assertJson(['store_id' => $store->getKey()]);
});

test('admin requests reject a session store without membership', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->getKey()])
        ->get('/admin/tenant-resolution-test')
        ->assertForbidden();
});

test('admin auth livewire updates do not require a resolved store', function () {
    $request = \Illuminate\Http\Request::create('/livewire-test123/update', 'POST', [], [], [], [
        'HTTP_REFERER' => 'http://admin.example.test/admin/login',
    ]);

    expect(app(\App\Http\Middleware\ResolveStore::class)->handle($request, fn (): \Symfony\Component\HttpFoundation\Response => response('ok')))
        ->getContent()
        ->toBe('ok');
});
