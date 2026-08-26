<?php

use App\Exceptions\InsufficientInventoryException;
use App\Services\CartService;
use App\Services\ProductService;

it('creates a cart for the current store', function () {
    $ctx = createStoreContext();
    $cart = app(CartService::class)->create($ctx['store']);

    expect($cart->store_id)->toBe($ctx['store']->id);
    expect($cart->currency)->toBe('USD');
    expect($cart->cart_version)->toBe(1);
    expect($cart->status)->toBe('active');
});

it('adds a line item to the cart', function () {
    $ctx = createStoreContext();
    $product = app(ProductService::class)->create($ctx['store'], ['title' => 'Shirt', 'price_amount' => 2500, 'quantity_on_hand' => 10]);
    app(ProductService::class)->transitionStatus($product, \App\Enums\ProductStatus::Active);
    $cart = app(CartService::class)->create($ctx['store']);

    $line = app(CartService::class)->addLine($cart, $product->variants()->first()->id, 2);

    expect($line->unit_price_amount)->toBe(2500);
    expect($line->line_subtotal_amount)->toBe(5000);
    expect($line->line_total_amount)->toBe(5000);
});

it('increments quantity when adding an existing variant', function () {
    $ctx = createStoreContext();
    $product = app(ProductService::class)->create($ctx['store'], ['title' => 'Shirt', 'price_amount' => 2500, 'quantity_on_hand' => 10]);
    app(ProductService::class)->transitionStatus($product, \App\Enums\ProductStatus::Active);
    $cart = app(CartService::class)->create($ctx['store']);
    $variantId = $product->variants()->first()->id;

    app(CartService::class)->addLine($cart, $variantId, 1);
    $line = app(CartService::class)->addLine($cart, $variantId, 2);

    expect($cart->lines()->count())->toBe(1);
    expect($line->quantity)->toBe(3);
    expect($line->line_subtotal_amount)->toBe(7500);
});

it('rejects add when product is not active', function () {
    $ctx = createStoreContext();
    $product = app(ProductService::class)->create($ctx['store'], ['title' => 'Draft', 'price_amount' => 2500, 'quantity_on_hand' => 10]);
    $cart = app(CartService::class)->create($ctx['store']);

    expect(fn () => app(CartService::class)->addLine($cart, $product->variants()->first()->id, 1))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects add when inventory is insufficient and policy is deny', function () {
    $ctx = createStoreContext();
    $product = app(ProductService::class)->create($ctx['store'], ['title' => 'Low', 'price_amount' => 2500, 'quantity_on_hand' => 2]);
    app(ProductService::class)->transitionStatus($product, \App\Enums\ProductStatus::Active);
    $cart = app(CartService::class)->create($ctx['store']);

    expect(fn () => app(CartService::class)->addLine($cart, $product->variants()->first()->id, 5))
        ->toThrow(InsufficientInventoryException::class);
});

it('allows add when inventory is insufficient but policy is continue', function () {
    $ctx = createStoreContext();
    $product = app(ProductService::class)->create($ctx['store'], ['title' => 'Backorder', 'price_amount' => 2500, 'quantity_on_hand' => 2, 'inventory_policy' => 'continue']);
    app(ProductService::class)->transitionStatus($product, \App\Enums\ProductStatus::Active);
    $cart = app(CartService::class)->create($ctx['store']);

    $line = app(CartService::class)->addLine($cart, $product->variants()->first()->id, 5);

    expect($line->quantity)->toBe(5);
});

it('merges guest cart into customer cart on login', function () {
    $ctx = createStoreContext();
    $customer = \App\Models\Customer::factory()->create(['store_id' => $ctx['store']->id]);

    $productA = app(ProductService::class)->create($ctx['store'], ['title' => 'A', 'price_amount' => 1000, 'quantity_on_hand' => 10]);
    $productB = app(ProductService::class)->create($ctx['store'], ['title' => 'B', 'price_amount' => 2000, 'quantity_on_hand' => 10]);
    app(ProductService::class)->transitionStatus($productA, \App\Enums\ProductStatus::Active);
    app(ProductService::class)->transitionStatus($productB, \App\Enums\ProductStatus::Active);

    $guest = app(CartService::class)->create($ctx['store']);
    app(CartService::class)->addLine($guest, $productA->variants()->first()->id, 2);

    $customerCart = app(CartService::class)->create($ctx['store'], $customer);
    app(CartService::class)->addLine($customerCart, $productA->variants()->first()->id, 1);
    app(CartService::class)->addLine($customerCart, $productB->variants()->first()->id, 3);

    app(CartService::class)->mergeOnLogin($guest, $customerCart);

    $merged = $customerCart->fresh();
    expect($merged->lines()->where('variant_id', $productA->variants()->first()->id)->first()->quantity)->toBe(2);
    expect($merged->lines()->where('variant_id', $productB->variants()->first()->id)->first()->quantity)->toBe(3);
    expect($guest->fresh()->status)->toBe('abandoned');
});
