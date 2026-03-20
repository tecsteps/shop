<?php

use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\DiscountService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->discountService = app(DiscountService::class);
});

it('validates a valid percent discount code', function () {
    $discount = Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'SAVE10',
        'value_type' => 'percent',
        'value_amount' => 10,
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 5000]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 5000,
        'line_subtotal_amount' => 10000,
        'line_total_amount' => 10000,
    ]);

    $result = $this->discountService->validate('SAVE10', $this->store, $cart);
    expect($result->id)->toBe($discount->id);
});

it('validates case-insensitively', function () {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'SAVE10',
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'line_subtotal_amount' => 5000]);

    $result = $this->discountService->validate('save10', $this->store, $cart);
    expect($result->code)->toBe('SAVE10');
});

it('rejects nonexistent code', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);

    expect(fn () => $this->discountService->validate('NOSUCHCODE', $this->store, $cart))
        ->toThrow(InvalidDiscountException::class);
});

it('rejects disabled discount', function () {
    Discount::factory()->disabled()->create([
        'store_id' => $this->store->id,
        'code' => 'DISABLED1',
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);

    try {
        $this->discountService->validate('DISABLED1', $this->store, $cart);
        $this->fail('Expected InvalidDiscountException');
    } catch (InvalidDiscountException $e) {
        expect($e->reason)->toBe('discount_expired');
    }
});

it('rejects discount not yet active', function () {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'FUTURE10',
        'starts_at' => now()->addMonth()->toIso8601String(),
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);

    try {
        $this->discountService->validate('FUTURE10', $this->store, $cart);
        $this->fail('Expected InvalidDiscountException');
    } catch (InvalidDiscountException $e) {
        expect($e->reason)->toBe('discount_not_yet_active');
    }
});

it('rejects expired discount', function () {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'OLD10',
        'starts_at' => now()->subMonth()->toIso8601String(),
        'ends_at' => now()->subDay()->toIso8601String(),
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);

    try {
        $this->discountService->validate('OLD10', $this->store, $cart);
        $this->fail('Expected InvalidDiscountException');
    } catch (InvalidDiscountException $e) {
        expect($e->reason)->toBe('discount_expired');
    }
});

it('rejects discount with usage limit reached', function () {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'LIMITED',
        'usage_limit' => 5,
        'usage_count' => 5,
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);

    try {
        $this->discountService->validate('LIMITED', $this->store, $cart);
        $this->fail('Expected InvalidDiscountException');
    } catch (InvalidDiscountException $e) {
        expect($e->reason)->toBe('discount_usage_limit_reached');
    }
});

it('rejects discount when minimum purchase not met', function () {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'MIN50',
        'minimum_purchase_amount' => 20000,
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'line_subtotal_amount' => 10000,
    ]);

    try {
        $this->discountService->validate('MIN50', $this->store, $cart);
        $this->fail('Expected InvalidDiscountException');
    } catch (InvalidDiscountException $e) {
        expect($e->reason)->toBe('discount_min_purchase_not_met');
    }
});

it('calculates percent discount correctly', function () {
    $discount = Discount::factory()->create([
        'store_id' => $this->store->id,
        'value_type' => 'percent',
        'value_amount' => 10,
    ]);

    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $line = CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'line_subtotal_amount' => 10000,
    ]);

    $result = $this->discountService->calculate($discount, 10000, collect([$line]));
    expect($result->totalDiscount)->toBe(1000);
});

it('calculates fixed discount correctly', function () {
    $discount = Discount::factory()->fixed(500)->create([
        'store_id' => $this->store->id,
    ]);

    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $line = CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'line_subtotal_amount' => 10000,
    ]);

    $result = $this->discountService->calculate($discount, 10000, collect([$line]));
    expect($result->totalDiscount)->toBe(500);
});

it('caps fixed discount at qualifying subtotal', function () {
    $discount = Discount::factory()->fixed(15000)->create([
        'store_id' => $this->store->id,
    ]);

    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $line = CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'line_subtotal_amount' => 10000,
    ]);

    $result = $this->discountService->calculate($discount, 10000, collect([$line]));
    expect($result->totalDiscount)->toBe(10000);
});

it('free shipping discount does not reduce item amounts', function () {
    $discount = Discount::factory()->freeShipping()->create([
        'store_id' => $this->store->id,
    ]);

    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $line = CartLine::factory()->create([
        'variant_id' => $variant->id,
        'line_subtotal_amount' => 10000,
    ]);

    $result = $this->discountService->calculate($discount, 10000, collect([$line]));
    expect($result->totalDiscount)->toBe(0)
        ->and($result->isFreeShipping)->toBeTrue();
});

it('allocates discount proportionally across lines', function () {
    $discount = Discount::factory()->create([
        'store_id' => $this->store->id,
        'value_type' => 'percent',
        'value_amount' => 10,
    ]);

    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $variant1 = ProductVariant::factory()->create(['product_id' => $product->id]);
    $variant2 = ProductVariant::factory()->create(['product_id' => $product->id]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $lineA = CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant1->id,
        'line_subtotal_amount' => 6000,
    ]);
    $lineB = CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant2->id,
        'line_subtotal_amount' => 4000,
    ]);

    $result = $this->discountService->calculate($discount, 10000, collect([$lineA, $lineB]));

    expect($result->totalDiscount)->toBe(1000)
        ->and($result->lineAllocations[$lineA->id])->toBe(600)
        ->and($result->lineAllocations[$lineB->id])->toBe(400);
});
