<?php

use App\Models\Checkout;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\ShippingCalculator;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->checkoutService = app(CheckoutService::class);
});

/**
 * An addressed checkout with a single line.
 */
function shippingCheckout($test, array $variantAttributes = [], int $quantity = 1): Checkout
{
    $variant = createPurchasableVariant($test->store, 2500, 100, $variantAttributes);

    $cartService = app(CartService::class);
    $cart = $cartService->create($test->store);
    $cartService->addLine($cart, $variant->getKey(), $quantity);

    $checkout = $test->checkoutService->createFromCart($cart);

    return $test->checkoutService->setAddress($checkout, [
        'email' => 'shopper@example.test',
        'shipping_address' => validShippingAddress(),
    ]);
}

it('returns available shipping rates for address', function () {
    $zone = ShippingZone::factory()->for($this->store)->create(['countries_json' => ['DE']]);
    ShippingRate::factory()->for($zone, 'zone')->flatAmount(499)->create(['name' => 'Standard Shipping']);

    $rates = app(ShippingCalculator::class)->getAvailableRates($this->store, ['country_code' => 'DE']);

    expect($rates)->toHaveCount(1);
    expect($rates->first()->name)->toBe('Standard Shipping');
    expect($rates->first()->config_json['amount'])->toBe(499);
});

it('returns empty when no zone matches address', function () {
    $zone = ShippingZone::factory()->for($this->store)->create(['countries_json' => ['DE']]);
    ShippingRate::factory()->for($zone, 'zone')->create();

    $rates = app(ShippingCalculator::class)->getAvailableRates($this->store, ['country_code' => 'FR']);

    expect($rates)->toBeEmpty();
});

it('calculates flat rate correctly', function () {
    $zone = ShippingZone::factory()->for($this->store)->create(['countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->for($zone, 'zone')->flatAmount(499)->create();

    $checkout = shippingCheckout($this);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->getKey());

    expect($checkout->totals_json['shipping'])->toBe(499);
});

it('calculates weight-based rate correctly', function () {
    $zone = ShippingZone::factory()->for($this->store)->create(['countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->for($zone, 'zone')->create([
        'type' => 'weight',
        'config_json' => [
            'ranges' => [
                ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
                ['min_g' => 501, 'max_g' => 2000, 'amount' => 899],
            ],
        ],
    ]);

    $checkout = shippingCheckout($this, ['weight_g' => 250], 3);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->getKey());

    expect($checkout->totals_json['shipping'])->toBe(899);
});

it('returns zero shipping when all items are digital', function () {
    $checkout = shippingCheckout($this, ['requires_shipping' => false]);

    $checkout = $this->checkoutService->setShippingMethod($checkout);

    expect($checkout->totals_json['shipping'])->toBe(0);
});
