<?php

use App\Exceptions\InvalidDiscountException;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\DiscountService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->checkoutService = app(CheckoutService::class);
});

/**
 * A started checkout with a single line at the given subtotal.
 */
function discountCheckout($test, int $subtotal, ?string $discountCode = null): Checkout
{
    $variant = createPurchasableVariant($test->store, $subtotal);

    $cartService = app(CartService::class);
    $cart = $cartService->create($test->store);
    $cartService->addLine($cart, $variant->getKey(), 1);

    return $test->checkoutService->createFromCart($cart, discountCode: $discountCode);
}

it('applies a valid percent discount code at checkout', function () {
    Discount::factory()->for($this->store)->create(['code' => 'SAVE10', 'value_amount' => 10]);

    $checkout = discountCheckout($this, 5000, 'SAVE10');

    expect($checkout->totals_json['discount'])->toBe(500);
});

it('applies a valid fixed discount code at checkout', function () {
    Discount::factory()->for($this->store)->fixed(500)->create(['code' => '5OFF']);

    $checkout = discountCheckout($this, 5000, '5OFF');

    expect($checkout->totals_json['discount'])->toBe(500);
});

it('removes discount when code is cleared', function () {
    Discount::factory()->for($this->store)->create(['code' => 'SAVE10', 'value_amount' => 10]);

    $checkout = discountCheckout($this, 5000, 'SAVE10');
    expect($checkout->totals_json['discount'])->toBe(500);

    $checkout->forceFill(['discount_code' => null])->save();
    $this->checkoutService->recalculate($checkout);
    $checkout->refresh();

    expect($checkout->totals_json['discount'])->toBe(0);
    expect($checkout->totals_json['total'])->toBe(5000);
});

it('rejects expired discount at checkout', function () {
    Discount::factory()->for($this->store)->create([
        'code' => 'OLD20',
        'starts_at' => now()->subYear(),
        'ends_at' => now()->subDay(),
    ]);

    $checkout = discountCheckout($this, 5000);

    app(DiscountService::class)->validate('OLD20', $this->store, $checkout->cart);
})->throws(InvalidDiscountException::class);

it('increments usage count on order completion', function () {
    $discount = Discount::factory()->for($this->store)->create(['code' => 'SAVE10', 'value_amount' => 10]);
    expect($discount->usage_count)->toBe(0);

    $checkout = createPaymentSelectedCheckout($this->store, discountCode: 'SAVE10');

    $this->checkoutService->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($discount->refresh()->usage_count)->toBe(1);
});

it('handles free shipping discount at checkout', function () {
    Discount::factory()->for($this->store)->freeShipping()->create(['code' => 'FREESHIP']);
    $zone = ShippingZone::factory()->for($this->store)->create(['countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->for($zone, 'zone')->flatAmount(499)->create();

    $checkout = discountCheckout($this, 5000, 'FREESHIP');
    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'shopper@example.test',
        'shipping_address' => validShippingAddress(),
    ]);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->getKey());

    expect($checkout->totals_json['shipping'])->toBe(0);
});
