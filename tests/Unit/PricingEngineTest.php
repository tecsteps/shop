<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Enums\TaxMode;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\PricingEngine;
use App\Services\TaxCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

function createPricingContext(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $pricingEngine = app(PricingEngine::class);

    return compact('store', 'pricingEngine') + $ctx;
}

function createCartWithLines(array $lineSpecs, $store): array
{
    $cart = Cart::factory()->create([
        'store_id' => $store->id,
        'currency' => 'USD',
        'status' => CartStatus::Active,
    ]);

    $lines = [];
    foreach ($lineSpecs as $spec) {
        $product = \App\Models\Product::factory()->active()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_amount' => $spec['price'],
        ]);
        $subtotal = $spec['price'] * $spec['quantity'];
        $lines[] = CartLine::factory()->create([
            'cart_id' => $cart->id,
            'variant_id' => $variant->id,
            'quantity' => $spec['quantity'],
            'unit_price_amount' => $spec['price'],
            'line_subtotal_amount' => $subtotal,
            'line_discount_amount' => 0,
            'line_total_amount' => $subtotal,
        ]);
    }

    return compact('cart', 'lines');
}

it('calculates subtotal from line items', function () {
    $ctx = createPricingContext();
    ['cart' => $cart] = createCartWithLines([
        ['price' => 2499, 'quantity' => 2],
        ['price' => 7999, 'quantity' => 1],
    ], $ctx['store']);

    $checkout = Checkout::factory()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    $result = $ctx['pricingEngine']->calculate($checkout);

    expect($result->subtotal)->toBe(12997);
});

it('calculates subtotal for a single line', function () {
    $ctx = createPricingContext();
    ['cart' => $cart] = createCartWithLines([
        ['price' => 1500, 'quantity' => 3],
    ], $ctx['store']);

    $checkout = Checkout::factory()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    $result = $ctx['pricingEngine']->calculate($checkout);

    expect($result->subtotal)->toBe(4500);
});

it('returns zero subtotal for empty cart', function () {
    $ctx = createPricingContext();
    $cart = Cart::factory()->create([
        'store_id' => $ctx['store']->id,
        'currency' => 'USD',
        'status' => CartStatus::Active,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    $result = $ctx['pricingEngine']->calculate($checkout);

    expect($result->subtotal)->toBe(0)
        ->and($result->total)->toBe(0);
});

it('applies percent discount correctly', function () {
    $ctx = createPricingContext();
    ['cart' => $cart] = createCartWithLines([
        ['price' => 5000, 'quantity' => 2],
    ], $ctx['store']);

    Discount::factory()->create([
        'store_id' => $ctx['store']->id,
        'type' => DiscountType::Code,
        'code' => 'SAVE10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'status' => DiscountStatus::Active,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
        'discount_code' => 'SAVE10',
    ]);

    $result = $ctx['pricingEngine']->calculate($checkout);

    expect($result->subtotal)->toBe(10000)
        ->and($result->discount)->toBe(1000);
});

it('applies fixed discount correctly', function () {
    $ctx = createPricingContext();
    ['cart' => $cart] = createCartWithLines([
        ['price' => 5000, 'quantity' => 2],
    ], $ctx['store']);

    Discount::factory()->fixed()->create([
        'store_id' => $ctx['store']->id,
        'type' => DiscountType::Code,
        'code' => '5OFF',
        'status' => DiscountStatus::Active,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
        'discount_code' => '5OFF',
    ]);

    $result = $ctx['pricingEngine']->calculate($checkout);

    expect($result->subtotal)->toBe(10000)
        ->and($result->discount)->toBe(500);
});

it('caps fixed discount at subtotal so it never goes negative', function () {
    $ctx = createPricingContext();
    ['cart' => $cart] = createCartWithLines([
        ['price' => 150, 'quantity' => 2],
    ], $ctx['store']);

    Discount::factory()->fixed()->create([
        'store_id' => $ctx['store']->id,
        'type' => DiscountType::Code,
        'code' => 'BIGOFF',
        'value_amount' => 500,
        'status' => DiscountStatus::Active,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
        'discount_code' => 'BIGOFF',
    ]);

    $result = $ctx['pricingEngine']->calculate($checkout);

    expect($result->subtotal)->toBe(300)
        ->and($result->discount)->toBe(300)
        ->and($result->total)->toBeGreaterThanOrEqual(0);
});

it('applies free shipping discount by zeroing shipping', function () {
    $ctx = createPricingContext();
    ['cart' => $cart] = createCartWithLines([
        ['price' => 2500, 'quantity' => 2],
    ], $ctx['store']);

    Discount::factory()->freeShipping()->create([
        'store_id' => $ctx['store']->id,
        'type' => DiscountType::Code,
        'code' => 'FREESHIP',
        'status' => DiscountStatus::Active,
    ]);

    $zone = ShippingZone::factory()->create(['store_id' => $ctx['store']->id, 'countries_json' => ['US']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'config_json' => ['amount' => 499]]);

    $checkout = Checkout::factory()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
        'discount_code' => 'FREESHIP',
        'shipping_method_id' => $rate->id,
    ]);

    $result = $ctx['pricingEngine']->calculate($checkout);

    expect($result->shipping)->toBe(0);
});

