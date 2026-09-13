<?php

use App\Enums\StoreStatus;
use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreUser;
use App\Models\User;
use App\Tenancy\CurrentStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('resolves store from hostname for storefront requests', function () {
    $store = Store::factory()->create(['status' => StoreStatus::Active]);
    StoreDomain::factory()->create(['store_id' => $store->id, 'hostname' => 'acme-fashion.test']);

    $response = $this->get('http://acme-fashion.test/');

    $response->assertOk();
    expect(app(CurrentStore::class)->get()?->id)->toBe($store->id);
    expect(app()->bound('current_store'))->toBeTrue();
});

it('returns 404 for unknown hostname', function () {
    $response = $this->get('http://nonexistent.test/');

    $response->assertNotFound();
    expect(app(CurrentStore::class)->get())->toBeNull();
});

it('returns 503 for suspended store on storefront', function () {
    $store = Store::factory()->create(['status' => StoreStatus::Suspended]);
    StoreDomain::factory()->create(['store_id' => $store->id, 'hostname' => 'suspended.test']);

    $response = $this->get('http://suspended.test/');

    $response->assertStatus(503);
});

it('resolves store from session for admin requests', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();

    $response = $this->actingAsAdmin($user, $store)->get('/admin');

    $response->assertOk();
    expect(app(CurrentStore::class)->get()?->id)->toBe($store->id);
});

it('denies admin access when user has no store_users record', function () {
    $store = Store::factory()->create(['status' => StoreStatus::Active]);
    $stranger = User::factory()->create();

    $response = $this->actingAs($stranger)->withSession(['current_store_id' => $store->id])->get('/admin');

    $response->assertForbidden();
});

it('caches hostname lookup', function () {
    $store = Store::factory()->create(['status' => StoreStatus::Active]);
    StoreDomain::factory()->create(['store_id' => $store->id, 'hostname' => 'cached.test']);

    $this->get('http://cached.test/')->assertOk();

    expect(Cache::has('store_domain:cached.test'))->toBeTrue();
    expect(Cache::get('store_domain:cached.test'))->toBe($store->id);

    DB::enableQueryLog();
    $this->get('http://cached.test/')->assertOk();
    $domainQueries = collect(DB::getQueryLog())->filter(fn (array $query): bool => str_contains($query['query'], 'store_domains'));

    expect($domainQueries)->toBeEmpty();
});

it('allows admin reads but blocks mutations on suspended stores', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $store->update(['status' => StoreStatus::Suspended]);

    $this->actingAsAdmin($user, $store)->get('/admin')->assertOk();

    Route::middleware(['web', 'auth', 'store.resolve'])->post('/admin/mutation-probe', fn () => 'mutated');

    $this->actingAsAdmin($user, $store)->post('/admin/mutation-probe')->assertForbidden();
});

it('redirects to the store selector when the session store is missing', function () {
    $user = User::factory()->create();
    $storeA = Store::factory()->create(['status' => StoreStatus::Active]);
    $storeB = Store::factory()->create(['status' => StoreStatus::Active]);
    StoreUser::factory()->create(['store_id' => $storeA->id, 'user_id' => $user->id, 'role' => StoreUserRole::Admin]);
    StoreUser::factory()->create(['store_id' => $storeB->id, 'user_id' => $user->id, 'role' => StoreUserRole::Admin]);

    $response = $this->actingAs($user)->get('/admin');

    $response->assertRedirect(route('admin.store-selector'));
});

it('auto-selects the only store when the session store is missing', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();

    $response = $this->actingAs($user)->get('/admin');

    $response->assertOk();
    $response->assertSessionHas('current_store_id', $store->id);
});

it('resolves store from the ?store handle fallback', function () {
    $store = Store::factory()->create(['status' => StoreStatus::Active, 'handle' => 'fallback-shop']);

    $response = $this->get('http://unknown-host.test/?store=fallback-shop');

    $response->assertOk();
    expect(app(CurrentStore::class)->get()?->id)->toBe($store->id);
});
