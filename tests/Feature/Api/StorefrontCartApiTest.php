<?php

use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Illuminate\Support\Facades\Cache::flush();
});

it('creates a cart', function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    ['domain' => $domain] = $this->createStoreContext();

    $response = $this->postJson(CommerceTestHelper::storefrontUrl($domain, '/api/storefront/v1/carts'), []);

    $response->assertCreated();
    expect($response->json('id'))->not->toBeNull();
    $response->assertJsonPath('cart_version', 1);
});

it('retrieves a cart with lines and totals', function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store, [
        ['price' => 2500, 'qty' => 1],
        ['price' => 1000, 'qty' => 2],
    ]);

    $response = $this->getJson(CommerceTestHelper::storefrontUrl($domain, "/api/storefront/v1/carts/{$cart->id}"));

    $response->assertOk();
    expect($response->json('lines'))->toHaveCount(2);
    $response->assertJsonPath('totals.subtotal', 4500);
    $response->assertJsonPath('totals.item_count', 3);
});

it('adds a line to the cart', function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store);
    $cart = app(App\Services\CartService::class)->create($store);

    $response = $this->postJson(
        CommerceTestHelper::storefrontUrl($domain, "/api/storefront/v1/carts/{$cart->id}/lines"),
        ['variant_id' => $variant->id, 'quantity' => 1]
    );

    $response->assertCreated();
    expect($response->json('lines'))->toHaveCount(1);
});

it('updates a line quantity', function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);
    $line = $cart->cartLines()->first();

    $response = $this->putJson(
        CommerceTestHelper::storefrontUrl($domain, "/api/storefront/v1/carts/{$cart->id}/lines/{$line->id}"),
        ['quantity' => 5, 'cart_version' => $cart->cart_version]
    );

    $response->assertOk();
    $response->assertJsonPath('lines.0.quantity', 5);
});

it('removes a line', function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);
    $line = $cart->cartLines()->first();

    $response = $this->deleteJson(
        CommerceTestHelper::storefrontUrl($domain, "/api/storefront/v1/carts/{$cart->id}/lines/{$line->id}"),
        ['cart_version' => $cart->cart_version]
    );

    $response->assertOk();
    expect($response->json('lines'))->toHaveCount(0);
});

it('validates variant exists on add', function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $cart = app(App\Services\CartService::class)->create($store);

    $response = $this->postJson(
        CommerceTestHelper::storefrontUrl($domain, "/api/storefront/v1/carts/{$cart->id}/lines"),
        ['variant_id' => 999999, 'quantity' => 1]
    );

    $response->assertUnprocessable();
});

it('validates quantity is positive', function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store);
    $cart = app(App\Services\CartService::class)->create($store);

    $response = $this->postJson(
        CommerceTestHelper::storefrontUrl($domain, "/api/storefront/v1/carts/{$cart->id}/lines"),
        ['variant_id' => $variant->id, 'quantity' => 0]
    );

    $response->assertUnprocessable();
});

it('returns 404 for nonexistent cart', function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    ['domain' => $domain] = $this->createStoreContext();

    $response = $this->getJson(CommerceTestHelper::storefrontUrl($domain, '/api/storefront/v1/carts/999999'));

    $response->assertNotFound();
});

it('respects storefront rate limiting', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $cart = app(App\Services\CartService::class)->create($store);
    $url = CommerceTestHelper::storefrontUrl($domain, "/api/storefront/v1/carts/{$cart->id}");

    for ($i = 0; $i < 120; $i++) {
        $this->getJson($url)->assertOk();
    }

    $this->getJson($url)->assertStatus(429);
});
