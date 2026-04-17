<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->domain = $this->context['domain'];
});

it('creates a cart via session-based flow', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'policy' => 'deny',
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);

    expect($cart)->toBeInstanceOf(Cart::class)
        ->and($cart->cart_version)->toBe(1);
});

it('retrieves a cart with lines', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 5000,
        'line_total_amount' => 5000,
    ]);

    $cart->load('lines');
    expect($cart->lines)->toHaveCount(1)
        ->and($cart->lines->first()->line_subtotal_amount)->toBe(5000);
});

it('validates variant exists on add', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $cartService = app(\App\Services\CartService::class);

    expect(fn () => $cartService->addLine($cart, 999999, 1))
        ->toThrow(\App\Exceptions\InvalidCartException::class);
});

it('validates quantity is positive', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'policy' => 'deny',
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $cartService = app(\App\Services\CartService::class);
    $line = $cartService->addLine($cart, $variant->id, 2);

    // Setting quantity to 0 removes the line
    $cartService->updateLineQuantity($cart, $line->id, 0);

    $cart->refresh();
    expect($cart->lines)->toHaveCount(0);
});

it('updates line quantity via service', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'policy' => 'deny',
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $cartService = app(\App\Services\CartService::class);
    $line = $cartService->addLine($cart, $variant->id, 1);
    $updated = $cartService->updateLineQuantity($cart, $line->id, 5);

    expect($updated->quantity)->toBe(5)
        ->and($updated->line_subtotal_amount)->toBe(12500);
});

it('removes a line via service', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'policy' => 'deny',
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $cartService = app(\App\Services\CartService::class);
    $line = $cartService->addLine($cart, $variant->id, 1);
    $cartService->removeLine($cart, $line->id);

    $cart->refresh();
    expect($cart->lines)->toHaveCount(0);
});

it('rejects variant from different store', function () {
    $otherContext = createStoreContext();
    $otherStore = $otherContext['store'];

    $product = Product::factory()->create(['store_id' => $otherStore->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 1000]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $cartService = app(\App\Services\CartService::class);

    expect(fn () => $cartService->addLine($cart, $variant->id, 1))
        ->toThrow(\App\Exceptions\InvalidCartException::class, 'Variant does not belong to this store.');
});

it('rejects update when new quantity exceeds inventory with deny policy', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 1000]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 3,
        'policy' => 'deny',
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $cartService = app(\App\Services\CartService::class);
    $line = $cartService->addLine($cart, $variant->id, 2);

    expect(fn () => $cartService->updateLineQuantity($cart, $line->id, 10))
        ->toThrow(\App\Exceptions\InsufficientInventoryException::class);
});
