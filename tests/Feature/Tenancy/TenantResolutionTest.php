<?php

use App\Enums\StoreStatus;
use Illuminate\Support\Facades\Cache;

it('resolves store from hostname for storefront requests', function (): void {
    $context = $this->createStoreContext(['hostname' => 'acme-fashion.test']);

    $response = $this->get('http://acme-fashion.test/');

    $response->assertOk();
    expect(app('current_store')->id)->toBe($context['store']->id);
});

it('returns 404 for unknown hostname', function (): void {
    $response = $this->get('http://nonexistent.test/');
    $response->assertNotFound();
});

it('returns 503 for suspended store on storefront', function (): void {
    $context = $this->createStoreContext(['hostname' => 'suspended-store.test']);
    $context['store']->update(['status' => StoreStatus::Suspended]);
    Cache::forget('store_domain:suspended-store.test');

    $response = $this->get('http://suspended-store.test/');

    $response->assertStatus(503);
});

it('caches hostname lookup', function (): void {
    $this->createStoreContext(['hostname' => 'cache-store.test']);

    expect(Cache::has('store_domain:cache-store.test'))->toBeFalse();

    $this->get('http://cache-store.test/')->assertOk();

    expect(Cache::has('store_domain:cache-store.test'))->toBeTrue();
});
