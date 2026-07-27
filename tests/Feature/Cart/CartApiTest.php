<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\CartService;

/**
 * Build the absolute storefront API URL for the store's primary domain.
 */
function cartApiUrl(Store $store, string $path): string
{
    return 'http://'.$store->handle.'.test/api/storefront/v1'.$path;
}

/**
 * Create a store with a purchasable variant.
 *
 * @return array{0: Store, 1: ProductVariant}
 */
function cartApiSetup(): array
{
    $store = test()->createStore();
    test()->bindStore($store);

    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->withInventory(50)->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
        'sku' => 'TSH-BLU-M',
    ]);

    return [$store, $variant];
}

test('creates a cart via API', function () {
    [$store] = cartApiSetup();

    $response = $this->postJson(cartApiUrl($store, '/carts'), ['currency' => 'EUR']);

    $response->assertCreated()
        ->assertJsonPath('currency', 'EUR')
        ->assertJsonPath('cart_version', 1)
        ->assertJsonPath('status', 'active')
        ->assertJsonPath('lines', [])
        ->assertJsonPath('totals.subtotal', 0)
        ->assertJsonPath('totals.total', 0)
        ->assertJsonStructure(['id', 'store_id', 'customer_id', 'created_at', 'updated_at']);
});

test('retrieves a cart via API', function () {
    [$store, $variant] = cartApiSetup();
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, 2);

    $response = $this->getJson(cartApiUrl($store, "/carts/{$cart->id}"));

    $response->assertOk()
        ->assertJsonPath('id', $cart->id)
        ->assertJsonPath('cart_version', 2)
        ->assertJsonPath('totals.subtotal', 5000)
        ->assertJsonPath('totals.total', 5000)
        ->assertJsonPath('totals.line_count', 1)
        ->assertJsonPath('totals.item_count', 2)
        ->assertJsonPath('lines.0.variant_id', $variant->id)
        ->assertJsonPath('lines.0.quantity', 2)
        ->assertJsonPath('lines.0.unit_price_amount', 2500)
        ->assertJsonPath('lines.0.sku', 'TSH-BLU-M')
        ->assertJsonStructure(['lines' => [['product_title', 'variant_title', 'available_quantity', 'requires_shipping', 'image_url']]]);
});

test('adds a line via API', function () {
    [$store, $variant] = cartApiSetup();
    $cart = app(CartService::class)->create($store);

    // spec 02 §2.1 defines 201 Created for this endpoint.
    $response = $this->postJson(cartApiUrl($store, "/carts/{$cart->id}/lines"), [
        'variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    $response->assertCreated()
        ->assertJsonPath('lines.0.variant_id', $variant->id)
        ->assertJsonPath('lines.0.quantity', 2)
        ->assertJsonPath('totals.subtotal', 5000)
        ->assertJsonPath('cart_version', 2);
});

test('updates line quantity via API', function () {
    [$store, $variant] = cartApiSetup();
    $service = app(CartService::class);
    $cart = $service->create($store);
    $line = $service->addLine($cart, $variant->id, 1);

    $response = $this->putJson(cartApiUrl($store, "/carts/{$cart->id}/lines/{$line->id}"), [
        'quantity' => 3,
        'cart_version' => 2,
    ]);

    $response->assertOk()
        ->assertJsonPath('lines.0.quantity', 3)
        ->assertJsonPath('totals.subtotal', 7500)
        ->assertJsonPath('cart_version', 3);
});

test('removes a line via API', function () {
    [$store, $variant] = cartApiSetup();
    $service = app(CartService::class);
    $cart = $service->create($store);
    $line = $service->addLine($cart, $variant->id, 1);

    $response = $this->deleteJson(cartApiUrl($store, "/carts/{$cart->id}/lines/{$line->id}"), [
        'cart_version' => 2,
    ]);

    $response->assertOk()
        ->assertJsonPath('lines', [])
        ->assertJsonPath('totals.subtotal', 0)
        ->assertJsonPath('cart_version', 3);
});

test('returns 404 for nonexistent cart', function () {
    [$store] = cartApiSetup();

    $this->getJson(cartApiUrl($store, '/carts/999999'))->assertNotFound();
});

test('returns 409 on version mismatch', function () {
    [$store, $variant] = cartApiSetup();
    $service = app(CartService::class);
    $cart = $service->create($store);
    $line = $service->addLine($cart, $variant->id, 1);
    $service->addLine($cart->refresh(), $variant->id, 1); // version 3

    $response = $this->putJson(cartApiUrl($store, "/carts/{$cart->id}/lines/{$line->id}"), [
        'quantity' => 5,
        'cart_version' => 2,
    ]);

    $response->assertConflict()
        ->assertJsonPath('cart_version', 3)
        ->assertJsonPath('id', $cart->id)
        ->assertJsonStructure(['message', 'lines', 'totals']);

    // The stale write must not have been applied.
    expect($line->refresh()->quantity)->toBe(2);
});

test('returns 422 with field errors when the variant is out of stock', function () {
    [$store, $variant] = cartApiSetup();
    $variant->inventoryItem->update(['quantity_on_hand' => 1]);
    $cart = app(CartService::class)->create($store);

    $response = $this->postJson(cartApiUrl($store, "/carts/{$cart->id}/lines"), [
        'variant_id' => $variant->id,
        'quantity' => 5,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['variant_id']);
});

test('respects storefront rate limiting', function () {
    [$store] = cartApiSetup();
    $cart = app(CartService::class)->create($store);

    for ($i = 0; $i < 120; $i++) {
        $this->getJson(cartApiUrl($store, "/carts/{$cart->id}"))->assertOk();
    }

    $this->getJson(cartApiUrl($store, "/carts/{$cart->id}"))->assertTooManyRequests();
});
