<?php

use App\Enums\CartStatus;
use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\DiscountService;

function createDiscountTestContext(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'title' => 'Test',
        'handle' => 'test-'.rand(1000, 9999),
        'status' => ProductStatus::Active,
        'published_at' => now(),
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'price_amount' => 5000,
        'currency' => 'EUR',
        'is_default' => true,
        'position' => 0,
        'status' => VariantStatus::Active,
    ]);

    InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    $cart = Cart::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => CartStatus::Active,
    ]);

    CartLine::create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 5000,
        'line_subtotal_amount' => 10000,
        'line_discount_amount' => 0,
        'line_total_amount' => 10000,
    ]);

    return array_merge($ctx, compact('product', 'variant', 'cart'));
}

it('validates an active discount code', function () {
    $ctx = createDiscountTestContext();
    $discount = Discount::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'type' => DiscountType::Code,
        'code' => 'VALID',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $service = app(DiscountService::class);
    $result = $service->validate('VALID', $ctx['store'], $ctx['cart']);

    expect($result->id)->toBe($discount->id);
});

it('rejects an expired discount code', function () {
    $ctx = createDiscountTestContext();
    Discount::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'type' => DiscountType::Code,
        'code' => 'EXPIRED',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->subDay(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $service = app(DiscountService::class);

    expect(fn () => $service->validate('EXPIRED', $ctx['store'], $ctx['cart']))
        ->toThrow(InvalidDiscountException::class);
});

it('rejects a not-yet-active discount code', function () {
    $ctx = createDiscountTestContext();
    Discount::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'type' => DiscountType::Code,
        'code' => 'FUTURE',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addMonth(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $service = app(DiscountService::class);

    expect(fn () => $service->validate('FUTURE', $ctx['store'], $ctx['cart']))
        ->toThrow(InvalidDiscountException::class);
});

it('rejects a discount that has reached its usage limit', function () {
    $ctx = createDiscountTestContext();
    Discount::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'type' => DiscountType::Code,
        'code' => 'MAXED',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'usage_limit' => 10,
        'usage_count' => 10,
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $service = app(DiscountService::class);

    expect(fn () => $service->validate('MAXED', $ctx['store'], $ctx['cart']))
        ->toThrow(InvalidDiscountException::class);
});

it('rejects an unknown discount code', function () {
    $ctx = createDiscountTestContext();
    $service = app(DiscountService::class);

    expect(fn () => $service->validate('DOESNOTEXIST', $ctx['store'], $ctx['cart']))
        ->toThrow(InvalidDiscountException::class);
});

it('performs case-insensitive code lookup', function () {
    $ctx = createDiscountTestContext();
    Discount::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'type' => DiscountType::Code,
        'code' => 'SUMMER20',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 20,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $service = app(DiscountService::class);
    $result = $service->validate('summer20', $ctx['store'], $ctx['cart']);

    expect($result->code)->toBe('SUMMER20');
});

it('enforces minimum purchase amount rule', function () {
    $ctx = createDiscountTestContext();
    Discount::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'type' => DiscountType::Code,
        'code' => 'MINBUY',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'status' => DiscountStatus::Active,
        'rules_json' => ['min_purchase_amount' => 50000],
    ]);

    $service = app(DiscountService::class);

    expect(fn () => $service->validate('MINBUY', $ctx['store'], $ctx['cart']))
        ->toThrow(InvalidDiscountException::class);
});

it('passes minimum purchase when cart meets threshold', function () {
    $ctx = createDiscountTestContext();
    Discount::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'type' => DiscountType::Code,
        'code' => 'MINOK',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'status' => DiscountStatus::Active,
        'rules_json' => ['min_purchase_amount' => 5000],
    ]);

    $service = app(DiscountService::class);
    $result = $service->validate('MINOK', $ctx['store'], $ctx['cart']);

    expect($result->code)->toBe('MINOK');
});

it('calculates percent discount amount', function () {
    $discount = new Discount([
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 15,
    ]);

    $service = app(DiscountService::class);
    $result = $service->calculate($discount, 10000, [['id' => 1, 'subtotal' => 10000]]);

    expect($result->amount)->toBe(1500);
});

it('calculates fixed discount amount', function () {
    $discount = new Discount([
        'value_type' => DiscountValueType::Fixed,
        'value_amount' => 500,
    ]);

    $service = app(DiscountService::class);
    $result = $service->calculate($discount, 10000, [['id' => 1, 'subtotal' => 10000]]);

    expect($result->amount)->toBe(500);
});

it('handles free shipping discount type', function () {
    $discount = new Discount([
        'value_type' => DiscountValueType::FreeShipping,
        'value_amount' => 0,
    ]);

    $service = app(DiscountService::class);
    $result = $service->calculate($discount, 5000, [['id' => 1, 'subtotal' => 5000]]);

    expect($result->amount)->toBe(0)
        ->and($result->isFreeShipping)->toBeTrue();
});

it('allocates discount proportionally across multiple lines', function () {
    $discount = new Discount([
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
    ]);

    $lines = [
        ['id' => 1, 'subtotal' => 7500],
        ['id' => 2, 'subtotal' => 2500],
    ];

    $service = app(DiscountService::class);
    $result = $service->calculate($discount, 10000, $lines);

    expect($result->amount)->toBe(1000)
        ->and($result->allocations[1])->toBe(750)
        ->and($result->allocations[2])->toBe(250);
});

it('distributes rounding remainder to the last qualifying line', function () {
    $discount = new Discount([
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
    ]);

    $lines = [
        ['id' => 1, 'subtotal' => 3333],
        ['id' => 2, 'subtotal' => 3333],
        ['id' => 3, 'subtotal' => 3334],
    ];

    $service = app(DiscountService::class);
    $result = $service->calculate($discount, 10000, $lines);

    $sumAllocations = array_sum($result->allocations);
    expect($sumAllocations)->toBe($result->amount);
});
