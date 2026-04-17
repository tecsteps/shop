<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\ShippingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->calc = new ShippingCalculator;
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

function makeCartWithLine(Store $store, int $weightGrams = 500, bool $requiresShipping = true, int $price = 2000, int $qty = 1): Cart
{
    $product = Product::factory()->for($store)->create();
    $variant = ProductVariant::factory()->for($product)->create([
        'weight_g' => $weightGrams,
        'requires_shipping' => $requiresShipping,
        'price_amount' => $price,
    ]);

    $cart = Cart::factory()->for($store)->create();
    CartLine::factory()->for($cart)->for($variant, 'variant')->create([
        'quantity' => $qty,
        'unit_price_amount' => $price,
        'line_subtotal_amount' => $price * $qty,
        'line_total_amount' => $price * $qty,
    ]);

    return $cart->fresh('lines.variant');
}

it('matches zone by country', function (): void {
    $zone = ShippingZone::factory()->for($this->store)->create([
        'countries_json' => ['DE', 'AT'],
        'regions_json' => [],
    ]);
    ShippingRate::factory()->for($zone, 'zone')->flat(799)->create();

    $rates = $this->calc->getAvailableRates($this->store, ['country' => 'DE']);

    expect($rates)->toHaveCount(1);
});

it('matches zone by region', function (): void {
    $zone = ShippingZone::factory()->for($this->store)->create([
        'countries_json' => [],
        'regions_json' => ['US-NY'],
    ]);
    ShippingRate::factory()->for($zone, 'zone')->flat(799)->create();

    $rates = $this->calc->getAvailableRates($this->store, [
        'country' => 'US',
        'province_code' => 'NY',
    ]);

    expect($rates)->toHaveCount(1);
});

it('returns empty when no zone matches', function (): void {
    $zone = ShippingZone::factory()->for($this->store)->create([
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);
    ShippingRate::factory()->for($zone, 'zone')->flat(799)->create();

    $rates = $this->calc->getAvailableRates($this->store, ['country' => 'FR']);

    expect($rates)->toBeEmpty();
});

it('calculates flat rate', function (): void {
    $zone = ShippingZone::factory()->for($this->store)->create([
        'countries_json' => ['DE'],
    ]);
    $rate = ShippingRate::factory()->for($zone, 'zone')->flat(799)->create();
    $cart = makeCartWithLine($this->store);

    expect($this->calc->calculate($rate, $cart))->toBe(799);
});

it('calculates weight-based rate using ranges', function (): void {
    $zone = ShippingZone::factory()->for($this->store)->create([
        'countries_json' => ['DE'],
    ]);
    $rate = ShippingRate::factory()->for($zone, 'zone')->weight([
        ['min_g' => 0, 'max_g' => 1000, 'amount' => 500],
        ['min_g' => 1001, 'max_g' => 5000, 'amount' => 1000],
    ])->create();

    $lightCart = makeCartWithLine($this->store, weightGrams: 400, qty: 1);
    expect($this->calc->calculate($rate, $lightCart))->toBe(500);

    $heavyCart = makeCartWithLine($this->store, weightGrams: 1500, qty: 2);
    expect($this->calc->calculate($rate, $heavyCart))->toBe(1000);
});

it('calculates price-based rate using ranges', function (): void {
    $zone = ShippingZone::factory()->for($this->store)->create([
        'countries_json' => ['DE'],
    ]);
    $rate = ShippingRate::factory()->for($zone, 'zone')->price([
        ['min_amount' => 0, 'max_amount' => 5000, 'amount' => 799],
        ['min_amount' => 5001, 'max_amount' => PHP_INT_MAX, 'amount' => 0],
    ])->create();

    $smallCart = makeCartWithLine($this->store, price: 3000, qty: 1);
    expect($this->calc->calculate($rate, $smallCart))->toBe(799);

    $bigCart = makeCartWithLine($this->store, price: 6000, qty: 1);
    expect($this->calc->calculate($rate, $bigCart))->toBe(0);
});

it('returns zero shipping when no items require shipping', function (): void {
    $zone = ShippingZone::factory()->for($this->store)->create([
        'countries_json' => ['DE'],
    ]);
    $rate = ShippingRate::factory()->for($zone, 'zone')->flat(799)->create();
    $cart = makeCartWithLine($this->store, requiresShipping: false);

    expect($this->calc->calculate($rate, $cart))->toBe(0);
});

it('skips inactive rates in getAvailableRates', function (): void {
    $zone = ShippingZone::factory()->for($this->store)->create([
        'countries_json' => ['DE'],
    ]);
    ShippingRate::factory()->for($zone, 'zone')->flat(799)->create();
    ShippingRate::factory()->for($zone, 'zone')->flat(1299)->inactive()->create();

    $rates = $this->calc->getAvailableRates($this->store, ['country' => 'DE']);

    expect($rates)->toHaveCount(1);
});

it('returns all rates from multiple matching zones', function (): void {
    $zone1 = ShippingZone::factory()->for($this->store)->create([
        'countries_json' => ['DE'],
    ]);
    $zone2 = ShippingZone::factory()->for($this->store)->create([
        'countries_json' => ['DE', 'AT'],
    ]);
    ShippingRate::factory()->for($zone1, 'zone')->flat(499)->create();
    ShippingRate::factory()->for($zone2, 'zone')->flat(799)->create();

    $rates = $this->calc->getAvailableRates($this->store, ['country' => 'DE']);

    expect($rates)->toHaveCount(2);
});
