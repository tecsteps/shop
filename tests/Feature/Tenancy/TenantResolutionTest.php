<?php

use App\Enums\StoreStatus;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

it('resolves store from hostname for storefront requests', function () {
    $ctx = createStoreContext('acme-fashion.test');

    $response = $this->get('http://acme-fashion.test/');

    $response->assertStatus(200);
    expect(app('current_store')->id)->toBe($ctx['store']->id);
});

it('returns 404 for unknown hostname', function () {
    $response = $this->get('http://nonexistent.test/');

    $response->assertStatus(404);
});

it('returns 503 for suspended store on storefront', function () {
    $organization = Organization::factory()->create();
    $store = Store::factory()->create([
        'organization_id' => $organization->id,
        'status' => StoreStatus::Suspended,
    ]);
    StoreDomain::factory()->create([
        'store_id' => $store->id,
        'hostname' => 'suspended.test',
    ]);

    $response = $this->get('http://suspended.test/');

    $response->assertStatus(503);
});

it('resolves store from session for admin requests', function () {
    $ctx = createStoreContext('admin-test.test');

    $response = $this->actingAs($ctx['user'], 'web')
        ->withSession(['current_store_id' => $ctx['store']->id])
        ->get('/admin');

    $response->assertStatus(200);
});

it('denies admin access when user has no store_users record', function () {
    $ctx = createStoreContext('admin-deny.test');
    $unrelatedUser = User::factory()->create();

    $response = $this->actingAs($unrelatedUser, 'web')
        ->withSession(['current_store_id' => $ctx['store']->id])
        ->get('/admin');

    $response->assertStatus(403);
});

it('caches hostname lookup', function () {
    $ctx = createStoreContext('cache-test.test');

    $this->get('http://cache-test.test/');

    expect(Cache::has('store_domain:cache-test.test'))->toBeTrue();
});
