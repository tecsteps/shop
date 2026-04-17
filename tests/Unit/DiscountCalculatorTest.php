<?php

use App\Enums\CartStatus;
use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
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
    $discountService = app(DiscountService::class);

    return compact('store', 'discountService') + $ctx;
}

function createCartForDiscount($store, array $lineSpecs = []): Cart
{
    $cart = Cart::factory()->create([
        'store_id' => $store->id,
        'currency' => 'USD',
        'status' => CartStatus::Active,
    ]);

    foreach ($lineSpecs as $spec) {
        $product = Product::factory()->active()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_amount' => $spec['price'],
        ]);
        $subtotal = $spec['price'] * $spec['quantity'];
        CartLine::factory()->create([
            'cart_id' => $cart->id,
            'variant_id' => $variant->id,
            'quantity' => $spec['quantity'],
            'unit_price_amount' => $spec['price'],
            'line_subtotal_amount' => $subtotal,
            'line_discount_amount' => 0,
            'line_total_amount' => $subtotal,
        ]);
    }

    return $cart->load('lines.variant.product');
}

it('validates an active discount code', function () {
    $ctx = createDiscountContext();
    $discount = Discount::factory()->create([
        'store_id' => $ctx['store']->id,
        'code' => 'ACTIVE10',
        'status' => DiscountStatus::Active,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
    ]);

    $cart = createCartForDiscount($ctx['store'], [['price' => 5000, 'quantity' => 1]]);

    $result = $ctx['discountService']->validate('ACTIVE10', $ctx['store'], $cart);

    expect($result->valid)->toBeTrue()
        ->and($result->discount)->toBeInstanceOf(Discount::class)
        ->and($result->discount->id)->toBe($discount->id);
});

it('rejects an expired discount code', function () {
    $ctx = createDiscountContext();
    Discount::factory()->create([
        'store_id' => $ctx['store']->id,
        'code' => 'EXPIRED',
        'status' => DiscountStatus::Active,
        'starts_at' => now()->subWeek(),
        'ends_at' => now()->subDay(),
    ]);

    $cart = createCartForDiscount($ctx['store'], [['price' => 5000, 'quantity' => 1]]);

    $result = $ctx['discountService']->validate('EXPIRED', $ctx['store'], $cart);

    expect($result->valid)->toBeFalse()
        ->and($result->errorCode)->toBe('discount_expired');
});

it('rejects a not-yet-active discount code', function () {
    $ctx = createDiscountContext();
    Discount::factory()->create([
        'store_id' => $ctx['store']->id,
        'code' => 'FUTURE',
        'status' => DiscountStatus::Active,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addMonth(),
    ]);

    $cart = createCartForDiscount($ctx['store'], [['price' => 5000, 'quantity' => 1]]);

    $result = $ctx['discountService']->validate('FUTURE', $ctx['store'], $cart);

    expect($result->valid)->toBeFalse()
        ->and($result->errorCode)->toBe('discount_not_yet_active');
});

it('rejects a discount that has reached its usage limit', function () {
    $ctx = createDiscountContext();
    Discount::factory()->create([
        'store_id' => $ctx['store']->id,
        'code' => 'LIMITED',
        'status' => DiscountStatus::Active,
        'usage_limit' => 10,
        'usage_count' => 10,
    ]);

    $cart = createCartForDiscount($ctx['store'], [['price' => 5000, 'quantity' => 1]]);

    $result = $ctx['discountService']->validate('LIMITED', $ctx['store'], $cart);

    expect($result->valid)->toBeFalse()
        ->and($result->errorCode)->toBe('discount_usage_limit_reached');
});

it('rejects an unknown discount code', function () {
    $ctx = createDiscountContext();
    $cart = createCartForDiscount($ctx['store'], [['price' => 5000, 'quantity' => 1]]);

    $result = $ctx['discountService']->validate('DOESNOTEXIST', $ctx['store'], $cart);

    expect($result->valid)->toBeFalse()
        ->and($result->errorCode)->toBe('discount_not_found');
});

it('performs case-insensitive code lookup', function () {
    $ctx = createDiscountContext();
    Discount::factory()->create([
        'store_id' => $ctx['store']->id,
        'code' => 'SUMMER20',
        'status' => DiscountStatus::Active,
    ]);

    $cart = createCartForDiscount($ctx['store'], [['price' => 5000, 'quantity' => 1]]);

    $result = $ctx['discountService']->validate('summer20', $ctx['store'], $cart);

    expect($result->valid)->toBeTrue();
});

