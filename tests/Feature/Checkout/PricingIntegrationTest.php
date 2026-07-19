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
 * Build the absolute storefront API URL for the store's primary domain.
 */
function pricingApiUrl(Store $store, string $path): string
{
    return 'http://'.$store->handle.'.test/api/storefront/v1'.$path;
}

/**
 * Store with variant, DE zone + flat rate 499, 19% exclusive tax.
 *
 * @return array{0: Store, 1: ProductVariant, 2: ShippingRate}
 */
function pricingSetup(int $price = 2500, bool $inclusive = false): array
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

    TaxSettings::factory()->withRate(1900)->create([
        'store_id' => $store->id,
        'prices_include_tax' => $inclusive,
    ]);

    return [$store, $variant, $rate];
}

function integrationAddress(): array
{
    return [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'address1' => '123 Main St',
        'city' => 'Berlin',
        'country' => 'DE',
        'country_code' => 'DE',
        'postal_code' => '10115',
    ];
}

/**
 * Drive a checkout through address + shipping selection.
 *
 * @return array{0: App\Models\Checkout, 1: App\Models\Cart}
 */
function checkoutThroughShipping(Store $store, ProductVariant $variant, ShippingRate $rate, int $quantity = 2, ?int $price = null): array
{
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, $quantity);

    $service = app(CheckoutService::class);
    $checkout = $service->createFromCart($cart->refresh(), 'jane@example.com');
    $checkout = $service->setAddress($checkout, ['shipping_address' => integrationAddress()]);
    $checkout = $service->setShippingMethod($checkout, $rate->id);

    return [$checkout, $cart->refresh()];
}

test('calculates correct totals for a simple checkout', function () {
    [$store, $variant, $rate] = pricingSetup();

    [$checkout] = checkoutThroughShipping($store, $variant, $rate, quantity: 2);

    // 2500 x 2 = 5000, flat 499, 19% exclusive on 5499 -> 1044 (spec 09).
    expect($checkout->totals_json['subtotal'])->toBe(5000)
        ->and($checkout->totals_json['shipping'])->toBe(499)
        ->and($checkout->totals_json['tax'])->toBe(1044)
        ->and($checkout->totals_json['total'])->toBe(6543);
});

test('applies discount code and recalculates', function () {
    [$store, $variant, $rate] = pricingSetup(price: 10000);
    \App\Models\Discount::factory()->percent(10)->create(['store_id' => $store->id, 'code' => 'SAVE10']);

    [$checkout] = checkoutThroughShipping($store, $variant, $rate, quantity: 1);

    $result = app(CheckoutService::class)->applyDiscount($checkout, 'SAVE10');
    $checkout->refresh();

    expect($result->valid)->toBeTrue()
        ->and($checkout->totals_json['subtotal'])->toBe(10000)
        ->and($checkout->totals_json['discount'])->toBe(1000)
        ->and($checkout->totals_json['subtotal'] - $checkout->totals_json['discount'])->toBe(9000);
});

test('stores pricing snapshot in totals_json', function () {
    [$store, $variant, $rate] = pricingSetup();

    [$checkout] = checkoutThroughShipping($store, $variant, $rate);

    expect($checkout->totals_json)->toHaveKeys(['subtotal', 'discount', 'shipping', 'tax', 'tax_lines', 'total', 'currency'])
        ->and($checkout->totals_json['tax_lines'][0])->toHaveKeys(['name', 'rate', 'amount']);
});

test('recalculates on shipping method change', function () {
    [$store, $variant, $rate] = pricingSetup();
    $zone = $rate->zone;
    $weightRate = ShippingRate::factory()->weight([
        ['min_g' => 0, 'max_g' => 5000, 'amount' => 899],
    ])->create(['zone_id' => $zone->id]);

    $service = app(CheckoutService::class);
    [$checkout] = checkoutThroughShipping($store, $variant, $rate);

    expect($checkout->totals_json['shipping'])->toBe(499)
        ->and($checkout->totals_json['total'])->toBe(6543);

    $checkout = $service->setShippingMethod($checkout, $weightRate->id);

    // 19% exclusive: 950 on lines + 170 on shipping = 1120.
    expect($checkout->totals_json['shipping'])->toBe(899)
        ->and($checkout->totals_json['tax'])->toBe(1120)
        ->and($checkout->totals_json['total'])->toBe(5000 + 899 + 1120);
});

test('handles prices include tax correctly', function () {
    [$store, $variant, $rate] = pricingSetup(price: 11900, inclusive: true);

    [$checkout] = checkoutThroughShipping($store, $variant, $rate, quantity: 1);

    // Gross 11900 @ 19%: extracted tax 1900, net subtotal 10000 (spec 09).
    $lineTax = $checkout->totals_json['tax'] - 0;
    $shippingTax = $checkout->tax_provider_snapshot_json['shipping_tax_amount'];
    $itemTax = $checkout->tax_provider_snapshot_json['lines'][0]['tax_amount'];

    expect($itemTax)->toBe(1900)
        ->and(11900 - $itemTax)->toBe(10000)
        ->and($checkout->totals_json['tax'])->toBe($itemTax + $shippingTax)
        ->and($checkout->totals_json['total'])->toBe(11900 + 499);
});
