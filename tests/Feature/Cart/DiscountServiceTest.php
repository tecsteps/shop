<?php

use App\Enums\DiscountValueType;
use App\Enums\VariantStatus;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\DiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->service = app(DiscountService::class);
});

function createCartWithLines(Store $store, array $linePrices = [2500]): Cart
{
    $cart = Cart::factory()->create(['store_id' => $store->id]);

    foreach ($linePrices as $price) {
        $product = Product::factory()->active()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_amount' => $price,
            'status' => VariantStatus::Active,
        ]);

        CartLine::factory()->create([
            'cart_id' => $cart->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price_amount' => $price,
            'line_subtotal_amount' => $price,
            'line_total_amount' => $price,
        ]);
    }

    return $cart;
}

test('validates an active discount code', function () {
    $discount = Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'SAVE10',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
    ]);

    $cart = createCartWithLines($this->store, [5000]);

    $result = $this->service->validate('SAVE10', $this->store, $cart);

    expect($result->id)->toBe($discount->id);
});

test('rejects an expired discount code', function () {
    Discount::factory()->expired()->create([
        'store_id' => $this->store->id,
        'code' => 'EXPIRED',
    ]);

    $cart = createCartWithLines($this->store);

    expect(fn () => $this->service->validate('EXPIRED', $this->store, $cart))
        ->toThrow(InvalidDiscountException::class, 'expired');
});

test('rejects a not-yet-active discount code', function () {
    Discount::factory()->future()->create([
        'store_id' => $this->store->id,
        'code' => 'FUTURE',
    ]);

    $cart = createCartWithLines($this->store);

    expect(fn () => $this->service->validate('FUTURE', $this->store, $cart))
        ->toThrow(InvalidDiscountException::class, 'not_yet_active');
});

test('rejects a discount that has reached its usage limit', function () {
    Discount::factory()->exhausted()->create([
        'store_id' => $this->store->id,
        'code' => 'MAXED',
    ]);

    $cart = createCartWithLines($this->store);

    expect(fn () => $this->service->validate('MAXED', $this->store, $cart))
        ->toThrow(InvalidDiscountException::class, 'usage_limit_reached');
});

test('rejects an unknown discount code', function () {
    $cart = createCartWithLines($this->store);

    expect(fn () => $this->service->validate('DOESNOTEXIST', $this->store, $cart))
        ->toThrow(InvalidDiscountException::class, 'not_found');
});

test('performs case-insensitive code lookup', function () {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'SUMMER20',
    ]);

    $cart = createCartWithLines($this->store, [5000]);

    $result = $this->service->validate('summer20', $this->store, $cart);

    expect($result->code)->toBe('SUMMER20');
});

test('enforces minimum purchase amount rule', function () {
    Discount::factory()->withMinPurchase(5000)->create([
        'store_id' => $this->store->id,
        'code' => 'MINBUY',
    ]);

    $cart = createCartWithLines($this->store, [3000]);

    expect(fn () => $this->service->validate('MINBUY', $this->store, $cart))
        ->toThrow(InvalidDiscountException::class, 'minimum_not_met');
});

test('passes minimum purchase when cart meets threshold', function () {
    Discount::factory()->withMinPurchase(5000)->create([
        'store_id' => $this->store->id,
        'code' => 'MINBUY',
    ]);

    $cart = createCartWithLines($this->store, [5000]);

    $result = $this->service->validate('MINBUY', $this->store, $cart);

    expect($result)->toBeInstanceOf(Discount::class);
});

test('calculates percent discount amount', function () {
    $discount = Discount::factory()->create([
        'store_id' => $this->store->id,
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 15,
    ]);

    $result = $this->service->calculate($discount, 10000);

    expect($result['amount'])->toBe(1500);
    expect($result['free_shipping'])->toBeFalse();
});

test('calculates fixed discount amount', function () {
    $discount = Discount::factory()->fixed(500)->create([
        'store_id' => $this->store->id,
    ]);

    $result = $this->service->calculate($discount, 10000);

    expect($result['amount'])->toBe(500);
});

test('caps fixed discount at subtotal so it never goes negative', function () {
    $discount = Discount::factory()->fixed(500)->create([
        'store_id' => $this->store->id,
    ]);

    $result = $this->service->calculate($discount, 300);

    expect($result['amount'])->toBe(300);
});

test('handles free shipping discount type', function () {
    $discount = Discount::factory()->freeShipping()->create([
        'store_id' => $this->store->id,
    ]);

    $result = $this->service->calculate($discount, 10000);

    expect($result['amount'])->toBe(0);
    expect($result['free_shipping'])->toBeTrue();
});

test('allocates discount proportionally across multiple lines', function () {
    $lines = [
        ['line_subtotal_amount' => 7500, 'product_id' => 1],
        ['line_subtotal_amount' => 2500, 'product_id' => 2],
    ];

    $allocations = $this->service->allocateToLines(1000, $lines);

    expect($allocations[0])->toBe(750);
    expect($allocations[1])->toBe(250);
    expect(array_sum($allocations))->toBe(1000);
});

test('distributes rounding remainder to the last qualifying line', function () {
    $lines = [
        ['line_subtotal_amount' => 3333, 'product_id' => 1],
        ['line_subtotal_amount' => 3333, 'product_id' => 2],
        ['line_subtotal_amount' => 3334, 'product_id' => 3],
    ];

    $allocations = $this->service->allocateToLines(1000, $lines);

    expect(array_sum($allocations))->toBe(1000);
});

test('increments usage count', function () {
    $discount = Discount::factory()->create([
        'store_id' => $this->store->id,
        'usage_count' => 5,
    ]);

    $this->service->incrementUsage($discount);
    $discount->refresh();

    expect($discount->usage_count)->toBe(6);
});