it('enforces minimum purchase amount rule', function () {
    $ctx = createDiscountContext();
    Discount::factory()->create([
        'store_id' => $ctx['store']->id,
        'code' => 'MINPURCH',
        'status' => DiscountStatus::Active,
        'rules_json' => ['min_purchase_amount' => 5000],
    ]);

    $cart = createCartForDiscount($ctx['store'], [['price' => 3000, 'quantity' => 1]]);

    $result = $ctx['discountService']->validate('MINPURCH', $ctx['store'], $cart);

    expect($result->valid)->toBeFalse()
        ->and($result->errorCode)->toBe('discount_min_purchase_not_met');
});

it('passes minimum purchase when cart meets threshold', function () {
    $ctx = createDiscountContext();
    Discount::factory()->create([
        'store_id' => $ctx['store']->id,
        'code' => 'MINPURCH',
        'status' => DiscountStatus::Active,
        'rules_json' => ['min_purchase_amount' => 5000],
    ]);

    $cart = createCartForDiscount($ctx['store'], [['price' => 5000, 'quantity' => 1]]);

    $result = $ctx['discountService']->validate('MINPURCH', $ctx['store'], $cart);

    expect($result->valid)->toBeTrue();
});

it('calculates percent discount amount', function () {
    $ctx = createDiscountContext();
    $discount = Discount::factory()->create([
        'store_id' => $ctx['store']->id,
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 15,
        'status' => DiscountStatus::Active,
    ]);

    $lines = [
        ['line_id' => 1, 'product_id' => 1, 'collection_ids' => [], 'line_subtotal_amount' => 10000, 'quantity' => 1],
    ];

    $result = $ctx['discountService']->calculate($discount, 10000, $lines);

    expect($result['total_discount'])->toBe(1500);
});

it('calculates fixed discount amount', function () {
    $ctx = createDiscountContext();
    $discount = Discount::factory()->fixed()->create([
        'store_id' => $ctx['store']->id,
        'value_amount' => 500,
        'status' => DiscountStatus::Active,
    ]);

    $lines = [
        ['line_id' => 1, 'product_id' => 1, 'collection_ids' => [], 'line_subtotal_amount' => 10000, 'quantity' => 1],
    ];

    $result = $ctx['discountService']->calculate($discount, 10000, $lines);

    expect($result['total_discount'])->toBe(500);
});

it('handles free shipping discount type', function () {
    $ctx = createDiscountContext();
    $discount = Discount::factory()->freeShipping()->create([
        'store_id' => $ctx['store']->id,
        'status' => DiscountStatus::Active,
    ]);

    $lines = [
        ['line_id' => 1, 'product_id' => 1, 'collection_ids' => [], 'line_subtotal_amount' => 10000, 'quantity' => 1],
    ];

    $result = $ctx['discountService']->calculate($discount, 10000, $lines);

    expect($result['total_discount'])->toBe(0)
        ->and($result['line_discounts'])->toBeEmpty();
});

it('allocates discount proportionally across multiple lines', function () {
    $ctx = createDiscountContext();
    $discount = Discount::factory()->create([
        'store_id' => $ctx['store']->id,
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'status' => DiscountStatus::Active,
    ]);

    $lines = [
        ['line_id' => 1, 'product_id' => 1, 'collection_ids' => [], 'line_subtotal_amount' => 7500, 'quantity' => 1],
        ['line_id' => 2, 'product_id' => 2, 'collection_ids' => [], 'line_subtotal_amount' => 2500, 'quantity' => 1],
    ];

    $result = $ctx['discountService']->calculate($discount, 10000, $lines);

    expect($result['total_discount'])->toBe(1000)
        ->and($result['line_discounts'][1])->toBe(750)
        ->and($result['line_discounts'][2])->toBe(250);
});

it('distributes rounding remainder to the last qualifying line', function () {
    $ctx = createDiscountContext();
    $discount = Discount::factory()->create([
        'store_id' => $ctx['store']->id,
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'status' => DiscountStatus::Active,
    ]);

    // Three lines with subtotals that create uneven division
    $lines = [
        ['line_id' => 1, 'product_id' => 1, 'collection_ids' => [], 'line_subtotal_amount' => 3333, 'quantity' => 1],
        ['line_id' => 2, 'product_id' => 2, 'collection_ids' => [], 'line_subtotal_amount' => 3334, 'quantity' => 1],
        ['line_id' => 3, 'product_id' => 3, 'collection_ids' => [], 'line_subtotal_amount' => 3333, 'quantity' => 1],
    ];

    $subtotal = 10000;
    $result = $ctx['discountService']->calculate($discount, $subtotal, $lines);

    // Sum of allocations should exactly equal total discount
    $allocationsSum = array_sum($result['line_discounts']);
    expect($allocationsSum)->toBe($result['total_discount']);
});
