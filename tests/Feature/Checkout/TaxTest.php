<?php

use App\Models\Checkout;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\CartService;
use App\Services\CheckoutService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->checkoutService = app(CheckoutService::class);
});

/**
 * A checkout addressed in Germany with one line and an optional flat rate.
 */
function taxCheckout($test, int $price, int $quantity, ?int $flatRateAmount = null): Checkout
{
    $variant = createPurchasableVariant($test->store, $price);

    $cartService = app(CartService::class);
    $cart = $cartService->create($test->store);
    $cartService->addLine($cart, $variant->getKey(), $quantity);

    $checkout = $test->checkoutService->createFromCart($cart);
    $checkout = $test->checkoutService->setAddress($checkout, [
        'email' => 'shopper@example.test',
        'shipping_address' => validShippingAddress(),
    ]);

    if ($flatRateAmount !== null) {
        $zone = ShippingZone::factory()->for($test->store)->create(['countries_json' => ['DE']]);
        $rate = ShippingRate::factory()->for($zone, 'zone')->flatAmount($flatRateAmount)->create();
        $checkout = $test->checkoutService->setShippingMethod($checkout, $rate->getKey());
    }

    return $checkout;
}

it('calculates exclusive tax correctly at checkout', function () {
    TaxSettings::factory()->for($this->store)->rateBasisPoints(1900)->create();

    $checkout = taxCheckout($this, 2500, 2, 499);

    // Discounted subtotal + shipping = 5499; 19% of 5499 = 1044.
    expect($checkout->totals_json['tax'])->toBe(1044);
    expect($checkout->totals_json['total'])->toBe(6543);
});

it('extracts inclusive tax correctly at checkout', function () {
    TaxSettings::factory()->for($this->store)->rateBasisPoints(1900)->pricesIncludeTax()->create();

    $checkout = taxCheckout($this, 11900, 1);

    expect($checkout->totals_json['tax'])->toBe(1900);
    expect($checkout->totals_json['total'])->toBe(11900);
});

it('applies zero tax when no tax settings exist', function () {
    $checkout = taxCheckout($this, 5000, 1);

    expect($checkout->totals_json['tax'])->toBe(0);
    expect($checkout->totals_json['tax_lines'])->toBe([]);
});

it('stores tax lines in totals_json', function () {
    TaxSettings::factory()->for($this->store)->rateBasisPoints(1900)->create();

    $checkout = taxCheckout($this, 10000, 1);

    expect($checkout->totals_json['tax_lines'])->toHaveCount(1);
    expect($checkout->totals_json['tax_lines'][0])->toMatchArray([
        'name' => 'Tax',
        'rate' => 1900,
        'amount' => 1900,
    ]);
});
