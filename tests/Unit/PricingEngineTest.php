<?php

use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Services\DiscountService;
use App\Services\PricingEngine;
use App\Services\ShippingCalculator;
use App\Services\Tax\ManualTaxProvider;
use App\Services\Tax\StripeTaxProvider;
use App\Services\TaxCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $manual = new ManualTaxProvider;
    $this->engine = new PricingEngine(
        new DiscountService,
        new ShippingCalculator,
        new TaxCalculator($manual, new StripeTaxProvider($manual)),
    );
    $this->store = Store::factory()->create();
});

/**
 * Build a checkout for a cart with the given [price, quantity] lines.
 *
 * @param  list<array{0: int, 1: int}>  $lines
 */
function checkoutWithLines(Store $store, array $lines, bool $requiresShipping = true): Checkout
{
    $cart = Cart::factory()->create(['store_id' => $store->id, 'currency' => 'USD']);

    foreach ($lines as [$price, $quantity]) {
        $product = Product::factory()->create(['store_id' => $store->id, 'status' => 'active']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_amount' => $price,
            'requires_shipping' => $requiresShipping,
            'weight_g' => 500,
        ]);
        $cart->lines()->create([
            'variant_id' => $variant->id,
            'quantity' => $quantity,
            'unit_price_amount' => $price,
            'line_subtotal_amount' => $price * $quantity,
            'line_discount_amount' => 0,
            'line_total_amount' => $price * $quantity,
        ]);
    }

    return Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'shipping_address_json' => ['country' => 'DE', 'province_code' => null],
    ]);
}

it('calculates subtotal from line items', function () {
    $checkout = checkoutWithLines($this->store, [[2499, 2], [7999, 1]]);

    expect($this->engine->calculate($checkout)->subtotal)->toBe(12997);
});

it('calculates subtotal for a single line', function () {
    $checkout = checkoutWithLines($this->store, [[1500, 3]]);

    expect($this->engine->calculate($checkout)->subtotal)->toBe(4500);
});

it('returns zero subtotal for empty cart', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $checkout = Checkout::factory()->create(['store_id' => $this->store->id, 'cart_id' => $cart->id]);

    expect($this->engine->calculate($checkout)->subtotal)->toBe(0);
});

it('applies percent discount correctly', function () {
    Discount::factory()->for($this->store)->percent(10, 'TEN')->create();
    $checkout = checkoutWithLines($this->store, [[10000, 1]]);
    $checkout->update(['discount_code' => 'TEN']);

    $result = $this->engine->calculate($checkout);

    expect($result->discount)->toBe(1000)
        ->and($result->discountedSubtotal())->toBe(9000);
});

it('applies fixed discount correctly', function () {
    Discount::factory()->for($this->store)->fixed(500, 'FIVE')->create();
    $checkout = checkoutWithLines($this->store, [[10000, 1]]);
    $checkout->update(['discount_code' => 'FIVE']);

    $result = $this->engine->calculate($checkout);

    expect($result->discount)->toBe(500)
        ->and($result->discountedSubtotal())->toBe(9500);
});

it('caps fixed discount at subtotal so it never goes negative', function () {
    Discount::factory()->for($this->store)->fixed(500, 'BIG')->create();
    $checkout = checkoutWithLines($this->store, [[300, 1]]);
    $checkout->update(['discount_code' => 'BIG']);

    $result = $this->engine->calculate($checkout);

    expect($result->discount)->toBe(300)
        ->and($result->discountedSubtotal())->toBe(0);
});

it('applies free shipping discount by zeroing shipping', function () {
    Discount::factory()->for($this->store)->freeShipping('FREESHIP')->create();
    $zone = ShippingZone::factory()->for($this->store)->countries(['DE'])->create();
    $rate = ShippingRate::factory()->for($zone, 'zone')->flat(499)->create();

    $checkout = checkoutWithLines($this->store, [[5000, 1]]);
    $checkout->update(['discount_code' => 'FREESHIP', 'shipping_method_id' => $rate->id]);

    expect($this->engine->calculate($checkout)->shipping)->toBe(0);
});

