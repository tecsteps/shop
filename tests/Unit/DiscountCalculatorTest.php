<?php

use App\Enums\DiscountValueType;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\DiscountService;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

function discountService(): DiscountService
{
    return app(DiscountService::class);
}

/**
 * Create a cart whose single line has the given subtotal.
 */
function cartWithSubtotal(App\Models\Store $store, int $subtotal): Cart
{
    $cart = Cart::factory()->create(['store_id' => $store->id, 'currency' => 'USD']);
    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => $subtotal]);

    CartLine::create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => $subtotal,
        'line_subtotal_amount' => $subtotal,
        'line_discount_amount' => 0,
        'line_total_amount' => $subtotal,
    ]);

    return $cart->refresh();
}

/**
 * Flat lines for direct calculate() calls.
 *
 * @param  array<int, int>  $subtotals
 * @return array<int, array<string, mixed>>
 */
function discountLines(array $subtotals): array
{
    return array_map(fn (int $amount, int $index): array => [
        'product_id' => $index + 1,
        'collection_ids' => [],
        'line_subtotal_amount' => $amount,
    ], $subtotals, array_keys($subtotals));
}

test('validates an active discount code', function () {
    $store = $this->createStore();
    $cart = cartWithSubtotal($store, 10000);
    Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'SAVE10',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
    ]);

    $result = discountService()->validate('SAVE10', $store, $cart);

    expect($result->valid)->toBeTrue()
        ->and($result->discount)->toBeInstanceOf(Discount::class)
        ->and($result->discount->code)->toBe('SAVE10');
});

test('rejects an expired discount code', function () {
    $store = $this->createStore();
    $cart = cartWithSubtotal($store, 10000);
    Discount::factory()->expired()->create(['store_id' => $store->id, 'code' => 'OLD10']);

    $result = discountService()->validate('OLD10', $store, $cart);

    expect($result->valid)->toBeFalse()
        ->and($result->errorCode)->toBe('discount_expired');
});

test('rejects a not-yet-active discount code', function () {
    $store = $this->createStore();
    $cart = cartWithSubtotal($store, 10000);
    Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'FUTURE10',
        'starts_at' => now()->addDay(),
        'ends_at' => null,
    ]);

    $result = discountService()->validate('FUTURE10', $store, $cart);

    expect($result->valid)->toBeFalse()
        ->and($result->errorCode)->toBe('discount_not_yet_active');
});

test('rejects a discount that has reached its usage limit', function () {
    $store = $this->createStore();
    $cart = cartWithSubtotal($store, 10000);
    Discount::factory()->maxedOut()->create(['store_id' => $store->id, 'code' => 'MAXED10']);

    $result = discountService()->validate('MAXED10', $store, $cart);

    expect($result->valid)->toBeFalse()
        ->and($result->errorCode)->toBe('discount_usage_limit_reached');
});

test('rejects an unknown discount code', function () {
    $store = $this->createStore();
    $cart = cartWithSubtotal($store, 10000);

    $result = discountService()->validate('DOESNOTEXIST', $store, $cart);

    expect($result->valid)->toBeFalse()
        ->and($result->errorCode)->toBe('discount_not_found');
});

test('performs case-insensitive code lookup', function () {
    $store = $this->createStore();
    $cart = cartWithSubtotal($store, 10000);
    Discount::factory()->create(['store_id' => $store->id, 'code' => 'SUMMER20']);

    expect(discountService()->validate('summer20', $store, $cart)->valid)->toBeTrue();
});

test('enforces minimum purchase amount rule', function () {
    $store = $this->createStore();
    $cart = cartWithSubtotal($store, 3000);
    Discount::factory()->withMinPurchase(5000)->create(['store_id' => $store->id, 'code' => 'MIN50']);

    $result = discountService()->validate('MIN50', $store, $cart);

    expect($result->valid)->toBeFalse()
        ->and($result->errorCode)->toBe('discount_min_purchase_not_met');
});

test('passes minimum purchase when cart meets threshold', function () {
    $store = $this->createStore();
    $cart = cartWithSubtotal($store, 5000);
    Discount::factory()->withMinPurchase(5000)->create(['store_id' => $store->id, 'code' => 'MIN50']);

    expect(discountService()->validate('MIN50', $store, $cart)->valid)->toBeTrue();
});

test('calculates percent discount amount', function () {
    $discount = new Discount(['value_type' => DiscountValueType::Percent, 'value_amount' => 15, 'rules_json' => []]);

    $result = discountService()->calculate($discount, 10000, discountLines([10000]));

    expect($result['amount'])->toBe(1500);
});

test('calculates fixed discount amount', function () {
    $discount = new Discount(['value_type' => DiscountValueType::Fixed, 'value_amount' => 500, 'rules_json' => []]);

    $result = discountService()->calculate($discount, 10000, discountLines([10000]));

    expect($result['amount'])->toBe(500);
});

test('handles free shipping discount type', function () {
    $discount = new Discount(['value_type' => DiscountValueType::FreeShipping, 'value_amount' => 0, 'rules_json' => []]);

    $result = discountService()->calculate($discount, 10000, discountLines([10000]));

    expect($result['amount'])->toBe(0)
        ->and($result['free_shipping'])->toBeTrue();
});

test('allocates discount proportionally across multiple lines', function () {
    $discount = new Discount(['value_type' => DiscountValueType::Percent, 'value_amount' => 10, 'rules_json' => []]);

    $result = discountService()->calculate($discount, 10000, discountLines([7500, 2500]));

    expect($result['amount'])->toBe(1000)
        ->and($result['allocations'][0])->toBe(750)
        ->and($result['allocations'][1])->toBe(250);
});

test('distributes rounding remainder to the last qualifying line', function () {
    $discount = new Discount(['value_type' => DiscountValueType::Percent, 'value_amount' => 10, 'rules_json' => []]);

    $result = discountService()->calculate($discount, 10000, discountLines([3333, 3333, 3334]));

    expect($result['amount'])->toBe(1000)
        ->and(array_sum($result['allocations']))->toBe(1000)
        ->and($result['allocations'][2])->toBe(1000 - $result['allocations'][0] - $result['allocations'][1]);
});
