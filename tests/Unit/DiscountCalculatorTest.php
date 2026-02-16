<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
use App\Services\DiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new DiscountService;
    $this->ctx = createStoreContext();
});

it('validates an active discount code', function () {
    $discount = Discount::factory()->for($this->ctx['store'])->create([
        'code' => 'ACTIVE10',
        'status' => DiscountStatus::Active,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
    ]);

    $cart = Cart::factory()->for($this->ctx['store'])->create();
    CartLine::factory()->for($cart)->create(['unit_price' => 5000, 'quantity' => 1, 'subtotal' => 5000, 'total' => 5000]);
    $cart->load('lines');

    $result = $this->service->validate('ACTIVE10', $this->ctx['store'], $cart);
    expect($result->id)->toBe($discount->id);
});

it('rejects an expired discount code', function () {
    Discount::factory()->for($this->ctx['store'])->create([
        'code' => 'EXPIRED',
        'status' => DiscountStatus::Active,
        'ends_at' => now()->subDay(),
    ]);

    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $cart->load('lines');

    $this->service->validate('EXPIRED', $this->ctx['store'], $cart);
})->throws(InvalidDiscountException::class);

it('rejects a not-yet-active discount code', function () {
    Discount::factory()->for($this->ctx['store'])->create([
        'code' => 'FUTURE',
        'status' => DiscountStatus::Active,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addMonth(),
    ]);

    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $cart->load('lines');

    $this->service->validate('FUTURE', $this->ctx['store'], $cart);
})->throws(InvalidDiscountException::class);

it('rejects a discount that has reached its usage limit', function () {
    Discount::factory()->for($this->ctx['store'])->create([
        'code' => 'LIMITED',
        'status' => DiscountStatus::Active,
        'usage_limit' => 10,
        'usage_count' => 10,
    ]);

    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $cart->load('lines');

    $this->service->validate('LIMITED', $this->ctx['store'], $cart);
})->throws(InvalidDiscountException::class);

it('rejects an unknown discount code', function () {
    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $cart->load('lines');

    $this->service->validate('DOESNOTEXIST', $this->ctx['store'], $cart);
})->throws(InvalidDiscountException::class);

it('performs case-insensitive code lookup', function () {
    Discount::factory()->for($this->ctx['store'])->create([
        'code' => 'SUMMER20',
        'status' => DiscountStatus::Active,
    ]);

    $cart = Cart::factory()->for($this->ctx['store'])->create();
    CartLine::factory()->for($cart)->create(['unit_price' => 5000, 'quantity' => 1, 'subtotal' => 5000, 'total' => 5000]);
    $cart->load('lines');

    $result = $this->service->validate('summer20', $this->ctx['store'], $cart);
    expect($result->code)->toBe('SUMMER20');
});

it('enforces minimum purchase amount rule', function () {
    Discount::factory()->for($this->ctx['store'])->create([
        'code' => 'MINPURCHASE',
        'status' => DiscountStatus::Active,
        'minimum_purchase' => 5000,
    ]);

    $cart = Cart::factory()->for($this->ctx['store'])->create();
    CartLine::factory()->for($cart)->create(['unit_price' => 3000, 'quantity' => 1, 'subtotal' => 3000, 'total' => 3000]);
    $cart->load('lines');

    $this->service->validate('MINPURCHASE', $this->ctx['store'], $cart);
})->throws(InvalidDiscountException::class);

it('passes minimum purchase when cart meets threshold', function () {
    Discount::factory()->for($this->ctx['store'])->create([
        'code' => 'MINOK',
        'status' => DiscountStatus::Active,
        'minimum_purchase' => 5000,
    ]);

    $cart = Cart::factory()->for($this->ctx['store'])->create();
    CartLine::factory()->for($cart)->create(['unit_price' => 5000, 'quantity' => 1, 'subtotal' => 5000, 'total' => 5000]);
    $cart->load('lines');

    $result = $this->service->validate('MINOK', $this->ctx['store'], $cart);
    expect($result)->toBeInstanceOf(Discount::class);
});

it('calculates percent discount amount', function () {
    $discount = Discount::factory()->for($this->ctx['store'])->create([
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 1500,
    ]);

    $result = $this->service->calculate($discount, 10000, []);
    expect($result->amount)->toBe(1500);
});

it('calculates fixed discount amount', function () {
    $discount = Discount::factory()->for($this->ctx['store'])->create([
        'value_type' => DiscountValueType::Fixed,
        'value_amount' => 500,
    ]);

    $result = $this->service->calculate($discount, 10000, []);
    expect($result->amount)->toBe(500);
});

it('handles free shipping discount type', function () {
    $discount = Discount::factory()->for($this->ctx['store'])->create([
        'value_type' => DiscountValueType::FreeShipping,
        'value_amount' => 0,
    ]);

    $result = $this->service->calculate($discount, 10000, []);
    expect($result->amount)->toBe(0)
        ->and($result->freeShipping)->toBeTrue();
});
