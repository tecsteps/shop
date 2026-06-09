<?php

use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
use App\Models\Store;
use App\Services\DiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Build a store + cart with a single line at the given subtotal.
 *
 * @return array{store: Store, cart: Cart}
 */
function discountTestContext(int $subtotal = 10000): array
{
    $store = Store::factory()->create();
    $cart = Cart::factory()->for($store)->create();
    CartLine::factory()->for($cart)->priced($subtotal, 1)->create();

    return ['store' => $store, 'cart' => $cart];
}

/**
 * Validate a code and return the InvalidDiscountException reason, or null
 * when validation passes.
 */
function discountValidationReason(string $code, Store $store, Cart $cart): ?string
{
    try {
        app(DiscountService::class)->validate($code, $store, $cart);
    } catch (InvalidDiscountException $exception) {
        return $exception->reason;
    }

    return null;
}

it('validates an active discount code', function () {
    ['store' => $store, 'cart' => $cart] = discountTestContext();
    $discount = Discount::factory()->for($store)->create([
        'code' => 'SAVE10',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
    ]);

    $validated = app(DiscountService::class)->validate('SAVE10', $store, $cart);

    expect($validated)->toBeInstanceOf(Discount::class);
    expect($validated->getKey())->toBe($discount->getKey());
});

it('rejects an expired discount code', function () {
    ['store' => $store, 'cart' => $cart] = discountTestContext();
    Discount::factory()->for($store)->create([
        'code' => 'OLD20',
        'starts_at' => now()->subYear(),
        'ends_at' => now()->subDay(),
    ]);

    expect(discountValidationReason('OLD20', $store, $cart))->toBe('expired');
});

it('rejects a not-yet-active discount code', function () {
    ['store' => $store, 'cart' => $cart] = discountTestContext();
    Discount::factory()->for($store)->create([
        'code' => 'SOON10',
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addYear(),
    ]);

    expect(discountValidationReason('SOON10', $store, $cart))->toBe('not_yet_active');
});

it('rejects a discount that has reached its usage limit', function () {
    ['store' => $store, 'cart' => $cart] = discountTestContext();
    Discount::factory()->for($store)->create([
        'code' => 'LIMITED',
        'usage_limit' => 10,
        'usage_count' => 10,
    ]);

    expect(discountValidationReason('LIMITED', $store, $cart))->toBe('usage_limit_reached');
});

it('rejects an unknown discount code', function () {
    ['store' => $store, 'cart' => $cart] = discountTestContext();

    expect(discountValidationReason('DOESNOTEXIST', $store, $cart))->toBe('not_found');
});

it('performs case-insensitive code lookup', function () {
    ['store' => $store, 'cart' => $cart] = discountTestContext();
    Discount::factory()->for($store)->create(['code' => 'SUMMER20']);

    $validated = app(DiscountService::class)->validate('summer20', $store, $cart);

    expect($validated->code)->toBe('SUMMER20');
});

it('enforces minimum purchase amount rule', function () {
    ['store' => $store, 'cart' => $cart] = discountTestContext(3000);
    Discount::factory()->for($store)->create([
        'code' => 'MIN50',
        'rules_json' => ['min_purchase_amount' => 5000],
    ]);

    expect(discountValidationReason('MIN50', $store, $cart))->toBe('minimum_not_met');
});

it('passes minimum purchase when cart meets threshold', function () {
    ['store' => $store, 'cart' => $cart] = discountTestContext(5000);
    Discount::factory()->for($store)->create([
        'code' => 'MIN50',
        'rules_json' => ['min_purchase_amount' => 5000],
    ]);

    expect(discountValidationReason('MIN50', $store, $cart))->toBeNull();
});

it('calculates percent discount amount', function () {
    ['store' => $store, 'cart' => $cart] = discountTestContext(10000);
    $discount = Discount::factory()->for($store)->create(['value_amount' => 15]);

    $result = app(DiscountService::class)->calculate($discount, 10000, $cart->lines()->get()->all());

    expect($result->amount)->toBe(1500);
});

it('calculates fixed discount amount', function () {
    ['store' => $store, 'cart' => $cart] = discountTestContext(10000);
    $discount = Discount::factory()->for($store)->fixed(500)->create();

    $result = app(DiscountService::class)->calculate($discount, 10000, $cart->lines()->get()->all());

    expect($result->amount)->toBe(500);
});

it('handles free shipping discount type', function () {
    ['store' => $store, 'cart' => $cart] = discountTestContext(10000);
    $discount = Discount::factory()->for($store)->freeShipping()->create();

    $result = app(DiscountService::class)->calculate($discount, 10000, $cart->lines()->get()->all());

    expect($result->amount)->toBe(0);
    expect($result->freeShipping)->toBeTrue();
});

it('allocates discount proportionally across multiple lines', function () {
    $store = Store::factory()->create();
    $cart = Cart::factory()->for($store)->create();
    $lineA = CartLine::factory()->for($cart)->priced(7500, 1)->create();
    $lineB = CartLine::factory()->for($cart)->priced(2500, 1)->create();

    $discount = Discount::factory()->for($store)->create(['value_amount' => 10]);

    $result = app(DiscountService::class)->calculate($discount, 10000, $cart->lines()->get()->all());

    expect($result->amount)->toBe(1000);
    expect($result->allocations[$lineA->getKey()])->toBe(750);
    expect($result->allocations[$lineB->getKey()])->toBe(250);
});

it('distributes rounding remainder to the last qualifying line', function () {
    $store = Store::factory()->create();
    $cart = Cart::factory()->for($store)->create();
    CartLine::factory()->for($cart)->priced(1111, 1)->create();
    CartLine::factory()->for($cart)->priced(2222, 1)->create();
    CartLine::factory()->for($cart)->priced(3333, 1)->create();

    $discount = Discount::factory()->for($store)->create(['value_amount' => 15]);

    $result = app(DiscountService::class)->calculate($discount, 6666, $cart->lines()->get()->all());

    expect(array_sum($result->allocations))->toBe($result->amount);
});
