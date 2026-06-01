<?php

use App\Enums\StoreStatus;
use App\Models\Store;
use Illuminate\Support\Facades\Cache;

it('resolves store from hostname for storefront requests', function () {
    $context = createStoreContext(['hostname' => 'acme-fashion.test', 'bind' => false]);

    $response = $this->get(storefrontUrl('acme-fashion.test'));

    $response->assertOk();
    expect(app('current_store')->id)->toBe($context['store']->id);
});

it('returns 404 for unknown hostname', function () {
    $this->get(storefrontUrl('nonexistent.test'))
        ->assertNotFound();
});

it('returns 503 for suspended store on storefront', function () {
    createStoreContext([
        'hostname' => 'suspended.test',
        'status' => StoreStatus::Suspended,
        'bind' => false,
    ]);

    $this->get(storefrontUrl('suspended.test'))
        ->assertStatus(503);
});

it('resolves store from session for admin requests', function () {
    $context = createStoreContext(['bind' => false]);

    actingAsAdmin($context['owner'], $context['store']);

    $this->get('/admin')->assertOk();
    expect(app('current_store')->id)->toBe($context['store']->id);
});

it('denies admin access when user has no store_users record', function () {
    $context = createStoreContext(['bind' => false]);
    $otherStore = createStoreContext(['hostname' => 'other.test', 'bind' => false]);

    // Owner of the first store, but session points at a store they cannot access.
    $this->actingAs($context['owner'], 'web');
    session()->put('current_store_id', $otherStore['store']->id);

    $this->get('/admin')->assertForbidden();
});

it('caches hostname lookup', function () {
    $context = createStoreContext(['hostname' => 'cached.test', 'bind' => false]);

    expect(Cache::has('store_domain:cached.test'))->toBeFalse();

    $this->get(storefrontUrl('cached.test'))->assertOk();

    expect(Cache::get('store_domain:cached.test'))->toBe($context['store']->id);
});
