<?php

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Models\TaxSetting;
use App\Services\CheckoutService;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function stateContext(): array
{
    ['store' => $store, 'domain' => $domain] = test()->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);
    $rate = CommerceTestHelper::flatShippingRate($store);

    return ['store' => $store, 'domain' => $domain, 'cart' => $cart, 'rate' => $rate];
}

it('transitions from started to addressed with valid address', function () {
    $ctx = stateContext();
    $checkout = app(CheckoutService::class)->createFromCart($ctx['cart'], 'customer@example.com');

    $checkout = app(CheckoutService::class)->setAddress($checkout, CommerceTestHelper::address());

    expect($checkout->status)->toBe(CheckoutStatus::Addressed);
    expect($checkout->billing_address_json['city'])->toBe('Berlin');
});

it('rejects address transition with missing required fields', function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    Illuminate\Support\Facades\Cache::flush();

    $ctx = stateContext();
    $checkout = app(CheckoutService::class)->createFromCart($ctx['cart'], 'customer@example.com');

    $address = CommerceTestHelper::address();
    unset($address['city']);

    $response = $this->putJson(
        CommerceTestHelper::storefrontUrl($ctx['domain'], "/api/storefront/v1/checkouts/{$checkout->id}/address"),
        ['shipping_address' => $address]
    );

    $response->assertUnprocessable();
    expect($checkout->refresh()->status)->toBe(CheckoutStatus::Started);
});

it('transitions from addressed to shipping selected', function () {
    $ctx = stateContext();
    $checkouts = app(CheckoutService::class);

    $checkout = $checkouts->createFromCart($ctx['cart'], 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $ctx['rate']->id);

    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected);
    expect($checkout->shipping_method_id)->toBe($ctx['rate']->id);
});

it('rejects shipping selection with rate from wrong zone', function () {
    $ctx = stateContext();
    $checkouts = app(CheckoutService::class);

    $usRate = CommerceTestHelper::flatShippingRate($ctx['store'], ['US']);

    $checkout = $checkouts->createFromCart($ctx['cart'], 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());

    $checkouts->setShippingMethod($checkout, $usRate->id);
})->throws(Illuminate\Validation\ValidationException::class);

it('skips shipping selection when no items require shipping', function () {
    ['store' => $store] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store, [['price' => 2500, 'qty' => 1, 'shipping' => false]]);
    $checkouts = app(CheckoutService::class);

    $checkout = $checkouts->createFromCart($cart, 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, null);

    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected);
    expect($checkout->shipping_method_id)->toBeNull();
    expect($checkout->totals_json['shipping'])->toBe(0);
});

it('transitions from shipping selected to payment selected', function () {
    $ctx = stateContext();
    $checkouts = app(CheckoutService::class);

    $checkout = $checkouts->createFromCart($ctx['cart'], 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $ctx['rate']->id);
    $checkout = $checkouts->selectPaymentMethod($checkout, PaymentMethod::CreditCard);

    expect($checkout->status)->toBe(CheckoutStatus::PaymentSelected);
    expect($checkout->payment_method)->toBe(PaymentMethod::CreditCard);
    expect($checkout->expires_at)->not->toBeNull();

    $variantId = $ctx['cart']->cartLines()->first()->variant_id;
    $item = App\Models\InventoryItem::query()->where('variant_id', $variantId)->first();
    expect($item->quantity_reserved)->toBe(2);
});

it('transitions from payment selected to completed', function () {
    $ctx = stateContext();
    $checkouts = app(CheckoutService::class);

    $checkout = $checkouts->createFromCart($ctx['cart'], 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $ctx['rate']->id);
    $checkout = $checkouts->selectPaymentMethod($checkout, PaymentMethod::CreditCard);
    $checkouts->pay($checkout, PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);

    expect($checkout->refresh()->status)->toBe(CheckoutStatus::Completed);
});

it('rejects invalid state transitions', function () {
    $ctx = stateContext();
    $checkout = app(CheckoutService::class)->createFromCart($ctx['cart'], 'customer@example.com');

    app(CheckoutService::class)->selectPaymentMethod($checkout, PaymentMethod::CreditCard);
})->throws(InvalidCheckoutTransitionException::class);

it('recalculates pricing on address change', function () {
    $ctx = stateContext();

    TaxSetting::factory()->create([
        'store_id' => $ctx['store']->id,
        'mode' => App\Enums\TaxMode::Manual,
        'provider' => App\Enums\TaxProvider::None,
        'prices_include_tax' => false,
        'config_json' => [
            'default_tax_rate' => 1900,
            'tax_rates' => [
                ['country_code' => 'DE', 'rate' => 1900, 'name' => 'MwSt', 'shipping_taxed' => true],
                ['country_code' => 'AT', 'rate' => 2000, 'name' => 'USt', 'shipping_taxed' => true],
            ],
        ],
    ]);

    CommerceTestHelper::flatShippingRate($ctx['store'], ['AT']);

    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->createFromCart($ctx['cart'], 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $deTax = $checkout->totals_json['tax'];

    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address([
        'country' => 'Austria', 'country_code' => 'AT', 'city' => 'Vienna', 'postal_code' => '1010',
    ]));

    expect($checkout->totals_json['tax'])->not->toBe($deTax);
    expect($checkout->totals_json['tax'])->toBe(1000);
});
