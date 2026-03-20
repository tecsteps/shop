<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\CheckoutService;
use App\Services\PricingEngine;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->checkoutService = app(CheckoutService::class);
    $this->pricingEngine = app(PricingEngine::class);

    TaxSettings::factory()->create(['store_id' => $this->store->id, 'rate' => 0, 'is_active' => false]);

    $this->product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'price_amount' => 5000,
        'requires_shipping' => true,
    ]);

    $this->cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $this->cart->id,
        'variant_id' => $this->variant->id,
        'quantity' => 1,
        'unit_price_amount' => 5000,
        'line_subtotal_amount' => 5000,
        'line_total_amount' => 5000,
    ]);
});

it('includes flat shipping in checkout totals', function () {
    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'config_json' => ['amount' => 499],
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => 'addressed',
        'shipping_method_id' => $rate->id,
        'shipping_address_json' => ['country' => 'DE'],
    ]);

    $result = $this->pricingEngine->calculate($checkout);

    expect($result->shipping)->toBe(499)
        ->and($result->total)->toBe(5499);
});

it('returns zero shipping when no shipping method selected', function () {
    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => 'addressed',
        'shipping_method_id' => null,
    ]);

    $result = $this->pricingEngine->calculate($checkout);

    expect($result->shipping)->toBe(0);
});

it('validates shipping rate belongs to matching zone', function () {
    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['US'],
    ]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'config_json' => ['amount' => 999],
    ]);

    $checkout = $this->checkoutService->createFromCart($this->store, $this->cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country' => 'DE',
            'postal_code' => '10115',
        ],
    ]);

    // Rate belongs to US zone but address is DE - should be rejected
    expect(fn () => $this->checkoutService->setShippingMethod($checkout, $rate->id))
        ->toThrow(\App\Exceptions\InvalidCheckoutTransitionException::class);
});

it('applies free shipping discount overriding rate amount', function () {
    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'config_json' => ['amount' => 499],
    ]);

    \App\Models\Discount::factory()->freeShipping()->create([
        'store_id' => $this->store->id,
        'code' => 'FREESHIP',
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => 'addressed',
        'discount_code' => 'FREESHIP',
        'shipping_method_id' => $rate->id,
        'shipping_address_json' => ['country' => 'DE'],
    ]);

    $result = $this->pricingEngine->calculate($checkout);

    expect($result->shipping)->toBe(0)
        ->and($result->subtotal)->toBe(5000)
        ->and($result->total)->toBe(5000);
});

it('updates totals when switching shipping methods', function () {
    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);
    $standard = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'name' => 'Standard',
        'config_json' => ['amount' => 499],
    ]);
    $express = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'name' => 'Express',
        'config_json' => ['amount' => 999],
    ]);

    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => 'addressed',
        'shipping_method_id' => $standard->id,
        'shipping_address_json' => ['country' => 'DE'],
    ]);

    $result1 = $this->pricingEngine->calculate($checkout);

    $checkout->update(['shipping_method_id' => $express->id]);
    $result2 = $this->pricingEngine->calculate($checkout->fresh());

    expect($result1->shipping)->toBe(499)
        ->and($result2->shipping)->toBe(999)
        ->and($result2->total)->toBe($result1->total + 500);
});
