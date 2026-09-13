<?php

use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    Illuminate\Support\Facades\Cache::flush();
});

it('creates a cart via API', function () {
    ['domain' => $domain] = $this->createStoreContext();

    $response = $this->postJson(CommerceTestHelper::storefrontUrl($domain, '/api/storefront/v1/carts'), []);

    $response->assertCreated();
    $response->assertJsonPath('cart_version', 1);
    $response->assertJsonPath('status', 'active');
    expect($response->json('id'))->not->toBeNull();
});

it('retrieves a cart via API', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store, ['price_amount' => 2500]);

    $cart = app(App\Services\CartService::class)->create($store);
    app(App\Services\CartService::class)->addLine($cart, $variant->id, 2);

    $response = $this->getJson(CommerceTestHelper::storefrontUrl($domain, "/api/storefront/v1/carts/{$cart->id}"));

    $response->assertOk();
    $response->assertJsonPath('totals.subtotal', 5000);
    $response->assertJsonPath('totals.item_count', 2);
    expect($response->json('lines'))->toHaveCount(1);
});

it('adds a line via API', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store);

    $cart = app(App\Services\CartService::class)->create($store);

    $response = $this->postJson(
        CommerceTestHelper::storefrontUrl($domain, "/api/storefront/v1/carts/{$cart->id}/lines"),
        ['variant_id' => $variant->id, 'quantity' => 2]
    );

    $response->assertCreated();
    $response->assertJsonPath('cart_version', 2);
    expect($response->json('lines'))->toHaveCount(1);
});

it('updates line quantity via API', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store);

    $cart = app(App\Services\CartService::class)->create($store);
    $line = app(App\Services\CartService::class)->addLine($cart, $variant->id, 1);

    $response = $this->putJson(
        CommerceTestHelper::storefrontUrl($domain, "/api/storefront/v1/carts/{$cart->id}/lines/{$line->id}"),
        ['quantity' => 4, 'cart_version' => 2]
    );

    $response->assertOk();
    $response->assertJsonPath('lines.0.quantity', 4);
    $response->assertJsonPath('cart_version', 3);
});

it('removes a line via API', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store);

    $cart = app(App\Services\CartService::class)->create($store);
    $line = app(App\Services\CartService::class)->addLine($cart, $variant->id, 1);

    $response = $this->deleteJson(
        CommerceTestHelper::storefrontUrl($domain, "/api/storefront/v1/carts/{$cart->id}/lines/{$line->id}"),
        ['cart_version' => 2]
    );

    $response->assertOk();
    expect($response->json('lines'))->toHaveCount(0);
});

it('returns 404 for nonexistent cart', function () {
    ['domain' => $domain] = $this->createStoreContext();

    $response = $this->getJson(CommerceTestHelper::storefrontUrl($domain, '/api/storefront/v1/carts/999999'));

    $response->assertNotFound();
});

it('returns 409 on version mismatch', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store);

    $cart = app(App\Services\CartService::class)->create($store);
    $line = app(App\Services\CartService::class)->addLine($cart, $variant->id, 1);

    $response = $this->putJson(
        CommerceTestHelper::storefrontUrl($domain, "/api/storefront/v1/carts/{$cart->id}/lines/{$line->id}"),
        ['quantity' => 2, 'cart_version' => 1]
    );

    $response->assertConflict();
    $response->assertJsonPath('error_code', 'version_conflict');
    $response->assertJsonPath('current_version', 2);
});

it('isolates carts across stores', function () {
    ['store' => $storeA] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($storeA);
    $cart = app(App\Services\CartService::class)->create($storeA);
    app(App\Services\CartService::class)->addLine($cart, $variant->id, 1);

    ['domain' => $domainB] = $this->createStoreContext();

    $response = $this->getJson(CommerceTestHelper::storefrontUrl($domainB, "/api/storefront/v1/carts/{$cart->id}"));

    $response->assertNotFound();
});
