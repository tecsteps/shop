<?php

use App\Enums\DiscountValueType;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
use App\Models\ProductVariant;
use App\Services\DiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->discountService = new DiscountService;
});

it('validates an active discount code', function () {
    $discount = Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'ACTIVE',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['price_amount' => 5000]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 5000, 'line_subtotal_amount' => 5000, 'line_total_amount' => 5000]);
    $cart->load('lines');

    $result = $this->discountService->validate('ACTIVE', $this->store, $cart);

    expect($result->id)->toBe($discount->id);
});

it('rejects an expired discount code', function () {
    Discount::factory()->expired()->create([
        'store_id' => $this->store->id,
        'code' => 'EXPIRED',
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $cart->load('lines');

    $this->discountService->validate('EXPIRED', $this->store, $cart);
})->throws(InvalidDiscountException::class, 'expired');

it('rejects a not-yet-active discount code', function () {
    Discount::factory()->notYetActive()->create([
        'store_id' => $this->store->id,
        'code' => 'FUTURE',
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $cart->load('lines');

    $this->discountService->validate('FUTURE', $this->store, $cart);
})->throws(InvalidDiscountException::class, 'not_yet_active');

it('rejects a discount that has reached its usage limit', function () {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'LIMITED',
        'usage_limit' => 10,
        'usage_count' => 10,
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $cart->load('lines');

    $this->discountService->validate('LIMITED', $this->store, $cart);
})->throws(InvalidDiscountException::class, 'usage_limit_reached');

it('rejects an unknown discount code', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $cart->load('lines');

    $this->discountService->validate('DOESNOTEXIST', $this->store, $cart);
})->throws(InvalidDiscountException::class, 'not_found');

it('performs case-insensitive code lookup', function () {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'SUMMER20',
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['price_amount' => 5000]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 5000, 'line_subtotal_amount' => 5000, 'line_total_amount' => 5000]);
    $cart->load('lines');

    $result = $this->discountService->validate('summer20', $this->store, $cart);

    expect($result->code)->toBe('SUMMER20');
});

it('enforces minimum purchase amount rule', function () {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'MINPURCHASE',
        'rules_json' => ['minimum_purchase' => 5000],
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['price_amount' => 3000]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 3000, 'line_subtotal_amount' => 3000, 'line_total_amount' => 3000]);
    $cart->load('lines');

    $this->discountService->validate('MINPURCHASE', $this->store, $cart);
})->throws(InvalidDiscountException::class, 'minimum_not_met');

it('passes minimum purchase when cart meets threshold', function () {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'MINOK',
        'rules_json' => ['minimum_purchase' => 5000],
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['price_amount' => 5000]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 5000, 'line_subtotal_amount' => 5000, 'line_total_amount' => 5000]);
    $cart->load('lines');

    $result = $this->discountService->validate('MINOK', $this->store, $cart);

    expect($result)->toBeInstanceOf(Discount::class);
});

it('calculates percent discount amount', function () {
    $discount = Discount::factory()->make([
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 15,
    ]);

    $result = $this->discountService->calculate($discount, 10000);

    expect($result['amount'])->toBe(1500);
});

it('calculates fixed discount amount', function () {
    $discount = Discount::factory()->fixed(500)->make();

    $result = $this->discountService->calculate($discount, 10000);

    expect($result['amount'])->toBe(500);
});

it('handles free shipping discount type', function () {
    $discount = Discount::factory()->freeShipping()->make();

    $result = $this->discountService->calculate($discount, 5000);

    expect($result['amount'])->toBe(0)
        ->and($result['free_shipping'])->toBeTrue();
});

it('allocates discount proportionally across multiple lines', function () {
    $discount = Discount::factory()->make([
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
    ]);

    $lines = [
        ['line_id' => 1, 'subtotal' => 7500],
        ['line_id' => 2, 'subtotal' => 2500],
    ];

    $result = $this->discountService->calculate($discount, 10000, $lines);

    expect($result['amount'])->toBe(1000)
        ->and($result['allocations'][1])->toBe(750)
        ->and($result['allocations'][2])->toBe(250);
});

it('distributes rounding remainder to the last qualifying line', function () {
    $discount = Discount::factory()->make([
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
    ]);

    $lines = [
        ['line_id' => 1, 'subtotal' => 3333],
        ['line_id' => 2, 'subtotal' => 3333],
        ['line_id' => 3, 'subtotal' => 3334],
    ];

    $result = $this->discountService->calculate($discount, 10000, $lines);

    $totalAllocated = array_sum($result['allocations']);
    expect($totalAllocated)->toBe($result['amount']);
});
