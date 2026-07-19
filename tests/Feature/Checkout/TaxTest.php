<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Services\CartService;
use App\Services\CheckoutService;

/**
 * Store with variant, DE zone + flat rate 499, optional tax settings.
 *
 * @return array{0: Store, 1: ProductVariant, 2: ShippingRate}
 */
function taxSetup(int $price = 2500, bool $inclusive = false, bool $withTaxSettings = true): array
{
    $store = test()->createStore();
    test()->bindStore($store);

    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->withInventory(50)->create([
        'product_id' => $product->id,
        'price_amount' => $price,
    ]);

    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->flat(499)->create(['zone_id' => $zone->id]);

    if ($withTaxSettings) {
        TaxSettings::factory()->withRate(1900)->create([
            'store_id' => $store->id,
            'prices_include_tax' => $inclusive,
        ]);
    }

    return [$store, $variant, $rate];
}

/**
 * Checkout at shipping_selected for the given quantity.
 */
function taxCheckout(Store $store, ProductVariant $variant, ShippingRate $rate, int $quantity): App\Models\Checkout
{
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, $quantity);

    $service = app(CheckoutService::class);
    $checkout = $service->createFromCart($cart->refresh(), 'jane@example.com');
    $checkout = $service->setAddress($checkout, ['shipping_address' => [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'address1' => '123 Main St',
        'city' => 'Berlin',
        'country' => 'DE',
        'country_code' => 'DE',
        'postal_code' => '10115',
    ]]);

    return $service->setShippingMethod($checkout, $rate->id);
}

test('calculates exclusive tax correctly at checkout', function () {
    [$store, $variant, $rate] = taxSetup();

    // 5000 lines + 499 shipping = 5499 base -> 950 + 94 = 1044 (spec 09).
    $checkout = taxCheckout($store, $variant, $rate, quantity: 2);

    expect($checkout->totals_json['tax'])->toBe(1044)
        ->and($checkout->totals_json['total'])->toBe(6543);
});

test('extracts inclusive tax correctly at checkout', function () {
    [$store, $variant, $rate] = taxSetup(price: 11900, inclusive: true);

    $checkout = taxCheckout($store, $variant, $rate, quantity: 1);

    // Gross 11900 + 499 shipping; tax extracted from gross amounts.
    $itemTax = $checkout->tax_provider_snapshot_json['lines'][0]['tax_amount'];
    $shippingTax = $checkout->tax_provider_snapshot_json['shipping_tax_amount'];

    expect($itemTax)->toBe(1900)
        ->and(11900 - $itemTax)->toBe(10000)
        ->and($checkout->totals_json['tax'])->toBe($itemTax + $shippingTax)
        ->and($checkout->totals_json['total'])->toBe(11900 + 499);
});

test('applies zero tax when no tax settings exist', function () {
    [$store, $variant, $rate] = taxSetup(withTaxSettings: false);

    $checkout = taxCheckout($store, $variant, $rate, quantity: 2);

    expect($checkout->totals_json['tax'])->toBe(0)
        ->and($checkout->totals_json['total'])->toBe(5499);
});

test('stores tax lines in totals_json', function () {
    [$store, $variant, $rate] = taxSetup();

    $checkout = taxCheckout($store, $variant, $rate, quantity: 2);

    expect($checkout->totals_json['tax_lines'])->toHaveCount(1)
        ->and($checkout->totals_json['tax_lines'][0]['rate'])->toBe(1900)
        ->and($checkout->totals_json['tax_lines'][0]['amount'])->toBe(1044)
        ->and($checkout->totals_json['tax_lines'][0]['name'])->toBeString();
});
