<?php

use App\Enums\CartStatus;
use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Cart;
use App\Models\Discount;
use App\Services\DiscountService;
use Tests\Support\CommerceTestHelper;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

function discountContext(array $discountOverrides = [], int $cartSubtotal = 10000): array
{
    ['store' => $store] = test()->createStoreContext();

    $variant = CommerceTestHelper::purchasableVariant($store, ['price_amount' => 2500]);

    $cart = Cart::factory()->create([
        'store_id' => $store->id,
        'currency' => $store->default_currency,
        'cart_version' => 1,
        'status' => CartStatus::Active,
    ]);

    $quantity = max(1, intdiv($cartSubtotal, 2500));

    $cart->cartLines()->create([
        'variant_id' => $variant->id,
        'quantity' => $quantity,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 2500 * $quantity,
        'line_discount_amount' => 0,
        'line_total_amount' => 2500 * $quantity,
    ]);

    $discount = Discount::factory()->create(array_merge([
        'store_id' => $store->id,
        'type' => DiscountType::Code,
        'code' => 'SAVE10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
        'usage_limit' => null,
        'usage_count' => 0,
        'rules_json' => [],
        'status' => DiscountStatus::Active,
    ], $discountOverrides));

    return ['store' => $store, 'cart' => $cart->refresh(), 'discount' => $discount];
}

it('validates an active discount code', function () {
    ['store' => $store, 'cart' => $cart] = discountContext();

    $result = app(DiscountService::class)->validate('SAVE10', $store, $cart);

    expect($result->valid)->toBeTrue();
    expect($result->discount)->not->toBeNull();
    expect($result->discount->code)->toBe('SAVE10');
});

it('rejects an expired discount code', function () {
    ['store' => $store, 'cart' => $cart] = discountContext(['ends_at' => now()->subDay()]);

    $result = app(DiscountService::class)->validate('SAVE10', $store, $cart);

    expect($result->valid)->toBeFalse();
    expect($result->errorCode)->toBe('discount_expired');
});

it('rejects a not-yet-active discount code', function () {
    ['store' => $store, 'cart' => $cart] = discountContext(['starts_at' => now()->addDay()]);

    $result = app(DiscountService::class)->validate('SAVE10', $store, $cart);

    expect($result->valid)->toBeFalse();
    expect($result->errorCode)->toBe('discount_not_yet_active');
});

it('rejects a discount that has reached its usage limit', function () {
    ['store' => $store, 'cart' => $cart] = discountContext(['usage_limit' => 10, 'usage_count' => 10]);

    $result = app(DiscountService::class)->validate('SAVE10', $store, $cart);

    expect($result->valid)->toBeFalse();
    expect($result->errorCode)->toBe('discount_usage_limit_reached');
});

it('rejects an unknown discount code', function () {
    ['store' => $store, 'cart' => $cart] = discountContext();

    $result = app(DiscountService::class)->validate('DOESNOTEXIST', $store, $cart);

    expect($result->valid)->toBeFalse();
    expect($result->errorCode)->toBe('discount_not_found');
});

it('performs case-insensitive code lookup', function () {
    ['store' => $store, 'cart' => $cart] = discountContext(['code' => 'SUMMER20']);

    $result = app(DiscountService::class)->validate('summer20', $store, $cart);

    expect($result->valid)->toBeTrue();
});

it('enforces minimum purchase amount rule', function () {
    ['store' => $store, 'cart' => $cart] = discountContext([
        'rules_json' => ['min_purchase_amount' => 5000],
    ], 2500);

    $result = app(DiscountService::class)->validate('SAVE10', $store, $cart);

    expect($result->valid)->toBeFalse();
    expect($result->errorCode)->toBe('discount_min_purchase_not_met');
});

it('passes minimum purchase when cart meets threshold', function () {
    ['store' => $store, 'cart' => $cart] = discountContext([
        'rules_json' => ['min_purchase_amount' => 5000],
    ], 5000);

    $result = app(DiscountService::class)->validate('SAVE10', $store, $cart);

    expect($result->valid)->toBeTrue();
});

it('calculates percent discount amount', function () {
    ['discount' => $discount] = discountContext(['value_type' => DiscountValueType::Percent, 'value_amount' => 15]);

    $result = app(DiscountService::class)->calculate($discount, 10000, [
        ['line_id' => 1, 'subtotal' => 10000],
    ]);

    expect($result['total'])->toBe(1500);
});

it('calculates fixed discount amount', function () {
    ['discount' => $discount] = discountContext(['value_type' => DiscountValueType::Fixed, 'value_amount' => 500]);

    $result = app(DiscountService::class)->calculate($discount, 10000, [
        ['line_id' => 1, 'subtotal' => 10000],
    ]);

    expect($result['total'])->toBe(500);
});

it('handles free shipping discount type', function () {
    ['discount' => $discount] = discountContext(['value_type' => DiscountValueType::FreeShipping, 'value_amount' => 0]);

    $result = app(DiscountService::class)->calculate($discount, 10000, [
        ['line_id' => 1, 'subtotal' => 10000],
    ]);

    expect($result['total'])->toBe(0);
    expect($result['free_shipping'])->toBeTrue();
});

it('allocates discount proportionally across multiple lines', function () {
    ['discount' => $discount] = discountContext(['value_type' => DiscountValueType::Percent, 'value_amount' => 10]);

    $result = app(DiscountService::class)->calculate($discount, 10000, [
        ['line_id' => 1, 'subtotal' => 7500],
        ['line_id' => 2, 'subtotal' => 2500],
    ]);

    expect($result['total'])->toBe(1000);
    expect($result['allocations'][1])->toBe(750);
    expect($result['allocations'][2])->toBe(250);
});

it('distributes rounding remainder to the last qualifying line', function () {
    $result = app(DiscountService::class)->allocate(100, [
        ['line_id' => 1, 'subtotal' => 3333],
        ['line_id' => 2, 'subtotal' => 3333],
        ['line_id' => 3, 'subtotal' => 3334],
    ]);

    expect(array_sum($result))->toBe(100);
});
