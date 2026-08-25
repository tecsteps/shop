<?php

use App\Models\Cart;
use App\Services\ProductService;

it('creates a cart via API', function () {
    createStoreContext();

    $response = $this->postJson('/api/storefront/v1/carts');

    $response->assertStatus(201);
    $response->assertJsonPath('cart_version', 1);
    expect($response->json('id'))->toBeInt();
});

it('retrieves a cart via API', function () {
    $ctx = createStoreContext();
    $cart = Cart::factory()->create(['store_id' => $ctx['store']->id]);

    $response = $this->getJson('/api/storefront/v1/carts/'.$cart->id);

    $response->assertStatus(200);
    expect($response->json('id'))->toBe($cart->id);
});

it('adds a line via API', function () {
    $ctx = createStoreContext();
    $product = app(ProductService::class)->create($ctx['store'], ['title' => 'Shirt', 'price_amount' => 2500, 'quantity_on_hand' => 10]);
    app(ProductService::class)->transitionStatus($product, \App\Enums\ProductStatus::Active);
    $cart = Cart::factory()->create(['store_id' => $ctx['store']->id]);

    $response = $this->postJson('/api/storefront/v1/carts/'.$cart->id.'/lines', [
        'variant_id' => $product->variants()->first()->id,
        'quantity' => 2,
    ]);

    $response->assertStatus(201);
    expect(count($response->json('lines')))->toBe(1);
});

it('returns 404 for nonexistent cart', function () {
    createStoreContext();

    $this->getJson('/api/storefront/v1/carts/99999')->assertStatus(404);
});

it('returns 409 on version mismatch', function () {
    $ctx = createStoreContext();
    $cart = Cart::factory()->create(['store_id' => $ctx['store']->id]);
    $cart->increment('cart_version');
    $cart->increment('cart_version');

    $this->putJson('/api/storefront/v1/carts/'.$cart->id.'/lines/999', [
        'quantity' => 2,
        'cart_version' => 1,
    ])->assertStatus(409);
});

it('validates quantity is positive', function () {
    $ctx = createStoreContext();
    $product = app(ProductService::class)->create($ctx['store'], ['title' => 'Shirt', 'price_amount' => 2500, 'quantity_on_hand' => 10]);
    app(ProductService::class)->transitionStatus($product, \App\Enums\ProductStatus::Active);
    $cart = Cart::factory()->create(['store_id' => $ctx['store']->id]);

    $this->postJson('/api/storefront/v1/carts/'.$cart->id.'/lines', [
        'variant_id' => $product->variants()->first()->id,
        'quantity' => 0,
    ])->assertStatus(422);
});
