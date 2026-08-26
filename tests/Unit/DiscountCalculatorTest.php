<?php

use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\Store;
use App\Services\CartService;
use App\Services\DiscountService;

/**
 * @return string the InvalidDiscountException reason code
 */
function discountError(Closure $fn): string
{
    try {
        $fn();
    } catch (InvalidDiscountException $e) {
        return $e->reasonCode;
    }

    \PHPUnit\Framework\Assert::fail('Expected InvalidDiscountException to be thrown.');
}

it('validates an active discount code', function () {
    $store = Store::factory()->create();
    $cart = app(CartService::class)->create($store);
    $discount = Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'SUMMER20',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'status' => 'active',
    ]);

    $result = app(DiscountService::class)->validate('SUMMER20', $store, $cart);

    expect($result->id)->toBe($discount->id);
});

it('rejects an expired discount code', function () {
    $store = Store::factory()->create();
    $cart = app(CartService::class)->create($store);
    Discount::factory()->create(['store_id' => $store->id, 'code' => 'EXPIRED', 'ends_at' => now()->subDay()]);

    expect(discountError(fn () => app(DiscountService::class)->validate('EXPIRED', $store, $cart)))->toBe('discount_expired');
});

it('rejects a not-yet-active discount code', function () {
    $store = Store::factory()->create();
    $cart = app(CartService::class)->create($store);
    Discount::factory()->create(['store_id' => $store->id, 'code' => 'FUTURE', 'starts_at' => now()->addDay()]);

    expect(discountError(fn () => app(DiscountService::class)->validate('FUTURE', $store, $cart)))->toBe('discount_not_yet_active');
});

it('rejects a discount that has reached its usage limit', function () {
    $store = Store::factory()->create();
    $cart = app(CartService::class)->create($store);
    Discount::factory()->create(['store_id' => $store->id, 'code' => 'USEDUP', 'usage_limit' => 10, 'usage_count' => 10]);

    expect(discountError(fn () => app(DiscountService::class)->validate('USEDUP', $store, $cart)))->toBe('discount_usage_limit_reached');
});

it('rejects an unknown discount code', function () {
    $store = Store::factory()->create();
    $cart = app(CartService::class)->create($store);

    expect(discountError(fn () => app(DiscountService::class)->validate('DOESNOTEXIST', $store, $cart)))->toBe('discount_not_found');
});

it('performs case-insensitive code lookup', function () {
    $store = Store::factory()->create();
    $cart = app(CartService::class)->create($store);
    $discount = Discount::factory()->create(['store_id' => $store->id, 'code' => 'SUMMER20']);

    expect(app(DiscountService::class)->validate('summer20', $store, $cart)->id)->toBe($discount->id);
});

it('enforces minimum purchase amount rule', function () {
    $store = Store::factory()->create();
    $cart = app(CartService::class)->create($store);
    Discount::factory()->create(['store_id' => $store->id, 'code' => 'MIN', 'rules_json' => ['min_purchase_amount' => 5000]]);

    expect(discountError(fn () => app(DiscountService::class)->validate('MIN', $store, $cart)))->toBe('discount_min_purchase_not_met');
});

it('passes minimum purchase when cart meets threshold', function () {
    $store = Store::factory()->create();
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    // Build a cart with subtotal 5000 via a stub line
    \App\Models\CartLine::factory()->create([
        'cart_id' => $cart->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 5000,
        'line_total_amount' => 5000,
    ]);
    Discount::factory()->create(['store_id' => $store->id, 'code' => 'MIN', 'rules_json' => ['min_purchase_amount' => 5000]]);

    expect(app(DiscountService::class)->validate('MIN', $store, $cart))->not->toBeNull();
});

it('calculates percent discount amount', function () {
    $discount = Discount::make(['value_type' => 'percent', 'value_amount' => 15, 'rules_json' => []]);
    $result = app(DiscountService::class)->calculate($discount, 10000, [['id' => 1, 'subtotal' => 10000, 'product_id' => 1]]);

    expect($result->amount)->toBe(1500);
});

it('calculates fixed discount amount', function () {
    $discount = Discount::make(['value_type' => 'fixed', 'value_amount' => 500, 'rules_json' => []]);
    $result = app(DiscountService::class)->calculate($discount, 10000, [['id' => 1, 'subtotal' => 10000, 'product_id' => 1]]);

    expect($result->amount)->toBe(500);
});

it('handles free shipping discount type', function () {
    $discount = Discount::make(['value_type' => 'free_shipping', 'value_amount' => 0, 'rules_json' => []]);
    $result = app(DiscountService::class)->calculate($discount, 5000, [['id' => 1, 'subtotal' => 5000, 'product_id' => 1]]);

    expect($result->amount)->toBe(0);
    expect($result->freeShipping)->toBeTrue();
});

it('allocates discount proportionally across multiple lines', function () {
    $discount = Discount::make(['value_type' => 'percent', 'value_amount' => 10, 'rules_json' => []]);
    $result = app(DiscountService::class)->calculate($discount, 10000, [
        ['id' => 1, 'subtotal' => 7500, 'product_id' => 1],
        ['id' => 2, 'subtotal' => 2500, 'product_id' => 2],
    ]);

    expect($result->amount)->toBe(1000);
    expect($result->allocations[1])->toBe(750);
    expect($result->allocations[2])->toBe(250);
});

it('distributes rounding remainder to the last qualifying line', function () {
    $discount = Discount::make(['value_type' => 'percent', 'value_amount' => 10, 'rules_json' => []]);
    $result = app(DiscountService::class)->calculate($discount, 1000, [
        ['id' => 1, 'subtotal' => 333, 'product_id' => 1],
        ['id' => 2, 'subtotal' => 333, 'product_id' => 2],
        ['id' => 3, 'subtotal' => 334, 'product_id' => 3],
    ]);

    expect(array_sum($result->allocations))->toBe($result->amount);
});
