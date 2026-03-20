<?php

use App\Enums\StoreStatus;

it('resolves store from hostname for storefront requests', function () {
    $context = createStoreContext();
    $hostname = $context['domain']->hostname;

    $response = $this->get("http://{$hostname}/account/login");

    $response->assertOk();
});

it('returns 404 for unknown hostname on storefront routes', function () {
    $response = $this->get('http://unknown-store.example.com/account/login');

    $response->assertNotFound();
});

it('returns 503 for suspended store on storefront', function () {
    $context = createStoreContext();
    $context['store']->update(['status' => StoreStatus::Suspended]);

    $response = $this->get("http://{$context['domain']->hostname}/account/login");

    $response->assertServiceUnavailable();
});

it('caches hostname-to-store mapping', function () {
    $context = createStoreContext();
    $hostname = $context['domain']->hostname;

    $this->get("http://{$hostname}/account/login")->assertOk();
    $this->get("http://{$hostname}/account/login")->assertOk();
});

it('renders the admin login page without store resolution', function () {
    $this->get('/admin/login')->assertOk();
});

it('binds current_store to the container after resolution', function () {
    $context = createStoreContext();

    expect(app()->bound('current_store'))->toBeTrue()
        ->and(app('current_store')->id)->toBe($context['store']->id);
});
