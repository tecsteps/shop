<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\PricingEngine;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->pricingEngine = app(PricingEngine::class);

    $this->product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'price_amount' => 5000,
    ]);

    $this->cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $this->cart->id,
        'variant_id' => $this->variant->id,
        'quantity' => 2,
        'unit_price_amount' => 5000,
        'line_subtotal_amount' => 10000,
        'line_total_amount' => 10000,
    ]);
});

it('adds exclusive tax to total', function () {
    TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'rate' => 1900,
        'prices_include_tax' => false,
    ]);

    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'config_json' => ['amount' => 500],
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => 'addressed',
        'shipping_method_id' => $rate->id,
        'shipping_address_json' => ['country' => 'DE'],
    ]);

    $result = $this->pricingEngine->calculate($checkout);

    // Taxable = 10000 + 500 = 10500, tax = round(10500 * 1900 / 10000) = 1995
    expect($result->taxTotal)->toBe(1995)
        ->and($result->total)->toBe(10000 + 500 + 1995);
});

it('extracts inclusive tax without increasing total', function () {
    TaxSettings::factory()->inclusive()->create([
        'store_id' => $this->store->id,
        'rate' => 1900,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => 'addressed',
    ]);

    $result = $this->pricingEngine->calculate($checkout);

    // Inclusive: total = subtotal (tax is extracted, not added)
    expect($result->subtotal)->toBe(10000)
        ->and($result->taxTotal)->toBeGreaterThan(0)
        ->and($result->total)->toBe(10000);
});

it('returns zero tax when tax settings are inactive', function () {
    TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'rate' => 1900,
        'is_active' => false,
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => 'addressed',
    ]);

    $result = $this->pricingEngine->calculate($checkout);

    expect($result->taxTotal)->toBe(0)
        ->and($result->total)->toBe(10000);
});

it('stores tax lines in totals json', function () {
    TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'rate' => 1900,
        'prices_include_tax' => false,
        'tax_name' => 'VAT',
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => 'addressed',
    ]);

    $result = $this->pricingEngine->calculate($checkout);
    $resultArray = $result->toArray();

    expect($resultArray['tax_lines'])->toHaveCount(1)
        ->and($resultArray['tax_lines'][0]['name'])->toBe('VAT')
        ->and($resultArray['tax_lines'][0]['rate'])->toBe(1900)
        ->and($resultArray['tax_lines'][0]['amount'])->toBeGreaterThan(0);
});
