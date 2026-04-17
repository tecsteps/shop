<?php

use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->cartService = app(CartService::class);
});

it('starts with cart version 1', function () {
    $cart = $this->cartService->create($this->store);
    expect($cart->cart_version)->toBe(1);
});

it('increments version on add line', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 1000]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'policy' => 'deny',
    ]);

    $cart = $this->cartService->create($this->store);
    $this->cartService->addLine($cart, $variant->id, 1);

    $cart->refresh();
    expect($cart->cart_version)->toBe(2);
});

it('increments version on update quantity', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 1000]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'policy' => 'deny',
    ]);

    $cart = $this->cartService->create($this->store);
    $line = $this->cartService->addLine($cart, $variant->id, 1);

    $this->cartService->updateLineQuantity($cart, $line->id, 3);

    $cart->refresh();
    expect($cart->cart_version)->toBe(3);
});

it('increments version on remove line', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 1000]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'policy' => 'deny',
    ]);

    $cart = $this->cartService->create($this->store);
    $line = $this->cartService->addLine($cart, $variant->id, 1);
    $this->cartService->removeLine($cart, $line->id);

    $cart->refresh();
    expect($cart->cart_version)->toBe(3);
});

it('increments version on merge', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 1000]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'policy' => 'deny',
    ]);

    $guestCart = $this->cartService->create($this->store);
    $this->cartService->addLine($guestCart, $variant->id, 2);

    $customerCart = $this->cartService->create($this->store);
    $this->cartService->addLine($customerCart, $variant->id, 1);

    $merged = $this->cartService->mergeOnLogin($guestCart, $customerCart);

    expect($merged->cart_version)->toBe(3);
});
