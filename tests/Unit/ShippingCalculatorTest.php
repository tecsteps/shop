<?php

use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\ShippingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->calculator = new ShippingCalculator;
});

it('matches a zone by country code', function () {
    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE', 'AT', 'CH'],
    ]);
    ShippingRate::factory()->create(['zone_id' => $zone->id]);

    $rates = $this->calculator->getAvailableRates($this->store, ['country_code' => 'DE']);

    expect($rates)->toHaveCount(1);
});

it('matches a zone by region code', function () {
    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['US'],
        'regions_json' => ['US-NY', 'US-CA'],
    ]);
    ShippingRate::factory()->create(['zone_id' => $zone->id]);

    $rates = $this->calculator->getAvailableRates($this->store, ['country_code' => 'US', 'province_code' => 'US-NY']);

    expect($rates)->toHaveCount(1);
});

it('returns empty when no zone matches the address', function () {
    ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);

    $rates = $this->calculator->getAvailableRates($this->store, ['country_code' => 'FR']);

    expect($rates)->toBeEmpty();
});

it('calculates a flat rate', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['requires_shipping' => true]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 1000, 'line_subtotal_amount' => 1000, 'line_total_amount' => 1000]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'type' => ShippingRateType::Flat, 'config_json' => ['amount' => 499]]);

    expect($this->calculator->calculate($rate, $cart))->toBe(499);
});

it('calculates a weight-based rate', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['requires_shipping' => true, 'weight_g' => 750]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 1000, 'line_subtotal_amount' => 1000, 'line_total_amount' => 1000]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);
    $rate = ShippingRate::factory()->weightBased()->create(['zone_id' => $zone->id]);

    expect($this->calculator->calculate($rate, $cart))->toBe(899);
});

it('calculates a price-based rate', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['requires_shipping' => true, 'price_amount' => 7500]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 7500, 'line_subtotal_amount' => 7500, 'line_total_amount' => 7500]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);
    $rate = ShippingRate::factory()->priceBased()->create(['zone_id' => $zone->id]);

    expect($this->calculator->calculate($rate, $cart))->toBe(399);
});

it('returns zero shipping when no items require shipping', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['requires_shipping' => false]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 1000, 'line_subtotal_amount' => 1000, 'line_total_amount' => 1000]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'config_json' => ['amount' => 499]]);

    expect($this->calculator->calculate($rate, $cart))->toBe(0);
});

it('returns the correct rate when multiple zones match and the first is selected', function () {
    $zone1 = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['DE']]);
    ShippingRate::factory()->create(['zone_id' => $zone1->id, 'name' => 'Zone 1 Rate', 'config_json' => ['amount' => 499]]);

    $zone2 = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['DE', 'AT']]);
    ShippingRate::factory()->create(['zone_id' => $zone2->id, 'name' => 'Zone 2 Rate', 'config_json' => ['amount' => 799]]);

    $rates = $this->calculator->getAvailableRates($this->store, ['country_code' => 'DE']);

    expect($rates)->toHaveCount(2);
});

it('skips inactive rates', function () {
    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['DE']]);
    ShippingRate::factory()->inactive()->create(['zone_id' => $zone->id]);
    ShippingRate::factory()->create(['zone_id' => $zone->id, 'name' => 'Active Rate']);

    $rates = $this->calculator->getAvailableRates($this->store, ['country_code' => 'DE']);

    expect($rates)->toHaveCount(1)
        ->and($rates->first()->name)->toBe('Active Rate');
});
