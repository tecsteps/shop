<?php

use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

it('resolves store from hostname for storefront requests', function () {
    $context = createStoreContext();
    app()->forgetInstance('current_store');

    $response = $this->get('http://'.$context['domain']->hostname.'/');

    $response->assertOk();
    expect(app()->bound('current_store'))->toBeTrue();
    expect(app('current_store')->getKey())->toBe($context['store']->getKey());
});

it('returns 404 for unknown hostname', function () {
    $this->get('http://nonexistent.test/')->assertNotFound();
});

it('returns 503 for suspended store on storefront', function () {
    $store = Store::factory()->suspended()->create();
    $domain = StoreDomain::factory()->for($store)->create();

    $this->get('http://'.$domain->hostname.'/')->assertServiceUnavailable();
});

it('resolves store from session for admin requests', function () {
    $context = createStoreContext();
    app()->forgetInstance('current_store');

    $response = actingAsAdmin($context['user'], $context['store'])->get('/admin');

    $response->assertOk();
    expect(app('current_store')->getKey())->toBe($context['store']->getKey());
});

it('denies admin access when user has no store_users record', function () {
    $context = createStoreContext();
    app()->forgetInstance('current_store');

    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->withSession(['current_store_id' => $context['store']->getKey()])
        ->get('/admin')
        ->assertForbidden();
});

it('caches hostname lookup', function () {
    $context = createStoreContext();
    app()->forgetInstance('current_store');

    $hostname = $context['domain']->hostname;

    expect(Cache::has("store_domain:{$hostname}"))->toBeFalse();

    $this->get('http://'.$hostname.'/')->assertOk();

    expect(Cache::has("store_domain:{$hostname}"))->toBeTrue();
    expect(Cache::get("store_domain:{$hostname}"))->toBe($context['store']->getKey());
});
