<?php

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new CartService;
    $this->ctx = createStoreContext();
});

it('starts at version 1', function () {
    $cart = Cart::factory()->for($this->ctx['store'])->create();
    expect($cart->cart_version)->toBe(1);
});

it('increments version on add line', function () {
    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);
    \App\Models\InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 100,
    ]);

    $this->service->addLine($cart, $variant->id, 1);
    $cart->refresh();

    expect($cart->cart_version)->toBe(2);
});

it('increments version on update quantity', function () {
    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);
    \App\Models\InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 100,
    ]);

    $line = $this->service->addLine($cart, $variant->id, 1);
    $this->service->updateLineQuantity($cart, $line->id, 5);
    $cart->refresh();

    expect($cart->cart_version)->toBe(3);
});

it('increments version on remove line', function () {
    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);
    \App\Models\InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 100,
    ]);

    $line = $this->service->addLine($cart, $variant->id, 1);
    $this->service->removeLine($cart, $line->id);
    $cart->refresh();

    expect($cart->cart_version)->toBe(3);
});
