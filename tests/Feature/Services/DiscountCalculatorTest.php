<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\DiscountService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->service = app(DiscountService::class);
    $this->store = $this->ctx['store'];
});

// --- Validation Tests ---

it('validates an active discount code', function () {
    $discount = Discount::create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => 'SAVE10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subDay(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $cart = createCartWithLine(3000);

    $result = $this->service->validate('SAVE10', $this->store, $cart);

    expect($result->id)->toBe($discount->id);
});

it('validates code case-insensitively', function () {
    Discount::create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => 'SAVE10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subDay(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $cart = createCartWithLine(3000);

    $result = $this->service->validate('save10', $this->store, $cart);

    expect($result->code)->toBe('SAVE10');
});

it('rejects unknown discount code', function () {
    $cart = createCartWithLine(3000);

    expect(fn () => $this->service->validate('NOTREAL', $this->store, $cart))
        ->toThrow(InvalidDiscountException::class, 'discount_not_found');
});

it('rejects expired discount', function () {
    Discount::create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => 'OLD',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->subDay(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $cart = createCartWithLine(3000);

    expect(fn () => $this->service->validate('OLD', $this->store, $cart))
        ->toThrow(InvalidDiscountException::class);
});

it('rejects not-yet-active discount', function () {
    Discount::create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => 'FUTURE',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->addDay(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $cart = createCartWithLine(3000);

    expect(fn () => $this->service->validate('FUTURE', $this->store, $cart))
        ->toThrow(InvalidDiscountException::class);
});

it('rejects discount with exhausted usage limit', function () {
    Discount::create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => 'MAXED',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subDay(),
        'usage_limit' => 5,
        'usage_count' => 5,
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $cart = createCartWithLine(3000);

    expect(fn () => $this->service->validate('MAXED', $this->store, $cart))
        ->toThrow(InvalidDiscountException::class);
});

it('rejects discount when minimum purchase not met', function () {
    Discount::create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => 'MIN50',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subDay(),
        'status' => DiscountStatus::Active,
        'rules_json' => ['min_purchase_amount' => 5000],
    ]);

    $cart = createCartWithLine(2000);

    expect(fn () => $this->service->validate('MIN50', $this->store, $cart))
        ->toThrow(InvalidDiscountException::class, 'discount_min_purchase_not_met');
});

it('rejects disabled discount', function () {
    Discount::create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => 'DISABLED',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subDay(),
        'status' => DiscountStatus::Disabled,
        'rules_json' => [],
    ]);

    $cart = createCartWithLine(3000);

    expect(fn () => $this->service->validate('DISABLED', $this->store, $cart))
        ->toThrow(InvalidDiscountException::class);
});

// --- Calculation Tests ---

it('calculates percent discount', function () {
    $discount = Discount::create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => 'PCT15',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 15,
        'starts_at' => now()->subDay(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $cart = createCartWithLine(10000);
    $lines = $cart->lines;

    $result = $this->service->calculate($discount, 10000, $lines);

    expect($result['total'])->toBe(1500);
});

it('calculates fixed discount', function () {
    $discount = Discount::create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => 'FIXED5',
        'value_type' => DiscountValueType::Fixed,
        'value_amount' => 500,
        'starts_at' => now()->subDay(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $cart = createCartWithLine(10000);

    $result = $this->service->calculate($discount, 10000, $cart->lines);

    expect($result['total'])->toBe(500);
});

it('caps fixed discount at subtotal', function () {
    $discount = Discount::create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => 'BIG',
        'value_type' => DiscountValueType::Fixed,
        'value_amount' => 5000,
        'starts_at' => now()->subDay(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $cart = createCartWithLine(2000);

    $result = $this->service->calculate($discount, 2000, $cart->lines);

    expect($result['total'])->toBe(2000);
});

it('returns zero discount for free shipping type', function () {
    $discount = Discount::create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => 'FREESHIP',
        'value_type' => DiscountValueType::FreeShipping,
        'value_amount' => 0,
        'starts_at' => now()->subDay(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $cart = createCartWithLine(5000);

    $result = $this->service->calculate($discount, 5000, $cart->lines);

    expect($result['total'])->toBe(0);
    expect($result['allocations'])->toBeEmpty();
});

it('allocates discount proportionally across lines', function () {
    $discount = Discount::create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => 'PROP',
        'value_type' => DiscountValueType::Fixed,
        'value_amount' => 1000,
        'starts_at' => now()->subDay(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $cart = createCartWithMultipleLines();
    $lines = $cart->lines;
    $subtotal = $lines->sum('line_subtotal_amount');

    $result = $this->service->calculate($discount, $subtotal, $lines);

    expect($result['total'])->toBe(1000);
    expect(array_sum($result['allocations']))->toBe(1000);
    expect(count($result['allocations']))->toBe(2);
});

// --- Helper Methods ---

function createCartWithLine(int $unitPrice): Cart
{
    $store = app('current_store');
    $product = Product::factory()->create([
        'store_id' => $store->id,
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => $unitPrice,
        'status' => VariantStatus::Active,
    ]);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => $unitPrice,
        'line_subtotal_amount' => $unitPrice,
        'line_discount_amount' => 0,
        'line_total_amount' => $unitPrice,
    ]);

    return $cart->load('lines');
}

function createCartWithMultipleLines(): Cart
{
    $store = app('current_store');
    $product = Product::factory()->create([
        'store_id' => $store->id,
        'status' => ProductStatus::Active,
    ]);

    $variant1 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 3000,
        'status' => VariantStatus::Active,
    ]);
    $variant2 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 7000,
        'status' => VariantStatus::Active,
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant1->id,
        'quantity' => 1,
        'unit_price_amount' => 3000,
        'line_subtotal_amount' => 3000,
        'line_discount_amount' => 0,
        'line_total_amount' => 3000,
    ]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant2->id,
        'quantity' => 1,
        'unit_price_amount' => 7000,
        'line_subtotal_amount' => 7000,
        'line_discount_amount' => 0,
        'line_total_amount' => 7000,
    ]);

    return $cart->load('lines');
}
