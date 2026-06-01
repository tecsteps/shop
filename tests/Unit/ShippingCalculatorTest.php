<?php

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\ShippingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->calculator = new ShippingCalculator;
    $this->store = Store::factory()->create();
});

/**
 * Build a cart with one line for a variant of the given weight / shipping flag.
 */
function cartWithVariant(Store $store, int $weightG = 500, bool $requiresShipping = true, int $price = 2000, int $quantity = 1): Cart
{
    $product = Product::factory()->create(['store_id' => $store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'weight_g' => $weightG,
        'requires_shipping' => $requiresShipping,
        'price_amount' => $price,
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->id]);
    $cart->lines()->create([
        'variant_id' => $variant->id,
        'quantity' => $quantity,
        'unit_price_amount' => $price,
        'line_subtotal_amount' => $price * $quantity,
        'line_discount_amount' => 0,
        'line_total_amount' => $price * $quantity,
    ]);

    return $cart->load('lines.variant');
}

it('matches a zone by country code', function () {
    ShippingZone::factory()->for($this->store)->countries(['DE', 'AT', 'CH'])->create();

    $zone = $this->calculator->getMatchingZone($this->store, ['country' => 'DE']);

    expect($zone)->not->toBeNull();
});

it('matches a zone by region code', function () {
    ShippingZone::factory()->for($this->store)->countries([])->regions(['US-NY', 'US-CA'])->create();

    $zone = $this->calculator->getMatchingZone($this->store, ['country' => 'US', 'province_code' => 'US-NY']);

    expect($zone)->not->toBeNull();
});

it('returns empty when no zone matches the address', function () {
    ShippingZone::factory()->for($this->store)->countries(['DE'])->create();

    $rates = $this->calculator->getAvailableRates($this->store, ['country' => 'FR']);

    expect($rates)->toBeEmpty();
});

it('calculates a flat rate', function () {
    $zone = ShippingZone::factory()->for($this->store)->create();
    $rate = ShippingRate::factory()->for($zone, 'zone')->flat(499)->create();

    expect($this->calculator->calculate($rate, cartWithVariant($this->store)))->toBe(499);
});

it('calculates a weight-based rate', function () {
    $zone = ShippingZone::factory()->for($this->store)->create();
    $rate = ShippingRate::factory()->for($zone, 'zone')->weight([
        ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
        ['min_g' => 501, 'max_g' => 2000, 'amount' => 899],
    ])->create();

    expect($this->calculator->calculate($rate, cartWithVariant($this->store, weightG: 750)))->toBe(899);
});

it('calculates a price-based rate', function () {
    $zone = ShippingZone::factory()->for($this->store)->create();
    $rate = ShippingRate::factory()->for($zone, 'zone')->price([
        ['min_amount' => 0, 'max_amount' => 5000, 'amount' => 799],
        ['min_amount' => 5001, 'max_amount' => 999999, 'amount' => 399],
    ])->create();

    expect($this->calculator->calculate($rate, cartWithVariant($this->store, price: 7500)))->toBe(399);
});

it('returns zero shipping when no items require shipping', function () {
    $zone = ShippingZone::factory()->for($this->store)->create();
    $rate = ShippingRate::factory()->for($zone, 'zone')->flat(499)->create();

    expect($this->calculator->calculate($rate, cartWithVariant($this->store, requiresShipping: false)))->toBe(0);
});

it('returns rates from all matching zones for customer selection', function () {
    $zoneA = ShippingZone::factory()->for($this->store)->countries(['DE'])->create();
    ShippingRate::factory()->for($zoneA, 'zone')->flat(499)->create();

    $zoneB = ShippingZone::factory()->for($this->store)->countries(['DE'])->regions(['DE-BY'])->create();
    ShippingRate::factory()->for($zoneB, 'zone')->flat(999)->create();

    $rates = $this->calculator->getAvailableRates($this->store, ['country' => 'DE', 'province_code' => 'DE-BY']);

    expect($rates)->toHaveCount(2);
});

it('skips inactive rates', function () {
    $zone = ShippingZone::factory()->for($this->store)->countries(['DE'])->create();
    ShippingRate::factory()->for($zone, 'zone')->flat(499)->create();
    ShippingRate::factory()->for($zone, 'zone')->flat(999)->inactive()->create();

    $rates = $this->calculator->getAvailableRates($this->store, ['country' => 'DE']);

    expect($rates)->toHaveCount(1)
        ->and($rates->first()->config_json['amount'])->toBe(499);
});
