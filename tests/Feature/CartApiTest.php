<?php

it('creates and updates a guest cart using its id as the guest access token', function () {
    $store = shopStore();
    $product = shopProduct($store);

    $cartResponse = $this->postJson('http://shop.test/api/storefront/v1/carts', ['currency' => 'EUR'])
        ->assertCreated()
        ->assertJsonPath('store_id', $store->id)
        ->assertJsonPath('cart_version', 1)
        ->assertJsonPath('totals.total', 0);
    $cartId = $cartResponse->json('id');

    $lineResponse = $this->postJson("http://shop.test/api/storefront/v1/carts/{$cartId}/lines", [
        'variant_id' => $product['variant']->id,
        'quantity' => 2,
    ])
        ->assertCreated()
        ->assertJsonPath('cart_version', 2)
        ->assertJsonPath('totals.subtotal', 5000)
        ->assertJsonPath('totals.item_count', 2);

    $lineId = $lineResponse->json('lines.0.id');

    $this->getJson("http://shop.test/api/storefront/v1/carts/{$cartId}")
        ->assertOk()
        ->assertJsonPath('lines.0.variant_id', $product['variant']->id)
        ->assertJsonPath('lines.0.available_quantity', 10);

    $this->putJson("http://shop.test/api/storefront/v1/carts/{$cartId}/lines/{$lineId}", [
        'quantity' => 3,
        'cart_version' => 2,
    ])
        ->assertOk()
        ->assertJsonPath('cart_version', 3)
        ->assertJsonPath('totals.subtotal', 7500);

    $this->assertDatabaseHas('cart_lines', ['cart_id' => $cartId, 'quantity' => 3]);
});

it('rejects a currency code that is not assigned in ISO 4217', function () {
    shopStore();

    $this->postJson('http://shop.test/api/storefront/v1/carts', ['currency' => 'ABC'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('currency');
});

it('returns 409 when a cart mutation uses a stale version', function () {
    $store = shopStore();
    $product = shopProduct($store);
    $cart = \App\Models\Cart::factory()->create(['store_id' => $store->id]);
    $line = $cart->lines()->create([
        'variant_id' => $product['variant']->id,
        'quantity' => 1,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 2500,
        'line_discount_amount' => 0,
        'line_total_amount' => 2500,
    ]);
    $cart->update(['cart_version' => 4]);

    $this->putJson("http://shop.test/api/storefront/v1/carts/{$cart->id}/lines/{$line->id}", [
        'quantity' => 2,
        'cart_version' => 3,
    ])
        ->assertConflict()
        ->assertJsonPath('current_version', 4);

    expect($line->refresh()->quantity)->toBe(1);
});

it('rejects a guest cart from a different tenant', function () {
    $firstStore = shopStore('shop.test');
    $cart = \App\Models\Cart::factory()->create(['store_id' => $firstStore->id]);
    shopStore('second.test');

    $this->getJson("http://second.test/api/storefront/v1/carts/{$cart->id}")
        ->assertNotFound();
});

it('rejects a cart quantity that exceeds available deny-policy stock', function () {
    $store = shopStore();
    $product = shopProduct($store, stock: 2);
    $cart = \App\Models\Cart::factory()->create(['store_id' => $store->id]);

    $this->postJson("http://shop.test/api/storefront/v1/carts/{$cart->id}/lines", [
        'variant_id' => $product['variant']->id,
        'quantity' => 3,
    ])
        ->assertUnprocessable()
        ->assertJsonPath('error_code', 'insufficient_inventory');

    expect($cart->lines()->count())->toBe(0);
});
