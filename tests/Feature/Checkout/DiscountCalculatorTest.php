<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
use App\Models\Store;
use App\Services\DiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new DiscountService;
});

it('validates a valid discount code', function () {
    $store = Store::factory()->create();
    $discount = Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'SAVE10',
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'line_subtotal_amount' => 5000,
    ]);

    $result = $this->service->validate('SAVE10', $store, $cart->load('lines'));

    expect($result->id)->toBe($discount->id);
});

it('validates case-insensitively', function () {
    $store = Store::factory()->create();
    Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'SAVE10',
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->id]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'line_subtotal_amount' => 5000]);

    $result = $this->service->validate('save10', $store, $cart->load('lines'));

    expect($result->code)->toBe('SAVE10');
});

it('throws not_found for unknown code', function () {
    $store = Store::factory()->create();
    $cart = Cart::factory()->create(['store_id' => $store->id]);

    $this->service->validate('UNKNOWN', $store, $cart);
})->throws(InvalidDiscountException::class);

it('throws expired for inactive discount', function () {
    $store = Store::factory()->create();
    Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'EXPIRED',
        'status' => DiscountStatus::Disabled,
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->id]);

    $this->service->validate('EXPIRED', $store, $cart);
})->throws(InvalidDiscountException::class);

it('throws not_yet_active for future start date', function () {
    $store = Store::factory()->create();
    Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'FUTURE',
        'starts_at' => now()->addWeek(),
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->id]);

    $this->service->validate('FUTURE', $store, $cart);
})->throws(InvalidDiscountException::class);

it('throws expired for past end date', function () {
    $store = Store::factory()->create();
    Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'PASTEND',
        'ends_at' => now()->subDay(),
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->id]);

    $this->service->validate('PASTEND', $store, $cart);
})->throws(InvalidDiscountException::class);

it('throws usage_limit_reached when limit exceeded', function () {
    $store = Store::factory()->create();
    Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'LIMITED',
        'usage_limit' => 5,
        'usage_count' => 5,
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->id]);

    $this->service->validate('LIMITED', $store, $cart);
})->throws(InvalidDiscountException::class);

it('throws minimum_not_met when subtotal is too low', function () {
    $store = Store::factory()->create();
    Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'MINPURCHASE',
        'rules_json' => ['minimum_purchase_amount' => 10000],
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'line_subtotal_amount' => 5000,
    ]);

    $this->service->validate('MINPURCHASE', $store, $cart->load('lines'));
})->throws(InvalidDiscountException::class);

it('calculates percent discount', function () {
    $discount = Discount::factory()->make([
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
    ]);

    $lines = [
        ['line_subtotal_amount' => 5000, 'product_id' => 1],
    ];

    $result = $this->service->calculate($discount, 5000, $lines);

    expect($result['total_discount'])->toBe(500);
});

it('calculates fixed discount', function () {
    $discount = Discount::factory()->make([
        'value_type' => DiscountValueType::Fixed,
        'value_amount' => 1000,
    ]);

    $lines = [
        ['line_subtotal_amount' => 5000, 'product_id' => 1],
    ];

    $result = $this->service->calculate($discount, 5000, $lines);

    expect($result['total_discount'])->toBe(1000);
});

it('caps fixed discount at subtotal', function () {
    $discount = Discount::factory()->make([
        'value_type' => DiscountValueType::Fixed,
        'value_amount' => 10000,
    ]);

    $lines = [
        ['line_subtotal_amount' => 5000, 'product_id' => 1],
    ];

    $result = $this->service->calculate($discount, 5000, $lines);

    expect($result['total_discount'])->toBe(5000);
});

it('returns zero discount for free shipping', function () {
    $discount = Discount::factory()->freeShipping()->make();

    $lines = [
        ['line_subtotal_amount' => 5000, 'product_id' => 1],
    ];

    $result = $this->service->calculate($discount, 5000, $lines);

    expect($result['total_discount'])->toBe(0);
});

it('allocates discount proportionally across multiple lines', function () {
    $discount = Discount::factory()->make([
        'value_type' => DiscountValueType::Fixed,
        'value_amount' => 1000,
    ]);

    $lines = [
        0 => ['line_subtotal_amount' => 3000, 'product_id' => 1],
        1 => ['line_subtotal_amount' => 7000, 'product_id' => 2],
    ];

    $result = $this->service->calculate($discount, 10000, $lines);

    expect($result['total_discount'])->toBe(1000)
        ->and(array_sum($result['allocations']))->toBe(1000);
});
