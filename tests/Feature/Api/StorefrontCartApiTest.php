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

it('creates a cart', function () {
    $response = $this->postJson("{$this->apiBase}/carts");

    $response->assertStatus(201)
        ->assertJsonPath('data.cart_version', 1)
        ->assertJsonStructure(['data' => ['id', 'store_id', 'lines', 'totals']]);
});

it('retrieves a cart with lines and totals', function () {
    $cartService = new CartService;
    $cart = $cartService->create($this->store);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $v1 = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2000]);
    $v2 = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 3000]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $v1->id, 'quantity_on_hand' => 10]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $v2->id, 'quantity_on_hand' => 10]);

    $cartService->addLine($cart, $v1->id, 1);
    $cartService->addLine($cart, $v2->id, 1);

    $response = $this->getJson("{$this->apiBase}/carts/{$cart->id}");

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data.lines')
        ->assertJsonPath('data.totals.subtotal', 5000);
});

it('adds a line to the cart', function () {
    $cartService = new CartService;
    $cart = $cartService->create($this->store);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 1500]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $response = $this->postJson("{$this->apiBase}/carts/{$cart->id}/lines", [
        'variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.lines');
});

it('updates a line quantity', function () {
    $cartService = new CartService;
    $cart = $cartService->create($this->store);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2000]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 20]);

    $line = $cartService->addLine($cart, $variant->id, 1);
    $cart->refresh();

    $response = $this->putJson("{$this->apiBase}/carts/{$cart->id}/lines/{$line->id}", [
        'quantity' => 5,
        'cart_version' => $cart->cart_version,
    ]);

    $response->assertStatus(200);
    expect($response->json('data.lines.0.quantity'))->toBe(5);
});

it('removes a line', function () {
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

it('validates variant exists on add', function () {
    $cartService = new CartService;
    $cart = $cartService->create($this->store);

    $response = $this->postJson("{$this->apiBase}/carts/{$cart->id}/lines", [
        'variant_id' => 999999,
        'quantity' => 1,
    ]);

    $response->assertStatus(422);
});

it('validates quantity is positive', function () {
    $cartService = new CartService;
    $cart = $cartService->create($this->store);

    $response = $this->postJson("{$this->apiBase}/carts/{$cart->id}/lines", [
        'variant_id' => 1,
        'quantity' => 0,
    ]);

    $response->assertStatus(422);
});

it('returns 404 for nonexistent cart', function () {
    $response = $this->getJson("{$this->apiBase}/carts/999999");

    $response->assertStatus(404);
});
