<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreDomain;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    StoreDomain::factory()->create([
        'store_id' => $this->store->id,
        'hostname' => 'shop.test',
    ]);
});

test('can create a cart', function () {
    $response = $this->postJson(
        'http://shop.test/api/storefront/v1/carts',
        [],
    );

    $response->assertCreated()
        ->assertJsonStructure([
            'id', 'store_id', 'currency', 'cart_version', 'status', 'lines', 'totals',
        ]);

    expect($response->json('store_id'))->toBe($this->store->id)
        ->and($response->json('status'))->toBe('active')
        ->and($response->json('cart_version'))->toBe(1)
        ->and($response->json('lines'))->toBeEmpty();
});

test('can get a cart with lines', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'product_title' => 'Test Product',
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 5000,
        'line_total_amount' => 5000,
    ]);

    $response = $this->getJson("http://shop.test/api/storefront/v1/carts/{$cart->id}");

    $response->assertOk()
        ->assertJsonCount(1, 'lines')
        ->assertJsonPath('totals.item_count', 2)
        ->assertJsonPath('totals.subtotal', 5000);
});

test('can add a line to a cart', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 1500,
        'status' => 'active',
    ]);
    InventoryItem::factory()->create([
        'variant_id' => $variant->id,
        'quantity_on_hand' => 100,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    $response = $this->postJson(
        "http://shop.test/api/storefront/v1/carts/{$cart->id}/lines",
        ['variant_id' => $variant->id, 'quantity' => 3],
    );

    $response->assertCreated()
        ->assertJsonCount(1, 'lines')
        ->assertJsonPath('lines.0.quantity', 3)
        ->assertJsonPath('lines.0.unit_price_amount', 1500);
});

test('adding a line validates variant exists', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);

    $response = $this->postJson(
        "http://shop.test/api/storefront/v1/carts/{$cart->id}/lines",
        ['variant_id' => 99999, 'quantity' => 1],
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('variant_id');
});

test('can update line quantity with version check', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id, 'cart_version' => 2]);
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 1000]);
    $line = CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'line_subtotal_amount' => 1000,
        'line_total_amount' => 1000,
    ]);

    $response = $this->putJson(
        "http://shop.test/api/storefront/v1/carts/{$cart->id}/lines/{$line->id}",
        ['quantity' => 5, 'cart_version' => 2],
    );

    $response->assertOk();
    expect($line->fresh()->quantity)->toBe(5);
});

test('updating line with wrong version returns 409', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id, 'cart_version' => 3]);
    $line = CartLine::factory()->create(['cart_id' => $cart->id]);

    $response = $this->putJson(
        "http://shop.test/api/storefront/v1/carts/{$cart->id}/lines/{$line->id}",
        ['quantity' => 2, 'cart_version' => 1],
    );

    $response->assertStatus(409);
});

test('can remove a line from cart', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id, 'cart_version' => 1]);
    $line = CartLine::factory()->create(['cart_id' => $cart->id]);

    $response = $this->deleteJson(
        "http://shop.test/api/storefront/v1/carts/{$cart->id}/lines/{$line->id}",
        ['cart_version' => 1],
    );

    $response->assertOk()
        ->assertJsonCount(0, 'lines');
});
