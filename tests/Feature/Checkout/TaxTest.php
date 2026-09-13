<?php

use App\Enums\TaxMode;
use App\Enums\TaxProvider;
use App\Models\TaxSetting;
use App\Services\CheckoutService;
use App\Services\TaxCalculator;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function taxCheckoutContext(bool $inclusive = false): array
{
    ['store' => $store] = test()->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store, [['price' => 2500, 'qty' => 2]]);
    $rate = CommerceTestHelper::flatShippingRate($store, ['DE'], 499);

    TaxSetting::factory()->create([
        'store_id' => $store->id,
        'mode' => TaxMode::Manual,
        'provider' => TaxProvider::None,
        'prices_include_tax' => $inclusive,
        'config_json' => ['default_tax_rate' => 1900],
    ]);

    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->createFromCart($cart, 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $rate->id);

    return ['store' => $store, 'checkout' => $checkout];
}

it('calculates exclusive tax correctly at checkout', function () {
    $totals = taxCheckoutContext()['checkout']->refresh()->totals_json;

    expect($totals['tax'])->toBe(1044);
    expect($totals['total'])->toBe(6543);
});

it('extracts inclusive tax correctly at checkout', function () {
    $totals = taxCheckoutContext(true)['checkout']->refresh()->totals_json;

    expect($totals['tax'])->toBe(879);
    expect($totals['total'])->toBe(5499);
});

it('applies zero tax when no tax settings exist', function () {
    ['store' => $store] = $this->createStoreContext();

    $result = app(TaxCalculator::class)->calculate($store, [['amount' => 5000]], 499, ['country_code' => 'DE']);

    expect($result->totalAmount)->toBe(0);
    expect($result->taxLines)->toBeEmpty();
});

it('stores tax lines in totals json', function () {
    $totals = taxCheckoutContext()['checkout']->refresh()->totals_json;

    expect($totals['tax_lines'])->toHaveCount(2);
    expect($totals['tax_lines'][0]['rate'])->toBe(1900);
    expect($totals['tax_lines'][0]['amount'])->toBe(950);
    expect($totals['tax_lines'][1]['amount'])->toBe(94);
});
