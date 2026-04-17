<?php

use App\Enums\DiscountStatus;
use App\Exceptions\InvalidDiscountException;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\CartService;
use App\Services\DiscountService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function makeCartWithLine(int $price = 1000, int $qty = 2): array
{
    $store = Store::factory()->create();
    $product = Product::factory()->active()->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->getKey(),
        'price_amount' => $price,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $store->getKey(),
        'variant_id' => $variant->getKey(),
        'quantity_on_hand' => 100,
    ]);

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, (int) $variant->getKey(), $qty);

    return [$store, $cart, $product, $variant];
}

it('validates an active code case-insensitively', function () {
    [$store, $cart] = makeCartWithLine();

    Discount::factory()->create([
        'store_id' => $store->getKey(),
        'code' => 'SAVE10',
    ]);

    $result = app(DiscountService::class)->validate('save10', $store, $cart);

    expect($result->code)->toBe('SAVE10');
});

it('rejects an unknown code', function () {
    [$store, $cart] = makeCartWithLine();

    app(DiscountService::class)->validate('NOPE', $store, $cart);
})->throws(InvalidDiscountException::class, InvalidDiscountException::CODE_NOT_FOUND);

it('rejects an expired discount by ends_at', function () {
    [$store, $cart] = makeCartWithLine();

    Discount::factory()->expired()->create([
        'store_id' => $store->getKey(),
        'code' => 'OLD',
    ]);

    app(DiscountService::class)->validate('OLD', $store, $cart);
})->throws(InvalidDiscountException::class, InvalidDiscountException::CODE_EXPIRED);

it('rejects a discount before starts_at', function () {
    [$store, $cart] = makeCartWithLine();

    Discount::factory()->create([
        'store_id' => $store->getKey(),
        'code' => 'FUTURE',
        'starts_at' => now()->addDay(),
    ]);

    app(DiscountService::class)->validate('FUTURE', $store, $cart);
})->throws(InvalidDiscountException::class, InvalidDiscountException::CODE_NOT_YET_ACTIVE);

it('rejects a discount at its usage limit', function () {
    [$store, $cart] = makeCartWithLine();

    Discount::factory()->exhausted()->create([
        'store_id' => $store->getKey(),
        'code' => 'DONE',
    ]);

    app(DiscountService::class)->validate('DONE', $store, $cart);
})->throws(InvalidDiscountException::class, InvalidDiscountException::CODE_USAGE_LIMIT_REACHED);

it('rejects a discount below the minimum purchase threshold', function () {
    [$store, $cart] = makeCartWithLine(price: 500, qty: 1);

    Discount::factory()->create([
        'store_id' => $store->getKey(),
        'code' => 'BIG',
        'rules_json' => ['min_purchase_amount' => 10000],
    ]);

    app(DiscountService::class)->validate('BIG', $store, $cart);
})->throws(InvalidDiscountException::class, InvalidDiscountException::CODE_MIN_PURCHASE_NOT_MET);

it('rejects a discount that restricts to other products', function () {
    [$store, $cart] = makeCartWithLine();
    $other = Product::factory()->active()->create(['store_id' => $store->getKey()]);

    Discount::factory()->create([
        'store_id' => $store->getKey(),
        'code' => 'LIMITED',
        'rules_json' => ['applicable_product_ids' => [(int) $other->getKey()]],
    ]);

    app(DiscountService::class)->validate('LIMITED', $store, $cart);
})->throws(InvalidDiscountException::class, InvalidDiscountException::CODE_NOT_APPLICABLE);

it('rejects a disabled discount as expired', function () {
    [$store, $cart] = makeCartWithLine();

    Discount::factory()->create([
        'store_id' => $store->getKey(),
        'code' => 'OFF',
        'status' => DiscountStatus::Disabled->value,
    ]);

    app(DiscountService::class)->validate('OFF', $store, $cart);
})->throws(InvalidDiscountException::class, InvalidDiscountException::CODE_EXPIRED);

it('calculates a percent discount allocated across qualifying lines', function () {
    [$store, $cart] = makeCartWithLine(price: 1000, qty: 3);

    $discount = Discount::factory()->percent(10)->create([
        'store_id' => $store->getKey(),
        'code' => 'P10',
    ]);

    $result = app(DiscountService::class)->calculate($discount, $cart);

    expect($result->totalAmount)->toBe(300)
        ->and($result->allocations)->toHaveCount(1);
});

it('caps a fixed discount at the qualifying subtotal', function () {
    [$store, $cart] = makeCartWithLine(price: 500, qty: 1);

    $discount = Discount::factory()->fixed(999999)->create([
        'store_id' => $store->getKey(),
        'code' => 'BIG',
    ]);

    $result = app(DiscountService::class)->calculate($discount, $cart);

    expect($result->totalAmount)->toBe(500);
});

it('marks free shipping discounts with freeShipping flag', function () {
    [$store, $cart] = makeCartWithLine();

    $discount = Discount::factory()->freeShipping()->create([
        'store_id' => $store->getKey(),
        'code' => 'SHIPFREE',
    ]);

    $result = app(DiscountService::class)->calculate($discount, $cart);

    expect($result->freeShipping)->toBeTrue()
        ->and($result->totalAmount)->toBe(0);
});
