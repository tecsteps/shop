<?php

use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->domain = $this->context['domain'];
    $this->apiBase = "http://{$this->domain->hostname}/api/storefront/v1";
});

it('creates a cart via API', function () {
    $response = $this->postJson("{$this->apiBase}/carts");

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'store_id', 'cart_version', 'status', 'lines', 'totals']]);

    expect($response->json('data.cart_version'))->toBe(1);
});

it('retrieves a cart via API', function () {
    $cartService = new CartService;
    $cart = $cartService->create($this->store);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 50]);

    $cartService->addLine($cart, $variant->id, 2);

    $response = $this->getJson("{$this->apiBase}/carts/{$cart->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.totals.subtotal', 5000)
        ->assertJsonCount(1, 'data.lines');
});

it('adds a line via API', function () {
    $cartService = new CartService;
    $cart = $cartService->create($this->store);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 1500]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $response = $this->postJson("{$this->apiBase}/carts/{$cart->id}/lines", [
        'variant_id' => $variant->id,
        'quantity' => 3,
    ]);

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.lines');
});

it('updates line quantity via API', function () {
    $cartService = new CartService;
    $cart = $cartService->create($this->store);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2000]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 20]);

    $line = $cartService->addLine($cart, $variant->id, 2);
    $cart->refresh();

    $response = $this->putJson("{$this->apiBase}/carts/{$cart->id}/lines/{$line->id}", [
        'quantity' => 5,
        'cart_version' => $cart->cart_version,
    ]);

    $response->assertStatus(200);
    expect($response->json('data.lines.0.quantity'))->toBe(5);
});

it('removes a line via API', function () {
    $cartService = new CartService;
    $cart = $cartService->create($this->store);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2000]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 20]);

    $line = $cartService->addLine($cart, $variant->id, 2);
    $cart->refresh();

    $response = $this->deleteJson("{$this->apiBase}/carts/{$cart->id}/lines/{$line->id}", [
        'cart_version' => $cart->cart_version,
    ]);

    $response->assertStatus(200)
        ->assertJsonCount(0, 'data.lines');
});

it('returns 404 for nonexistent cart', function () {
    $response = $this->getJson("{$this->apiBase}/carts/999999");

    $response->assertStatus(404);
});

it('returns 409 on version mismatch', function () {
    $cartService = new CartService;
    $cart = $cartService->create($this->store);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2000]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 20]);

    $line = $cartService->addLine($cart, $variant->id, 2);

    $response = $this->putJson("{$this->apiBase}/carts/{$cart->id}/lines/{$line->id}", [
        'quantity' => 5,
        'cart_version' => 999,
    ]);

    $response->assertStatus(409);
});

it('validates variant exists on add', function () {
    $cartService = new CartService;
    $cart = $cartService->create($this->store);

    $response = $this->postJson("{$this->apiBase}/carts/{$cart->id}/lines", [
        'variant_id' => 999999,
        'quantity' => 1,
    ]);

    $response->assertStatus(422);
});
