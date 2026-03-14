<?php

use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Product;
use App\Models\ProductVariant;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $context = createStoreContext();
    $this->store = $context['store'];
    $this->domain = $context['domain'];
});

it('creates a new cart', function () {
    $response = $this->postJson(
        'http://'.$this->domain->hostname.'/api/storefront/v1/carts',
    );

    $response->assertStatus(201)
        ->assertJsonPath('id', fn ($id) => $id > 0)
        ->assertJsonPath('store_id', $this->store->id)
        ->assertJsonPath('status', 'active')
        ->assertJsonPath('totals.total', 0);
});

it('shows a cart with lines', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
    ]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 5000,
        'line_discount_amount' => 0,
        'line_total_amount' => 5000,
    ]);

    $response = $this->getJson(
        'http://'.$this->domain->hostname.'/api/storefront/v1/carts/'.$cart->id,
    );

    $response->assertOk()
        ->assertJsonPath('id', $cart->id)
        ->assertJsonPath('totals.total', 5000)
        ->assertJsonPath('totals.item_count', 2)
        ->assertJsonCount(1, 'lines');
});

it('adds a line to cart', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 1500,
        'status' => VariantStatus::Active,
    ]);

    $response = $this->postJson(
        'http://'.$this->domain->hostname.'/api/storefront/v1/carts/'.$cart->id.'/lines',
        ['variant_id' => $variant->id, 'quantity' => 3],
    );

    $response->assertStatus(201)
        ->assertJsonPath('totals.total', 4500)
        ->assertJsonCount(1, 'lines');
});

it('rejects adding inactive product to cart', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => ProductStatus::Draft,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'status' => VariantStatus::Active,
    ]);

    $response = $this->postJson(
        'http://'.$this->domain->hostname.'/api/storefront/v1/carts/'.$cart->id.'/lines',
        ['variant_id' => $variant->id, 'quantity' => 1],
    );

    $response->assertStatus(422);
});

it('updates a cart line quantity', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 2000,
        'status' => VariantStatus::Active,
    ]);
    $line = CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 2000,
        'line_subtotal_amount' => 2000,
        'line_discount_amount' => 0,
        'line_total_amount' => 2000,
    ]);

    $response = $this->putJson(
        'http://'.$this->domain->hostname.'/api/storefront/v1/carts/'.$cart->id.'/lines/'.$line->id,
        ['quantity' => 5, 'cart_version' => $cart->cart_version],
    );

    $response->assertOk()
        ->assertJsonPath('totals.total', 10000);
});

it('rejects update with wrong cart version', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'status' => VariantStatus::Active,
    ]);
    $line = CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 2000,
        'line_subtotal_amount' => 2000,
        'line_discount_amount' => 0,
        'line_total_amount' => 2000,
    ]);

    $response = $this->putJson(
        'http://'.$this->domain->hostname.'/api/storefront/v1/carts/'.$cart->id.'/lines/'.$line->id,
        ['quantity' => 5, 'cart_version' => 999],
    );

    $response->assertStatus(409);
});

it('removes a cart line', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'status' => VariantStatus::Active,
    ]);
    $line = CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 2000,
        'line_subtotal_amount' => 2000,
        'line_discount_amount' => 0,
        'line_total_amount' => 2000,
    ]);

    $response = $this->deleteJson(
        'http://'.$this->domain->hostname.'/api/storefront/v1/carts/'.$cart->id.'/lines/'.$line->id,
    );

    $response->assertOk()
        ->assertJsonPath('totals.total', 0)
        ->assertJsonCount(0, 'lines');
});

it('returns 404 for non-existent cart', function () {
    $response = $this->getJson(
        'http://'.$this->domain->hostname.'/api/storefront/v1/carts/99999',
    );

    $response->assertNotFound();
});
