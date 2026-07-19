<?php

use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\ShippingCalculator;
use App\ValueObjects\Address;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

function shippingCalculator(): ShippingCalculator
{
    return app(ShippingCalculator::class);
}

function addressFor(string $countryCode, ?string $provinceCode = null): Address
{
    return Address::fromArray([
        'country' => $countryCode,
        'country_code' => $countryCode,
        'province_code' => $provinceCode,
    ]);
}

/**
 * Build an in-memory cart with lines carrying variant weight/shipping data.
 *
 * @param  array<int, array<string, mixed>>  $lines
 */
function shippingCart(array $lines): Cart
{
    $cart = new Cart(['currency' => 'USD']);

    $cart->setRelation('lines', collect($lines)->map(function (array $data): CartLine {
        $quantity = $data['quantity'] ?? 1;
        $unitPrice = $data['unit_price_amount'] ?? 0;

        $line = new CartLine([
            'quantity' => $quantity,
            'unit_price_amount' => $unitPrice,
            'line_subtotal_amount' => $unitPrice * $quantity,
            'line_discount_amount' => 0,
            'line_total_amount' => $unitPrice * $quantity,
        ]);

        $line->setRelation('variant', new ProductVariant([
            'weight_g' => $data['weight_g'] ?? null,
            'requires_shipping' => $data['requires_shipping'] ?? true,
        ]));

        return $line;
    }));

    return $cart;
}

/**
 * Build an in-memory shipping rate.
 *
 * @param  array<string, mixed>  $config
 */
function shippingRate(ShippingRateType $type, array $config, bool $active = true): ShippingRate
{
    return new ShippingRate([
        'name' => 'Test Rate',
        'type' => $type,
        'config_json' => $config,
        'is_active' => $active,
    ]);
}

test('matches a zone by country code', function () {
    $store = $this->createStore();
    $zone = ShippingZone::factory()->create([
        'store_id' => $store->id,
        'countries_json' => ['DE', 'AT', 'CH'],
        'regions_json' => [],
    ]);

    expect(shippingCalculator()->getMatchingZone($store, addressFor('DE'))?->id)->toBe($zone->id);
});

test('matches a zone by region code', function () {
    $store = $this->createStore();
    $zone = ShippingZone::factory()->create([
        'store_id' => $store->id,
        'countries_json' => ['US'],
        'regions_json' => ['US-NY', 'US-CA'],
    ]);

    expect(shippingCalculator()->getMatchingZone($store, addressFor('US', 'US-NY'))?->id)->toBe($zone->id);
});

test('returns empty when no zone matches the address', function () {
    $store = $this->createStore();
    ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);

    $calculator = shippingCalculator();
    $cart = shippingCart([['unit_price_amount' => 1000]]);

    expect($calculator->getMatchingZone($store, addressFor('FR')))->toBeNull()
        ->and($calculator->getAvailableRates($store, addressFor('FR'), $cart))->toBeEmpty();
});

test('calculates a flat rate', function () {
    $rate = shippingRate(ShippingRateType::Flat, ['amount' => 499]);

    expect(shippingCalculator()->calculate($rate, shippingCart([['unit_price_amount' => 1000]])))->toBe(499);
});

test('calculates a weight-based rate', function () {
    $rate = shippingRate(ShippingRateType::Weight, [
        'ranges' => [
            ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
            ['min_g' => 501, 'max_g' => 2000, 'amount' => 899],
        ],
    ]);

    $cart = shippingCart([['weight_g' => 250, 'quantity' => 3]]); // 750g

    expect(shippingCalculator()->calculate($rate, $cart))->toBe(899);
});

test('calculates a price-based rate', function () {
    $rate = shippingRate(ShippingRateType::Price, [
        'ranges' => [
            ['min_amount' => 0, 'max_amount' => 5000, 'amount' => 799],
            ['min_amount' => 5001, 'max_amount' => 999999, 'amount' => 399],
        ],
    ]);

    $cart = shippingCart([['unit_price_amount' => 2500, 'quantity' => 3]]); // 7500

    expect(shippingCalculator()->calculate($rate, $cart))->toBe(399);
});

test('returns zero shipping when no items require shipping', function () {
    $store = $this->createStore();
    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    ShippingRate::factory()->flat(499)->create(['zone_id' => $zone->id]);

    $cart = shippingCart([['unit_price_amount' => 1000, 'requires_shipping' => false]]);

    expect(shippingCalculator()->getAvailableRates($store, addressFor('DE'), $cart))->toBeEmpty();
});

test('returns the rates of the winning zone when multiple zones match', function () {
    $store = $this->createStore();

    // Both zones match country DE; the lowest zone id wins the tie-break.
    $firstZone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    $secondZone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE', 'AT']]);

    ShippingRate::factory()->flat(499)->create(['zone_id' => $firstZone->id, 'name' => 'First Zone Rate']);
    ShippingRate::factory()->flat(899)->create(['zone_id' => $secondZone->id, 'name' => 'Second Zone Rate']);

    $matched = shippingCalculator()->getMatchingZone($store, addressFor('DE'));
    $rates = shippingCalculator()->getAvailableRates($store, addressFor('DE'), shippingCart([['unit_price_amount' => 1000]]));

    expect($matched->id)->toBe($firstZone->id)
        ->and($rates)->toHaveCount(1)
        ->and($rates->first()->name)->toBe('First Zone Rate')
        ->and($rates->first()->amount)->toBe(499);
});

test('skips inactive rates', function () {
    $store = $this->createStore();
    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    ShippingRate::factory()->flat(499)->inactive()->create(['zone_id' => $zone->id]);
    ShippingRate::factory()->flat(899)->create(['zone_id' => $zone->id, 'name' => 'Active Rate']);

    $rates = shippingCalculator()->getAvailableRates($store, addressFor('DE'), shippingCart([['unit_price_amount' => 1000]]));

    expect($rates)->toHaveCount(1)
        ->and($rates->first()->name)->toBe('Active Rate');
});
