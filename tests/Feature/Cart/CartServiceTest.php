<?php

use App\Enums\CartStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\InvalidCartException;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->cartService = app(CartService::class);
});

it('creates a cart for the store', function () {
    $cart = $this->cartService->create($this->store);

    expect($cart)->toBeInstanceOf(Cart::class)
        ->and($cart->store_id)->toBe($this->store->id)
        ->and($cart->currency)->toBe('USD')
        ->and($cart->cart_version)->toBe(1)
        ->and($cart->status)->toBe(CartStatus::Active);
});

it('creates a cart for an authenticated customer', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    $cart = $this->cartService->create($this->store, $customer);

    expect($cart->customer_id)->toBe($customer->id);
});

it('creates a guest cart with null customer', function () {
    $cart = $this->cartService->create($this->store);
    expect($cart->customer_id)->toBeNull();
});

it('adds a line item to the cart', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'policy' => 'deny',
    ]);

    $cart = $this->cartService->create($this->store);
    $line = $this->cartService->addLine($cart, $variant->id, 2);

    expect($line->variant_id)->toBe($variant->id)
        ->and($line->quantity)->toBe(2)
        ->and($line->unit_price_amount)->toBe(2500)
        ->and($line->line_subtotal_amount)->toBe(5000)
        ->and($line->line_discount_amount)->toBe(0)
        ->and($line->line_total_amount)->toBe(5000);
});

it('increments quantity when adding existing variant', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'policy' => 'deny',
    ]);

    $cart = $this->cartService->create($this->store);
    $this->cartService->addLine($cart, $variant->id, 1);
    $this->cartService->addLine($cart, $variant->id, 2);

    $cart->refresh();
    expect($cart->lines)->toHaveCount(1)
        ->and($cart->lines->first()->quantity)->toBe(3)
        ->and($cart->lines->first()->line_subtotal_amount)->toBe(7500);
});

it('rejects add when product is not active', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'draft']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 1000]);

    $cart = $this->cartService->create($this->store);

    expect(fn () => $this->cartService->addLine($cart, $variant->id, 1))
        ->toThrow(InvalidCartException::class, 'Product is not active.');
});

it('rejects add when variant is not active', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'status' => 'archived']);

    $cart = $this->cartService->create($this->store);

    expect(fn () => $this->cartService->addLine($cart, $variant->id, 1))
        ->toThrow(InvalidCartException::class, 'Variant is not active.');
});

it('rejects add when inventory is insufficient with deny policy', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 1000]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 2,
        'policy' => 'deny',
    ]);

    $cart = $this->cartService->create($this->store);

    expect(fn () => $this->cartService->addLine($cart, $variant->id, 5))
        ->toThrow(InsufficientInventoryException::class);
});

it('allows add when inventory is insufficient with continue policy', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 1000]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 2,
        'policy' => 'continue',
    ]);

    $cart = $this->cartService->create($this->store);
    $line = $this->cartService->addLine($cart, $variant->id, 5);

    expect($line->quantity)->toBe(5);
});

it('updates line quantity', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'policy' => 'deny',
    ]);

    $cart = $this->cartService->create($this->store);
    $line = $this->cartService->addLine($cart, $variant->id, 2);
    $updated = $this->cartService->updateLineQuantity($cart, $line->id, 5);

    expect($updated->quantity)->toBe(5)
        ->and($updated->line_subtotal_amount)->toBe(12500);
});

it('removes line when quantity is set to zero', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 1000]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'policy' => 'deny',
    ]);

    $cart = $this->cartService->create($this->store);
    $line = $this->cartService->addLine($cart, $variant->id, 2);
    $this->cartService->updateLineQuantity($cart, $line->id, 0);

    $cart->refresh();
    expect($cart->lines)->toHaveCount(0);
});

it('merges guest cart into customer cart using MAX quantity', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'policy' => 'deny',
    ]);

    $guestCart = $this->cartService->create($this->store);
    $this->cartService->addLine($guestCart, $variant->id, 3);

    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    $customerCart = $this->cartService->create($this->store, $customer);
    $this->cartService->addLine($customerCart, $variant->id, 1);

    $merged = $this->cartService->mergeOnLogin($guestCart, $customerCart);

    $merged->refresh();
    expect($merged->lines)->toHaveCount(1)
        ->and($merged->lines->first()->quantity)->toBe(3);

    $guestCart->refresh();
    expect($guestCart->status)->toBe(CartStatus::Abandoned);
});
