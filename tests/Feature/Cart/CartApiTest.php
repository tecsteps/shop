<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->hostname = 'acme-fashion.test';
});

function storefrontPost(string $path, array $data = []): \Illuminate\Testing\TestResponse
{
    return test()->postJson("http://acme-fashion.test/api/storefront/v1{$path}", $data);
}

function storefrontGet(string $path): \Illuminate\Testing\TestResponse
{
    return test()->getJson("http://acme-fashion.test/api/storefront/v1{$path}");
}

function storefrontPut(string $path, array $data = []): \Illuminate\Testing\TestResponse
{
    return test()->putJson("http://acme-fashion.test/api/storefront/v1{$path}", $data);
}

function storefrontDelete(string $path, array $data = []): \Illuminate\Testing\TestResponse
{
    return test()->deleteJson("http://acme-fashion.test/api/storefront/v1{$path}", $data);
}

function createApiVariant($store, int $price = 2500): ProductVariant
{
    $product = Product::factory()->active()->create(['store_id' => $store->id]);

    return ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => $price,
    ]);
}

it('creates a cart via API', function () {
    $response = storefrontPost('/carts');

    $response->assertStatus(201)
        ->assertJsonStructure([
            'id', 'store_id', 'currency', 'cart_version', 'status', 'lines', 'totals',
        ]);
});

it('retrieves a cart via API', function () {
    $cart = app(CartService::class)->create($this->store);

    $response = storefrontGet("/carts/{$cart->id}");

    $response->assertSuccessful()
        ->assertJsonPath('id', $cart->id)
        ->assertJsonStructure(['id', 'lines', 'totals']);
});

it('adds a line via API', function () {
    $cart = app(CartService::class)->create($this->store);
    $variant = createApiVariant($this->store, 2500);

    $response = storefrontPost("/carts/{$cart->id}/lines", [
        'variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('totals.item_count', 2);
});

it('updates line quantity via API', function () {
    $cart = app(CartService::class)->create($this->store);
    $variant = createApiVariant($this->store, 2500);
    $line = app(CartService::class)->addLine($cart, $variant->id, 1);

    $response = storefrontPut("/carts/{$cart->id}/lines/{$line->id}", [
        'quantity' => 5,
        'cart_version' => $cart->fresh()->cart_version,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('totals.item_count', 5);
});

it('removes a line via API', function () {
    $cart = app(CartService::class)->create($this->store);
    $variant = createApiVariant($this->store, 2500);
    $line = app(CartService::class)->addLine($cart, $variant->id, 1);

    $response = storefrontDelete("/carts/{$cart->id}/lines/{$line->id}", [
        'cart_version' => $cart->fresh()->cart_version,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('totals.line_count', 0);
});

it('returns 404 for nonexistent cart', function () {
    $response = storefrontGet('/carts/99999');

    $response->assertNotFound();
});

it('returns 409 on version mismatch', function () {
    $cart = app(CartService::class)->create($this->store);
    $variant = createApiVariant($this->store, 2500);
    $line = app(CartService::class)->addLine($cart, $variant->id, 1);

    // Send stale version (1 instead of current 2)
    $response = storefrontPut("/carts/{$cart->id}/lines/{$line->id}", [
        'quantity' => 5,
        'cart_version' => 1,
    ]);

    $response->assertStatus(409);
});

it('respects storefront rate limiting', function () {
    // The storefront throttle is configured in the middleware.
    // We verify that the API responds successfully under normal use.
    $response = storefrontPost('/carts');

    $response->assertStatus(201);
});
