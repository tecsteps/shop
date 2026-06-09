<?php

use App\Models\Checkout;
use App\Models\Discount;
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
 * Drive a checkout through address + shipping with one line of the given
 * price and quantity.
 */
function pricingCheckout($test, int $price, int $quantity, ?int $rateId = null, array $variantAttributes = []): Checkout
{
    $variant = createPurchasableVariant($test->store, $price, 100, $variantAttributes);

    $cartService = app(CartService::class);
    $cart = $cartService->create($test->store);
    $cartService->addLine($cart, $variant->getKey(), $quantity);

    $checkout = $test->checkoutService->createFromCart($cart);
    $checkout = $test->checkoutService->setAddress($checkout, [
        'email' => 'shopper@example.test',
        'shipping_address' => validShippingAddress(),
    ]);

    if ($rateId !== null) {
        $checkout = $test->checkoutService->setShippingMethod($checkout, $rateId);
    }

    return $checkout;
}

it('calculates correct totals for a simple checkout', function () {
    TaxSettings::factory()->for($this->store)->rateBasisPoints(1900)->create();
    $zone = ShippingZone::factory()->for($this->store)->create(['countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->for($zone, 'zone')->flatAmount(499)->create();

    $checkout = pricingCheckout($this, 2500, 2, $rate->getKey());

    expect($checkout->totals_json['subtotal'])->toBe(5000);
    expect($checkout->totals_json['shipping'])->toBe(499);
    expect($checkout->totals_json['tax'])->toBe(1044);
    expect($checkout->totals_json['total'])->toBe(6543);
});

it('applies discount code and recalculates', function () {
    Discount::factory()->for($this->store)->create(['code' => 'SAVE10', 'value_amount' => 10]);

    $checkout = pricingCheckout($this, 10000, 1);

    $checkout->forceFill(['discount_code' => 'SAVE10'])->save();
    $this->checkoutService->recalculate($checkout);
    $checkout->refresh();

    expect($checkout->totals_json['discount'])->toBe(1000);
    expect($checkout->totals_json['subtotal'] - $checkout->totals_json['discount'])->toBe(9000);
});

it('stores pricing snapshot in totals_json', function () {
    TaxSettings::factory()->for($this->store)->rateBasisPoints(1900)->create();

    $checkout = pricingCheckout($this, 2500, 2);

    expect($checkout->totals_json)
        ->toHaveKeys(['subtotal', 'discount', 'shipping', 'tax_lines', 'tax', 'total', 'currency']);
});

it('recalculates on shipping method change', function () {
    $zone = ShippingZone::factory()->for($this->store)->create(['countries_json' => ['DE']]);
    $flatRate = ShippingRate::factory()->for($zone, 'zone')->flatAmount(499)->create();
    $weightRate = ShippingRate::factory()->for($zone, 'zone')->create([
        'type' => 'weight',
        'config_json' => [
            'ranges' => [
                ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
                ['min_g' => 501, 'max_g' => 2000, 'amount' => 899],
            ],
        ],
    ]);

    $checkout = pricingCheckout($this, 2500, 3, $flatRate->getKey(), ['weight_g' => 250]);

    expect($checkout->totals_json['shipping'])->toBe(499);

    $checkout = $this->checkoutService->setShippingMethod($checkout, $weightRate->getKey());

    expect($checkout->totals_json['shipping'])->toBe(899);
});

it('handles prices-include-tax correctly', function () {
    TaxSettings::factory()->for($this->store)->rateBasisPoints(1900)->pricesIncludeTax()->create();

    $checkout = pricingCheckout($this, 11900, 1);

    expect($checkout->totals_json['tax'])->toBe(1900);
    expect($checkout->totals_json['total'])->toBe(11900);
    expect($checkout->totals_json['total'] - $checkout->totals_json['tax'])->toBe(10000);
});
