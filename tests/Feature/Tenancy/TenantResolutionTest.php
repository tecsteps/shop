<?php

use App\Enums\StoreStatus;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

it('resolves store from hostname for storefront requests', function () {
    $context = createStoreContext();

    $response = $this->get('http://acme-fashion.test/');

    $response->assertOk();
});

it('returns 404 for unknown hostname', function () {
    $response = $this->get('http://nonexistent.test/');

    $response->assertNotFound();
});

it('returns 503 for suspended store on storefront', function () {
    $context = createStoreContext();
    $context['store']->update(['status' => StoreStatus::Suspended]);

    $response = $this->get('http://acme-fashion.test/');

    $response->assertServiceUnavailable();
});

it('resolves store from session for admin requests', function () {
    $context = createStoreContext();

    $response = $this->actingAs($context['user'])
        ->withSession(['current_store_id' => $context['store']->id])
        ->get('/admin');

    $response->assertOk();
});

it('denies admin access when user has no store_users record', function () {
    $context = createStoreContext();
    $unrelatedUser = User::factory()->create();

    $response = $this->actingAs($unrelatedUser)
        ->withSession(['current_store_id' => $context['store']->id])
        ->get('/admin');

    $response->assertForbidden();
});

it('caches hostname lookup', function () {
    $context = createStoreContext();

    $this->get('http://acme-fashion.test/');

    expect(Cache::has('store_domain:acme-fashion.test'))->toBeTrue();
});
