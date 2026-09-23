<?php

use App\Models\Cart;
use App\Models\TaxSetting;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Validation\ValidationException;

function taxCheckoutForStore(\App\Models\Store $store): \App\Models\Checkout
{
    $product = shopProduct($store);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    app(CartService::class)->add($cart, $product['variant'], 1);

    return app(CheckoutService::class)->start($cart);
}

function taxAddress(): array
{
    return [
        'first_name' => 'Alex',
        'last_name' => 'Buyer',
        'address1' => '1 Test Street',
        'city' => 'Berlin',
        'province_code' => 'BE',
        'country' => 'Germany',
        'country_code' => 'DE',
        'zip' => '10115',
    ];
}

it('calculates manual regional tax and stores a provider snapshot', function () {
    $store = shopStore();
    TaxSetting::factory()->create([
        'store_id' => $store->id,
        'mode' => 'manual',
        'default_rate' => 500,
        'config_json' => ['tax_rates' => [['country_code' => 'DE', 'province_code' => 'BE', 'rate' => 1900]]],
    ]);

    $checkout = app(CheckoutService::class)->setAddress(taxCheckoutForStore($store), 'buyer@example.test', taxAddress());

    expect($checkout->tax_amount)->toBe(475)
        ->and($checkout->tax_provider_snapshot_json['provider'])->toBe('manual')
        ->and($checkout->tax_provider_snapshot_json['rate'])->toBe(1900);
});

it('allows checkout to continue without tax when the unavailable provider fallback is allow', function () {
    $store = shopStore();
    TaxSetting::factory()->create([
        'store_id' => $store->id,
        'mode' => 'provider',
        'provider' => 'stripe_tax',
        'default_rate' => 1900,
        'config_json' => ['fallback' => 'allow'],
    ]);

    $checkout = app(CheckoutService::class)->setAddress(taxCheckoutForStore($store), 'buyer@example.test', taxAddress());

    expect($checkout->tax_amount)->toBe(0)
        ->and($checkout->total_amount)->toBe(2500)
        ->and($checkout->tax_provider_snapshot_json['provider'])->toBe('stripe_tax')
        ->and($checkout->tax_provider_snapshot_json['status'])->toBe('fallback');
});

it('blocks checkout and records the failed provider response when fallback is block', function () {
    $store = shopStore();
    TaxSetting::factory()->create([
        'store_id' => $store->id,
        'mode' => 'provider',
        'provider' => 'stripe_tax',
        'config_json' => ['fallback' => 'block'],
    ]);
    $checkout = taxCheckoutForStore($store);

    expect(fn () => app(CheckoutService::class)->setAddress($checkout, 'buyer@example.test', taxAddress()))
        ->toThrow(ValidationException::class);

    expect($checkout->refresh()->tax_provider_snapshot_json['status'])->toBe('unavailable');
});
