<?php

use App\Services\CartService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->baseUrl = 'http://'.$this->context['domain']->hostname.'/api/storefront/v1';
});

it('creates a cart via API', function () {
    $this->postJson("{$this->baseUrl}/carts")
        ->assertCreated()
        ->assertJsonPath('store_id', $this->store->getKey())
        ->assertJsonPath('cart_version', 1)
        ->assertJsonPath('status', 'active');
});

it('retrieves a cart via API', function () {
    $variant = createPurchasableVariant($this->store, 2500);
    $cart = app(CartService::class)->create($this->store);
    app(CartService::class)->addLine($cart, $variant->getKey(), 2);

    $this->getJson("{$this->baseUrl}/carts/{$cart->getKey()}")
        ->assertOk()
        ->assertJsonCount(1, 'lines')
        ->assertJsonPath('totals.subtotal', 5000)
        ->assertJsonPath('totals.total', 5000);
});

it('adds a line via API', function () {
    $variant = createPurchasableVariant($this->store, 2500);
    $cart = app(CartService::class)->create($this->store);

    $this->postJson("{$this->baseUrl}/carts/{$cart->getKey()}/lines", [
        'variant_id' => $variant->getKey(),
        'quantity' => 2,
    ])->assertOk();

    $this->assertDatabaseHas('cart_lines', [
        'cart_id' => $cart->getKey(),
        'variant_id' => $variant->getKey(),
        'quantity' => 2,
    ]);
});

it('updates line quantity via API', function () {
    $variant = createPurchasableVariant($this->store, 2500);
    $cart = app(CartService::class)->create($this->store);
    $line = app(CartService::class)->addLine($cart, $variant->getKey(), 1);

    $this->putJson("{$this->baseUrl}/carts/{$cart->getKey()}/lines/{$line->getKey()}", [
        'quantity' => 3,
        'cart_version' => $cart->refresh()->cart_version,
    ])->assertOk();

    expect($line->refresh()->quantity)->toBe(3);
});

it('removes a line via API', function () {
    $variant = createPurchasableVariant($this->store, 2500);
    $cart = app(CartService::class)->create($this->store);
    $line = app(CartService::class)->addLine($cart, $variant->getKey(), 1);

    $this->deleteJson("{$this->baseUrl}/carts/{$cart->getKey()}/lines/{$line->getKey()}", [
        'cart_version' => $cart->refresh()->cart_version,
    ])->assertOk();

    $this->assertDatabaseMissing('cart_lines', ['id' => $line->getKey()]);
});

it('returns 404 for nonexistent cart', function () {
    $this->getJson("{$this->baseUrl}/carts/999")
        ->assertNotFound();
});

it('returns 409 on version mismatch', function () {
    $variant = createPurchasableVariant($this->store, 2500);
    $cartService = app(CartService::class);
    $cart = $cartService->create($this->store);
    $line = $cartService->addLine($cart, $variant->getKey(), 1);
    $cartService->updateLineQuantity($cart, $line->getKey(), 2);

    expect($cart->refresh()->cart_version)->toBe(3);

    $this->putJson("{$this->baseUrl}/carts/{$cart->getKey()}/lines/{$line->getKey()}", [
        'quantity' => 5,
        'expected_version' => 2,
    ])
        ->assertConflict()
        ->assertJsonPath('error_code', 'version_conflict')
        ->assertJsonPath('current_version', 3)
        ->assertJsonPath('cart.cart_version', 3);
});

it('respects storefront rate limiting', function () {
    $cart = app(CartService::class)->create($this->store);

    foreach (range(1, 120) as $i) {
        $this->getJson("{$this->baseUrl}/carts/{$cart->getKey()}")->assertOk();
    }

    $this->getJson("{$this->baseUrl}/carts/{$cart->getKey()}")
        ->assertStatus(429)
        ->assertJsonPath('message', 'Too many requests. Please try again later.');
});
