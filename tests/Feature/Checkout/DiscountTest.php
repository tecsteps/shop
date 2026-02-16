<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
use App\Services\DiscountService;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->discountService = app(DiscountService::class);
});

it('applies a valid percent discount code at checkout', function () {
    $discount = Discount::factory()->for($this->ctx['store'])->create([
        'code' => 'SAVE10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 1000,
        'status' => DiscountStatus::Active,
    ]);

    $result = $this->discountService->calculate($discount, 5000, []);
    expect($result->amount)->toBe(500);
});

it('applies a valid fixed discount code at checkout', function () {
    $discount = Discount::factory()->for($this->ctx['store'])->create([
        'code' => '5OFF',
        'value_type' => DiscountValueType::Fixed,
        'value_amount' => 500,
        'status' => DiscountStatus::Active,
    ]);

    $result = $this->discountService->calculate($discount, 5000, []);
    expect($result->amount)->toBe(500);
});

it('handles free shipping discount at checkout', function () {
    $discount = Discount::factory()->for($this->ctx['store'])->create([
        'value_type' => DiscountValueType::FreeShipping,
        'status' => DiscountStatus::Active,
    ]);

    $result = $this->discountService->calculate($discount, 5000, []);
    expect($result->freeShipping)->toBeTrue()
        ->and($result->amount)->toBe(0);
});

it('rejects expired discount at checkout', function () {
    Discount::factory()->for($this->ctx['store'])->create([
        'code' => 'EXPIRED',
        'status' => DiscountStatus::Active,
        'ends_at' => now()->subDay(),
    ]);

    $cart = Cart::factory()->for($this->ctx['store'])->create();
    CartLine::factory()->for($cart)->create(['unit_price' => 5000, 'quantity' => 1, 'subtotal' => 5000, 'total' => 5000]);
    $cart->load('lines');

    $this->discountService->validate('EXPIRED', $this->ctx['store'], $cart);
})->throws(\App\Exceptions\InvalidDiscountException::class);
