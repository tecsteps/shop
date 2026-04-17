<?php

use App\Models\Cart;
use App\Models\CartLine;
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
use App\Services\TaxCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);

    $this->engine = new PricingEngine(
        new DiscountService,
        new ShippingCalculator,
        new TaxCalculator,
    );

    $this->cart = Cart::factory()->for($this->store)->create(['currency' => 'EUR']);
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

function addLine(Cart $cart, int $price, int $qty = 1, int $weight = 250, bool $requiresShipping = true): CartLine
{
    $variant = ProductVariant::factory()
        ->for(Product::factory()->for($cart->store))
        ->create([
            'price_amount' => $price,
            'weight_g' => $weight,
            'requires_shipping' => $requiresShipping,
        ]);

    return CartLine::factory()->for($cart)->for($variant, 'variant')->create([
        'quantity' => $qty,
        'unit_price_amount' => $price,
        'line_subtotal_amount' => $price * $qty,
        'line_total_amount' => $price * $qty,
    ]);
}

it('calculates subtotal from line items', function (): void {
    addLine($this->cart, 2499, 2);
    addLine($this->cart, 7999, 1);

    $checkout = Checkout::factory()->for($this->store)->for($this->cart)->create();

    $result = $this->engine->calculate($checkout);

    expect($result->subtotal)->toBe(12997)
        ->and($result->total)->toBe(12997);
});

it('applies percent discount', function (): void {
    addLine($this->cart, 10000, 1);
    Discount::factory()->for($this->store)->percent10()->create(['code' => 'P10']);

    $checkout = Checkout::factory()->for($this->store)->for($this->cart)->create([
        'discount_code' => 'P10',
    ]);

    $result = $this->engine->calculate($checkout);

    expect($result->subtotal)->toBe(10000)
        ->and($result->discount)->toBe(1000)
        ->and($result->total)->toBe(9000);
});

it('applies fixed discount', function (): void {
    addLine($this->cart, 10000, 1);
    Discount::factory()->for($this->store)->fixed500()->create(['code' => 'F500']);

    $checkout = Checkout::factory()->for($this->store)->for($this->cart)->create([
        'discount_code' => 'F500',
    ]);

    $result = $this->engine->calculate($checkout);

    expect($result->discount)->toBe(500)
        ->and($result->total)->toBe(9500);
});

it('caps fixed discount at subtotal', function (): void {
    addLine($this->cart, 300, 1);
    Discount::factory()->for($this->store)->fixed500()->create(['code' => 'F500B']);

    $checkout = Checkout::factory()->for($this->store)->for($this->cart)->create([
        'discount_code' => 'F500B',
    ]);

    $result = $this->engine->calculate($checkout);

    expect($result->discount)->toBe(300)
        ->and($result->total)->toBe(0);
});

it('applies free shipping discount', function (): void {
    addLine($this->cart, 5000, 1);
    Discount::factory()->for($this->store)->freeShipping()->create(['code' => 'FREESHIP']);

    $zone = ShippingZone::factory()->for($this->store)->create(['countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->for($zone, 'zone')->flat(499)->create();

    $checkout = Checkout::factory()->for($this->store)->for($this->cart)->create([
        'discount_code' => 'FREESHIP',
        'shipping_method_id' => $rate->id,
    ]);

    $result = $this->engine->calculate($checkout);

    expect($result->shipping)->toBe(0)
        ->and($result->freeShippingApplied)->toBeTrue()
        ->and($result->total)->toBe(5000);
});

it('calculates tax exclusive', function (): void {
    addLine($this->cart, 10000, 1);
    TaxSettings::factory()->for($this->store)->create();

    $checkout = Checkout::factory()->for($this->store)->for($this->cart)->create();

    $result = $this->engine->calculate($checkout);

    expect($result->subtotal)->toBe(10000)
        ->and($result->taxTotal)->toBe(1900)
        ->and($result->total)->toBe(11900);
});

it('extracts tax when prices include tax', function (): void {
    addLine($this->cart, 11900, 1);
    TaxSettings::factory()->for($this->store)->pricesInclude()->create();

    $checkout = Checkout::factory()->for($this->store)->for($this->cart)->create();

    $result = $this->engine->calculate($checkout);

    expect($result->subtotal)->toBe(11900)
        ->and($result->taxTotal)->toBe(1900)
        ->and($result->total)->toBe(11900);
});

it('returns zero tax when rate is zero', function (): void {
    addLine($this->cart, 10000, 1);
    TaxSettings::factory()->for($this->store)->create([
        'config_json' => ['name' => 'Tax', 'rate_basis_points' => 0],
    ]);

    $checkout = Checkout::factory()->for($this->store)->for($this->cart)->create();

    $result = $this->engine->calculate($checkout);

    expect($result->taxTotal)->toBe(0)
        ->and($result->total)->toBe(10000);
});

it('calculates flat shipping', function (): void {
    addLine($this->cart, 2000, 1);

    $zone = ShippingZone::factory()->for($this->store)->create(['countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->for($zone, 'zone')->flat(499)->create();

    $checkout = Checkout::factory()->for($this->store)->for($this->cart)->create([
        'shipping_method_id' => $rate->id,
    ]);

    $result = $this->engine->calculate($checkout);

    expect($result->shipping)->toBe(499)
        ->and($result->total)->toBe(2499);
});

it('calculates full checkout end-to-end', function (): void {
    addLine($this->cart, 10000, 1);
    Discount::factory()->for($this->store)->percent10()->create(['code' => 'P10']);
    TaxSettings::factory()->for($this->store)->create();

    $zone = ShippingZone::factory()->for($this->store)->create(['countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->for($zone, 'zone')->flat(499)->create();

    $checkout = Checkout::factory()->for($this->store)->for($this->cart)->create([
        'discount_code' => 'P10',
        'shipping_method_id' => $rate->id,
    ]);

    $result = $this->engine->calculate($checkout);

    expect($result->subtotal)->toBe(10000)
        ->and($result->discount)->toBe(1000)
        ->and($result->shipping)->toBe(499)
        ->and($result->taxTotal)->toBe(1805)
        ->and($result->total)->toBe(11304);
});
