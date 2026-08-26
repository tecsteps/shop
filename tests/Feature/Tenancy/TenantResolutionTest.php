<?php

use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

it('resolves store from hostname for storefront requests', function () {
    $organization = Organization::factory()->create();
    $store = Store::factory()->create(['organization_id' => $organization->id]);
    StoreDomain::factory()->create(['store_id' => $store->id, 'hostname' => 'acme-fashion.test']);

    $this->get('http://acme-fashion.test/')->assertStatus(200);

    expect(app('current_store')->id)->toBe($store->id);
});

it('returns 404 for unknown hostname', function () {
    $this->get('http://nonexistent.test/')->assertStatus(404);
});

it('returns 503 for suspended store on storefront', function () {
    $organization = Organization::factory()->create();
    $store = Store::factory()->suspended()->create(['organization_id' => $organization->id]);
    StoreDomain::factory()->create(['store_id' => $store->id, 'hostname' => 'suspended.test']);

    $this->get('http://suspended.test/')->assertStatus(503);
});

it('resolves store from session for admin requests', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    $this->actingAs($user)->withSession(['current_store_id' => $store->id])->get('/admin')->assertStatus(200);
});

it('denies admin access when user has no store_users record', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->withSession(['current_store_id' => $store->id])->get('/admin')->assertStatus(403);
});

it('caches hostname lookup', function () {
    $organization = Organization::factory()->create();
    $store = Store::factory()->create(['organization_id' => $organization->id]);
    StoreDomain::factory()->create(['store_id' => $store->id, 'hostname' => 'cache-me.test']);

    $this->get('http://cache-me.test/')->assertStatus(200);

    expect(Cache::has('store_domain:cache-me.test'))->toBeTrue();
});
