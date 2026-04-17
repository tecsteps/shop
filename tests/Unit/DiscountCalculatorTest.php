<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Enums\VariantStatus;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\DiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

function createDiscountContext(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::factory()->for($store)->active()->create();
    $variant = ProductVariant::factory()->for($product)->create([
        'price_amount' => 5000,
        'status' => VariantStatus::Active,
    ]);

    $cart = Cart::factory()->for($store)->create();
    $line = CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 5000,
        'line_subtotal_amount' => 10000,
        'line_total_amount' => 10000,
    ]);

    return array_merge($ctx, [
        'product' => $product,
        'variant' => $variant,
        'cart' => $cart,
        'line' => $line,
    ]);
}

it('validates an active discount code', function () {
    $ctx = createDiscountContext();
    $store = $ctx['store'];
    $cart = $ctx['cart'];

    $discount = Discount::factory()->for($store)->create([
        'code' => 'VALID10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 1000,
        'status' => DiscountStatus::Active,
    ]);

    $service = app(DiscountService::class);
    $result = $service->validate('VALID10', $store, $cart);

    expect($result->id)->toBe($discount->id);
});

it('rejects an expired discount code', function () {
    $ctx = createDiscountContext();
    $store = $ctx['store'];
    $cart = $ctx['cart'];

    Discount::factory()->for($store)->create([
        'code' => 'EXPIRED',
        'ends_at' => now()->subDay(),
        'status' => DiscountStatus::Active,
    ]);

    $service = app(DiscountService::class);

    expect(fn () => $service->validate('EXPIRED', $store, $cart))
        ->toThrow(InvalidDiscountException::class);
});

it('rejects a not-yet-active discount code', function () {
    $ctx = createDiscountContext();
    $store = $ctx['store'];
    $cart = $ctx['cart'];

    Discount::factory()->for($store)->create([
        'code' => 'FUTURE',
        'starts_at' => now()->addDays(5),
        'ends_at' => now()->addDays(30),
        'status' => DiscountStatus::Active,
    ]);

    $service = app(DiscountService::class);

    expect(fn () => $service->validate('FUTURE', $store, $cart))
        ->toThrow(InvalidDiscountException::class);
});

it('rejects a discount that has reached its usage limit', function () {
    $ctx = createDiscountContext();
    $store = $ctx['store'];
    $cart = $ctx['cart'];

    Discount::factory()->for($store)->create([
        'code' => 'LIMITED',
        'usage_limit' => 5,
        'usage_count' => 5,
        'status' => DiscountStatus::Active,
    ]);

    $service = app(DiscountService::class);

    expect(fn () => $service->validate('LIMITED', $store, $cart))
        ->toThrow(InvalidDiscountException::class);
});

it('rejects an unknown discount code', function () {
    $ctx = createDiscountContext();
    $store = $ctx['store'];
    $cart = $ctx['cart'];

    $service = app(DiscountService::class);

    expect(fn () => $service->validate('NONEXISTENT', $store, $cart))
        ->toThrow(InvalidDiscountException::class);
});

it('performs case-insensitive code lookup', function () {
    $ctx = createDiscountContext();
    $store = $ctx['store'];
    $cart = $ctx['cart'];

    $discount = Discount::factory()->for($store)->create([
        'code' => 'SAVE10',
        'status' => DiscountStatus::Active,
    ]);

    $service = app(DiscountService::class);
    $result = $service->validate('save10', $store, $cart);

    expect($result->id)->toBe($discount->id);
});

it('enforces minimum purchase amount rule', function () {
    $ctx = createDiscountContext();
    $store = $ctx['store'];
    $cart = $ctx['cart'];

    Discount::factory()->for($store)->create([
        'code' => 'MINPURCHASE',
        'status' => DiscountStatus::Active,
        'rules_json' => ['minimum_purchase' => 50000],
    ]);

    $service = app(DiscountService::class);

    expect(fn () => $service->validate('MINPURCHASE', $store, $cart))
        ->toThrow(InvalidDiscountException::class);
});

it('passes minimum purchase when cart meets threshold', function () {
    $ctx = createDiscountContext();
    $store = $ctx['store'];
    $cart = $ctx['cart'];

    $discount = Discount::factory()->for($store)->create([
        'code' => 'MINOK',
        'status' => DiscountStatus::Active,
        'rules_json' => ['minimum_purchase' => 5000],
    ]);

    $service = app(DiscountService::class);
    $result = $service->validate('MINOK', $store, $cart);

    expect($result->id)->toBe($discount->id);
});

it('calculates percent discount amount', function () {
    $discount = Discount::factory()->make([
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 1500,
    ]);

    $service = app(DiscountService::class);
    $result = $service->calculate($discount, 10000, [
        ['id' => 1, 'subtotal' => 10000],
    ]);

    expect($result->totalDiscount)->toBe(1500);
});

it('calculates fixed discount amount', function () {
    $discount = Discount::factory()->make([
        'value_type' => DiscountValueType::Fixed,
        'value_amount' => 500,
    ]);

    $service = app(DiscountService::class);
    $result = $service->calculate($discount, 10000, [
        ['id' => 1, 'subtotal' => 10000],
    ]);

    expect($result->totalDiscount)->toBe(500);
});

it('handles free shipping discount type', function () {
    $discount = Discount::factory()->make([
        'value_type' => DiscountValueType::FreeShipping,
        'value_amount' => 0,
    ]);

    $service = app(DiscountService::class);
    $result = $service->calculate($discount, 10000, [
        ['id' => 1, 'subtotal' => 10000],
    ]);

    expect($result->freeShipping)->toBeTrue()
        ->and($result->totalDiscount)->toBe(0);
});

it('allocates discount proportionally across multiple lines', function () {
    $discount = Discount::factory()->make([
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 1000,
    ]);

    $service = app(DiscountService::class);
    $result = $service->calculate($discount, 10000, [
        ['id' => 1, 'subtotal' => 7000],
        ['id' => 2, 'subtotal' => 3000],
    ]);

    // 10% of 10000 = 1000
    // Line 1: intdiv(1000 * 7000, 10000) = 700
    // Line 2: 1000 - 700 = 300
    expect($result->totalDiscount)->toBe(1000)
        ->and($result->lineAllocations[1])->toBe(700)
        ->and($result->lineAllocations[2])->toBe(300);
});

it('distributes rounding remainder to the last qualifying line', function () {
    $discount = Discount::factory()->make([
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 1000,
    ]);

    $service = app(DiscountService::class);
    $result = $service->calculate($discount, 9999, [
        ['id' => 1, 'subtotal' => 3333],
        ['id' => 2, 'subtotal' => 3333],
        ['id' => 3, 'subtotal' => 3333],
    ]);

    // 10% of 9999 = intdiv(99990000, 10000) = 9999
    // Line 1: intdiv(9999 * 3333, 9999) = 3333
    // Line 2: intdiv(9999 * 3333, 9999) = 3333
    // Line 3: 9999 - 3333 - 3333 = 3333
    $total = $result->lineAllocations[1] + $result->lineAllocations[2] + $result->lineAllocations[3];

    expect($total)->toBe($result->totalDiscount);
});