it('calculates tax exclusive correctly', function () {
    TaxSettings::factory()->for($this->store)->rate(1900)->create();
    $checkout = checkoutWithLines($this->store, [[10000, 1]], requiresShipping: false);

    $result = $this->engine->calculate($checkout);

    expect($result->taxTotal)->toBe(1900)
        ->and($result->total)->toBe(11900);
});

it('extracts tax from inclusive price correctly', function () {
    TaxSettings::factory()->for($this->store)->rate(1900)->inclusive()->create();
    $checkout = checkoutWithLines($this->store, [[11900, 1]], requiresShipping: false);

    $result = $this->engine->calculate($checkout);

    // Inclusive mode: subtotal is the gross line sum, tax is extracted from it,
    // and the net component equals subtotal - tax. Total stays gross.
    expect($result->taxTotal)->toBe(1900)
        ->and($result->subtotal)->toBe(11900)
        ->and($result->subtotal - $result->taxTotal)->toBe(10000)
        ->and($result->total)->toBe(11900);
});

it('returns zero tax when rate is zero', function () {
    TaxSettings::factory()->for($this->store)->rate(0)->create();
    $checkout = checkoutWithLines($this->store, [[10000, 1]], requiresShipping: false);

    expect($this->engine->calculate($checkout)->taxTotal)->toBe(0);
});

it('calculates shipping flat rate', function () {
    $zone = ShippingZone::factory()->for($this->store)->countries(['DE'])->create();
    $rate = ShippingRate::factory()->for($zone, 'zone')->flat(499)->create();

    $checkout = checkoutWithLines($this->store, [[5000, 1]]);
    $checkout->update(['shipping_method_id' => $rate->id]);

    expect($this->engine->calculate($checkout)->shipping)->toBe(499);
});

it('calculates full checkout totals end to end', function () {
    Discount::factory()->for($this->store)->percent(10, 'WELCOME10')->create();
    TaxSettings::factory()->for($this->store)->rate(1900)->inclusive()->create();
    $zone = ShippingZone::factory()->for($this->store)->countries(['DE'])->create();
    $rate = ShippingRate::factory()->for($zone, 'zone')->flat(499)->create();

    // 2 lines x 2499 = 4998; discount 10% = 499 (intdiv 4998*10/100); discounted 4499;
    // shipping 499; tax extracted from gross (4499 + 499 = 4998).
    $checkout = checkoutWithLines($this->store, [[2499, 1], [2499, 1]]);
    $checkout->update(['discount_code' => 'WELCOME10', 'shipping_method_id' => $rate->id]);

    $result = $this->engine->calculate($checkout);

    expect($result->subtotal)->toBe(4998)
        ->and($result->discount)->toBe(499)
        ->and($result->discountedSubtotal())->toBe(4499)
        ->and($result->shipping)->toBe(499)
        // inclusive: total is discounted subtotal + shipping (tax already inside).
        ->and($result->total)->toBe(4998);
});

it('handles rounding correctly with odd cent amounts', function () {
    Discount::factory()->for($this->store)->percent(10, 'ODD')->create();
    // 3 lines that create fractional allocations: 3333 + 3333 + 3334 = 10000.
    $checkout = checkoutWithLines($this->store, [[3333, 1], [3333, 1], [3334, 1]], requiresShipping: false);
    $checkout->update(['discount_code' => 'ODD']);

    $result = $this->engine->calculate($checkout);
    $totals = $checkout->fresh()->totals_json;

    expect($result->discount)->toBe(1000)
        ->and($totals['discount'])->toBe(1000);
});

it('produces identical results for identical inputs', function () {
    TaxSettings::factory()->for($this->store)->rate(1900)->create();
    $checkout = checkoutWithLines($this->store, [[2500, 2]], requiresShipping: false);

    $first = $this->engine->calculate($checkout);
    $second = $this->engine->calculate($checkout->fresh());

    expect($first->toArray())->toBe($second->toArray());
});
