<?php

use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\Store;
use App\Services\DiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = new DiscountService;
    $this->store = Store::factory()->create();
});

/**
 * A cart whose lines sum to the given subtotal (single synthetic line).
 */
function cartWithSubtotal(Store $store, int $subtotal): Cart
{
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    $cart->lines()->create([
        'variant_id' => App\Models\ProductVariant::factory()->create([
            'product_id' => App\Models\Product::factory()->create(['store_id' => $store->id]),
        ])->id,
        'quantity' => 1,
        'unit_price_amount' => $subtotal,
        'line_subtotal_amount' => $subtotal,
        'line_discount_amount' => 0,
        'line_total_amount' => $subtotal,
    ]);

    return $cart->load('lines.variant.product');
}

it('validates an active discount code', function () {
    $discount = Discount::factory()->for($this->store)->percent(10, 'ACTIVE')->create();
    $cart = cartWithSubtotal($this->store, 10000);

    expect($this->service->validate('ACTIVE', $this->store, $cart)->id)->toBe($discount->id);
});

it('rejects an expired discount code', function () {
    Discount::factory()->for($this->store)->percent(10, 'EXPIRED')->expired()->create();
    $cart = cartWithSubtotal($this->store, 10000);

    try {
        $this->service->validate('EXPIRED', $this->store, $cart);
        $this->fail('Expected InvalidDiscountException.');
    } catch (InvalidDiscountException $e) {
        expect($e->reason)->toBe('expired');
    }
});

it('rejects a not-yet-active discount code', function () {
    Discount::factory()->for($this->store)->percent(10, 'SOON')->notYetActive()->create();
    $cart = cartWithSubtotal($this->store, 10000);

    try {
        $this->service->validate('SOON', $this->store, $cart);
        $this->fail('Expected InvalidDiscountException.');
    } catch (InvalidDiscountException $e) {
        expect($e->reason)->toBe('not_yet_active');
    }
});

it('rejects a discount that has reached its usage limit', function () {
    Discount::factory()->for($this->store)->percent(10, 'MAXED')->create(['usage_limit' => 10, 'usage_count' => 10]);
    $cart = cartWithSubtotal($this->store, 10000);

    try {
        $this->service->validate('MAXED', $this->store, $cart);
        $this->fail('Expected InvalidDiscountException.');
    } catch (InvalidDiscountException $e) {
        expect($e->reason)->toBe('usage_limit_reached');
    }
});

it('rejects an unknown discount code', function () {
    $cart = cartWithSubtotal($this->store, 10000);

    try {
        $this->service->validate('DOESNOTEXIST', $this->store, $cart);
        $this->fail('Expected InvalidDiscountException.');
    } catch (InvalidDiscountException $e) {
        expect($e->reason)->toBe('not_found');
    }
});

it('performs case-insensitive code lookup', function () {
    $discount = Discount::factory()->for($this->store)->percent(20, 'SUMMER20')->create();
    $cart = cartWithSubtotal($this->store, 10000);

    expect($this->service->validate('summer20', $this->store, $cart)->id)->toBe($discount->id);
});

it('enforces minimum purchase amount rule', function () {
    Discount::factory()->for($this->store)->percent(10, 'MIN50')->create(['rules_json' => ['min_purchase_amount' => 5000]]);
    $cart = cartWithSubtotal($this->store, 3000);

    try {
        $this->service->validate('MIN50', $this->store, $cart);
        $this->fail('Expected InvalidDiscountException.');
    } catch (InvalidDiscountException $e) {
        expect($e->reason)->toBe('minimum_not_met');
    }
});

it('passes minimum purchase when cart meets threshold', function () {
    $discount = Discount::factory()->for($this->store)->percent(10, 'MIN50')->create(['rules_json' => ['min_purchase_amount' => 5000]]);
    $cart = cartWithSubtotal($this->store, 5000);

    expect($this->service->validate('MIN50', $this->store, $cart)->id)->toBe($discount->id);
});

it('calculates percent discount amount', function () {
    $discount = Discount::factory()->for($this->store)->percent(15)->make();

    expect($this->service->calculate($discount, 10000, [1 => 10000])->amount)->toBe(1500);
});

it('calculates fixed discount amount', function () {
    $discount = Discount::factory()->for($this->store)->fixed(500)->make();

    expect($this->service->calculate($discount, 10000, [1 => 10000])->amount)->toBe(500);
});

it('handles free shipping discount type', function () {
    $discount = Discount::factory()->for($this->store)->freeShipping()->make();
    $result = $this->service->calculate($discount, 10000, [1 => 10000]);

    expect($result->amount)->toBe(0)
        ->and($result->freeShipping)->toBeTrue();
});

it('allocates discount proportionally across multiple lines', function () {
    $discount = Discount::factory()->for($this->store)->percent(10)->make();
    $result = $this->service->calculate($discount, 10000, [1 => 7500, 2 => 2500]);

    expect($result->amount)->toBe(1000)
        ->and($result->allocations[1])->toBe(750)
        ->and($result->allocations[2])->toBe(250);
});

it('distributes rounding remainder to the last qualifying line', function () {
    $discount = Discount::factory()->for($this->store)->percent(10)->make();
    // 3 lines: 3333 + 3333 + 3334 = 10000; 10% = 1000.
    $result = $this->service->calculate($discount, 10000, [1 => 3333, 2 => 3333, 3 => 3334]);

    expect(array_sum($result->allocations))->toBe($result->amount)
        ->and($result->amount)->toBe(1000);
});
