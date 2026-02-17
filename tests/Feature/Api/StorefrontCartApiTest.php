<?php

use App\Enums\InventoryPolicy;
use App\Enums\VariantStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;

beforeEach(function () {
    $this->ctx = createStoreContext('shop.test');
    $this->store = $this->ctx['store'];
    $this->product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $this->variant = ProductVariant::factory()->default()->create([
        'product_id' => $this->product->id,
        'price_amount' => 2500,
        'status' => VariantStatus::Active,
    ]);
    InventoryItem::factory()->withStock(50)->create([
        'store_id' => $this->store->id,
        'variant_id' => $this->variant->id,
        'policy' => InventoryPolicy::Deny,
    ]);
});

it('creates a cart', function () {
    $response = $this->withHeaders(['Host' => 'shop.test'])
        ->postJson('/api/storefront/v1/carts');

    $response->assertStatus(201)
        ->assertJsonPath('cart_version', 1)
        ->assertJsonPath('status', 'active')
        ->assertJsonStructure(['id', 'store_id', 'cart_version', 'status', 'lines', 'totals']);
});

it('retrieves a cart with lines and totals', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);

    $variant2 = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'price_amount' => 1500,
        'status' => VariantStatus::Active,
    ]);
    InventoryItem::factory()->withStock(10)->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant2->id,
    ]);

    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $this->variant->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 5000,
        'line_discount_amount' => 0,
        'line_total_amount' => 5000,
    ]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant2->id,
        'quantity' => 1,
        'unit_price_amount' => 1500,
        'line_subtotal_amount' => 1500,
        'line_discount_amount' => 0,
        'line_total_amount' => 1500,
    ]);

    $response = $this->withHeaders(['Host' => 'shop.test'])
        ->getJson("/api/storefront/v1/carts/{$cart->id}");

    $response->assertOk()
        ->assertJsonCount(2, 'lines')
        ->assertJsonPath('totals.subtotal', 6500)
        ->assertJsonPath('totals.total', 6500)
        ->assertJsonPath('totals.line_count', 2)
        ->assertJsonPath('totals.item_count', 3);
});

it('adds a line to the cart', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);

    $response = $this->withHeaders(['Host' => 'shop.test'])
        ->postJson("/api/storefront/v1/carts/{$cart->id}/lines", [
            'variant_id' => $this->variant->id,
            'quantity' => 2,
        ]);

    $response->assertStatus(201)
        ->assertJsonCount(1, 'lines')
        ->assertJsonPath('lines.0.variant_id', $this->variant->id)
        ->assertJsonPath('lines.0.quantity', 2);
});

it('updates a line quantity', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $line = CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $this->variant->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 5000,
        'line_discount_amount' => 0,
        'line_total_amount' => 5000,
    ]);

    $response = $this->withHeaders(['Host' => 'shop.test'])
        ->putJson("/api/storefront/v1/carts/{$cart->id}/lines/{$line->id}", [
            'quantity' => 3,
            'cart_version' => $cart->cart_version,
        ]);

    $response->assertOk()
        ->assertJsonPath('lines.0.quantity', 3);
});

it('removes a line', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $line = CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $this->variant->id,
        'quantity' => 1,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 2500,
        'line_discount_amount' => 0,
        'line_total_amount' => 2500,
    ]);

    $response = $this->withHeaders(['Host' => 'shop.test'])
        ->deleteJson("/api/storefront/v1/carts/{$cart->id}/lines/{$line->id}");

    $response->assertOk()
        ->assertJsonCount(0, 'lines');
});

it('validates variant exists on add', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);

    $response = $this->withHeaders(['Host' => 'shop.test'])
        ->postJson("/api/storefront/v1/carts/{$cart->id}/lines", [
            'variant_id' => 99999,
            'quantity' => 1,
        ]);

    $response->assertStatus(422);
});

it('validates quantity is positive', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);

    $response = $this->withHeaders(['Host' => 'shop.test'])
        ->postJson("/api/storefront/v1/carts/{$cart->id}/lines", [
            'variant_id' => $this->variant->id,
            'quantity' => 0,
        ]);

    $response->assertStatus(422);
});

it('returns 404 for nonexistent cart', function () {
    $response = $this->withHeaders(['Host' => 'shop.test'])
        ->getJson('/api/storefront/v1/carts/999');

    $response->assertNotFound();
});
