<?php

use App\Services\CartService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->baseUrl = 'http://'.$this->context['domain']->hostname.'/api/storefront/v1';
});

it('creates a cart', function () {
    $this->postJson("{$this->baseUrl}/carts")
        ->assertCreated()
        ->assertJsonPath('cart_version', 1)
        ->assertJsonStructure(['id', 'store_id', 'currency', 'cart_version', 'status', 'lines', 'totals']);
});

it('retrieves a cart with lines and totals', function () {
    $variantA = createPurchasableVariant($this->store, 2500);
    $variantB = createPurchasableVariant($this->store, 1000);
    $cartService = app(CartService::class);
    $cart = $cartService->create($this->store);
    $cartService->addLine($cart, $variantA->getKey(), 2);
    $cartService->addLine($cart, $variantB->getKey(), 1);

    $this->getJson("{$this->baseUrl}/carts/{$cart->getKey()}")
        ->assertOk()
        ->assertJsonCount(2, 'lines')
        ->assertJsonPath('totals.subtotal', 6000)
        ->assertJsonPath('totals.total', 6000)
        ->assertJsonPath('totals.item_count', 3);
});

it('adds a line to the cart', function () {
    $variant = createPurchasableVariant($this->store, 2500);
    $cart = app(CartService::class)->create($this->store);

    $this->postJson("{$this->baseUrl}/carts/{$cart->getKey()}/lines", [
        'variant_id' => $variant->getKey(),
        'quantity' => 1,
    ])
        ->assertOk()
        ->assertJsonCount(1, 'lines');
});

it('updates a line quantity', function () {
    $variant = createPurchasableVariant($this->store, 2500);
    $cart = app(CartService::class)->create($this->store);
    $line = app(CartService::class)->addLine($cart, $variant->getKey(), 1);

    $this->putJson("{$this->baseUrl}/carts/{$cart->getKey()}/lines/{$line->getKey()}", [
        'quantity' => 4,
        'cart_version' => $cart->refresh()->cart_version,
    ])
        ->assertOk()
        ->assertJsonPath('lines.0.quantity', 4);
});

it('removes a line', function () {
    $variant = createPurchasableVariant($this->store, 2500);
    $cart = app(CartService::class)->create($this->store);
    $line = app(CartService::class)->addLine($cart, $variant->getKey(), 1);

    $this->deleteJson("{$this->baseUrl}/carts/{$cart->getKey()}/lines/{$line->getKey()}", [
        'cart_version' => $cart->refresh()->cart_version,
    ])
        ->assertOk()
        ->assertJsonCount(0, 'lines');
});

it('validates variant exists on add', function () {
    $cart = app(CartService::class)->create($this->store);

    $this->postJson("{$this->baseUrl}/carts/{$cart->getKey()}/lines", [
        'variant_id' => 999999,
        'quantity' => 1,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('variant_id');
});

it('validates quantity is positive', function () {
    $variant = createPurchasableVariant($this->store, 2500);
    $cart = app(CartService::class)->create($this->store);

    $this->postJson("{$this->baseUrl}/carts/{$cart->getKey()}/lines", [
        'variant_id' => $variant->getKey(),
        'quantity' => 0,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('quantity');
});

it('returns 404 for nonexistent cart', function () {
    $this->getJson("{$this->baseUrl}/carts/999")
        ->assertNotFound()
        ->assertJsonPath('message', 'The requested resource was not found.');
});
