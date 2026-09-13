<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Enums\PaymentMethod;
use App\Models\Discount;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function discountCheckoutContext(): array
{
    ['store' => $store] = test()->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store, [['price' => 2500, 'qty' => 2]]);
    $rate = CommerceTestHelper::flatShippingRate($store, ['DE'], 500);

    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->createFromCart($cart, 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $rate->id);

    return ['store' => $store, 'cart' => $cart, 'rate' => $rate, 'checkout' => $checkout];
}

function makeDiscount($store, array $overrides = []): Discount
{
    return Discount::factory()->create(array_merge([
        'store_id' => $store->id,
        'type' => DiscountType::Code,
        'code' => 'SAVE10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
        'usage_limit' => null,
        'usage_count' => 0,
        'rules_json' => [],
        'status' => DiscountStatus::Active,
    ], $overrides));
}

it('applies a valid percent discount code at checkout', function () {
    $ctx = discountCheckoutContext();
    makeDiscount($ctx['store']);

    $checkout = app(CheckoutService::class)->applyDiscount($ctx['checkout'], 'SAVE10', app(DiscountService::class));

    expect($checkout->discount_code)->toBe('SAVE10');
    expect($checkout->totals_json['discount'])->toBe(500);
});

it('applies a valid fixed discount code at checkout', function () {
    $ctx = discountCheckoutContext();
    makeDiscount($ctx['store'], ['code' => '5OFF', 'value_type' => DiscountValueType::Fixed, 'value_amount' => 500]);

    $checkout = app(CheckoutService::class)->applyDiscount($ctx['checkout'], '5OFF', app(DiscountService::class));

    expect($checkout->totals_json['discount'])->toBe(500);
});

it('removes discount when code is cleared', function () {
    $ctx = discountCheckoutContext();
    makeDiscount($ctx['store']);

    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->applyDiscount($ctx['checkout'], 'SAVE10', app(DiscountService::class));
    expect($checkout->totals_json['discount'])->toBe(500);

    $checkout = $checkouts->removeDiscount($checkout);

    expect($checkout->discount_code)->toBeNull();
    expect($checkout->totals_json['discount'])->toBe(0);
});

it('rejects expired discount at checkout', function () {
    $ctx = discountCheckoutContext();
    makeDiscount($ctx['store'], ['ends_at' => now()->subDay()]);

    app(CheckoutService::class)->applyDiscount($ctx['checkout'], 'SAVE10', app(DiscountService::class));
})->throws(App\Exceptions\InvalidDiscountException::class);

it('increments usage count on order completion', function () {
    $ctx = discountCheckoutContext();
    $discount = makeDiscount($ctx['store'], ['usage_count' => 5]);

    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->applyDiscount($ctx['checkout'], 'SAVE10', app(DiscountService::class));
    $checkout = $checkouts->selectPaymentMethod($checkout, PaymentMethod::CreditCard);
    $checkouts->pay($checkout, PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);

    expect($discount->refresh()->usage_count)->toBe(6);
});

it('handles free shipping discount at checkout', function () {
    $ctx = discountCheckoutContext();
    makeDiscount($ctx['store'], ['code' => 'FREESHIP', 'value_type' => DiscountValueType::FreeShipping, 'value_amount' => 0]);

    $checkout = app(CheckoutService::class)->applyDiscount($ctx['checkout'], 'FREESHIP', app(DiscountService::class));

    expect($checkout->totals_json['shipping'])->toBe(0);
    expect($checkout->totals_json['discount'])->toBe(0);
});