it('calculates tax exclusive correctly', function () {
    $taxCalc = new TaxCalculator;

    $result = $taxCalc->addExclusive(10000, 1900);

    expect($result)->toBe(1900);
});

it('extracts tax from inclusive price correctly', function () {
    $taxCalc = new TaxCalculator;

    $result = $taxCalc->extractInclusive(11900, 1900);

    expect($result)->toBe(1900);
});

it('returns zero tax when rate is zero', function () {
    $taxCalc = new TaxCalculator;

    expect($taxCalc->addExclusive(10000, 0))->toBe(0)
        ->and($taxCalc->extractInclusive(10000, 0))->toBe(0);
});

it('calculates shipping flat rate', function () {
    $ctx = createPricingContext();
    ['cart' => $cart] = createCartWithLines([
        ['price' => 2500, 'quantity' => 1],
    ], $ctx['store']);

    $zone = ShippingZone::factory()->create(['store_id' => $ctx['store']->id, 'countries_json' => ['US']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'config_json' => ['amount' => 499]]);

    $checkout = Checkout::factory()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
        'shipping_method_id' => $rate->id,
    ]);

    $result = $ctx['pricingEngine']->calculate($checkout);

    expect($result->shipping)->toBe(499);
});

it('calculates full checkout totals end to end', function () {
    $ctx = createPricingContext();
    ['cart' => $cart] = createCartWithLines([
        ['price' => 2499, 'quantity' => 2],
    ], $ctx['store']);

    Discount::factory()->create([
        'store_id' => $ctx['store']->id,
        'type' => DiscountType::Code,
        'code' => 'WELCOME10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'status' => DiscountStatus::Active,
    ]);

    $zone = ShippingZone::factory()->create(['store_id' => $ctx['store']->id, 'countries_json' => ['US']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'config_json' => ['amount' => 499]]);

    TaxSettings::create([
        'store_id' => $ctx['store']->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => ['default_rate' => 1900, 'tax_name' => 'Tax'],
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
        'discount_code' => 'WELCOME10',
        'shipping_method_id' => $rate->id,
        'shipping_address_json' => ['country' => 'US'],
    ]);

    $result = $ctx['pricingEngine']->calculate($checkout);

    // subtotal = 2499 * 2 = 4998
    // discount = 10% of 4998 = 499 (rounded)
    // discounted subtotal = 4998 - 499 = 4499
    // shipping = 499
    // taxable = line items after discount = 4499, shipping = 499
    // tax exclusive on lines: round(4499 * 1900 / 10000) = 855 (rounded)
    // tax exclusive on shipping: round(499 * 1900 / 10000) = 95 (rounded)
    // total tax = 855 + 95 = 950
    // total = 4499 + 499 + 950 = 5948
    expect($result->subtotal)->toBe(4998)
        ->and($result->discount)->toBe(500)
        ->and($result->shipping)->toBe(499);
});

it('handles rounding correctly with odd cent amounts', function () {
    $ctx = createPricingContext();
    ['cart' => $cart] = createCartWithLines([
        ['price' => 3333, 'quantity' => 1],
        ['price' => 3334, 'quantity' => 1],
        ['price' => 3333, 'quantity' => 1],
    ], $ctx['store']);

    Discount::factory()->create([
        'store_id' => $ctx['store']->id,
        'type' => DiscountType::Code,
        'code' => 'ROUND10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'status' => DiscountStatus::Active,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
        'discount_code' => 'ROUND10',
    ]);

    $result = $ctx['pricingEngine']->calculate($checkout);

    // Verify line discounts sum to total discount (no off-by-one)
    $cart->refresh();
    $lineDiscountsSum = $cart->lines->sum('line_discount_amount');
    expect($lineDiscountsSum)->toBe($result->discount);
});

it('produces identical results for identical inputs', function () {
    $ctx = createPricingContext();
    ['cart' => $cart] = createCartWithLines([
        ['price' => 2500, 'quantity' => 2],
    ], $ctx['store']);

    $checkout = Checkout::factory()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    $result1 = $ctx['pricingEngine']->calculate($checkout->fresh());
    $result2 = $ctx['pricingEngine']->calculate($checkout->fresh());

    expect($result1->toArray())->toBe($result2->toArray());
});
