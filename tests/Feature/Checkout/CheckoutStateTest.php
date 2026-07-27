<?php

use App\Enums\CheckoutStatus;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Validation\ValidationException;

/**
 * Build the absolute storefront API URL for the store's primary domain.
 */
function checkoutStateApiUrl(Store $store, string $path): string
{
    return 'http://'.$store->handle.'.test/api/storefront/v1'.$path;
}

/**
 * Store with variant (2500), DE zone + flat rate 499.
 *
 * @return array{0: Store, 1: ProductVariant, 2: ShippingRate}
 */
function checkoutStateSetup(array $variantAttributes = []): array
{
    $store = test()->createStore();
    test()->bindStore($store);

    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->withInventory(10)->create(array_merge([
        'product_id' => $product->id,
        'price_amount' => 2500,
    ], $variantAttributes));

    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->flat(499)->create(['zone_id' => $zone->id]);

    return [$store, $variant, $rate];
}

function stateAddress(string $country = 'DE'): array
{
    return [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'address1' => '123 Main St',
        'city' => 'Berlin',
        'country' => $country,
        'country_code' => $country,
        'postal_code' => '10115',
    ];
}

/**
 * Create a cart with the variant and a started checkout.
 *
 * @return array{0: App\Models\Cart, 1: App\Models\Checkout}
 */
function startedCheckout(Store $store, ProductVariant $variant, int $quantity = 2): array
{
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, $quantity);

    $checkout = app(CheckoutService::class)->createFromCart($cart->refresh(), 'jane@example.com');

    return [$cart->refresh(), $checkout];
}

test('transitions from started to addressed with valid address', function () {
    [$store, $variant] = checkoutStateSetup();
    [, $checkout] = startedCheckout($store, $variant);

    $checkout = app(CheckoutService::class)->setAddress($checkout, ['shipping_address' => stateAddress()]);

    expect($checkout->status)->toBe(CheckoutStatus::Addressed)
        ->and($checkout->shipping_address_json['city'])->toBe('Berlin')
        ->and($checkout->billing_address_json['city'])->toBe('Berlin');
});

test('rejects address transition with missing required fields', function () {
    [$store, $variant] = checkoutStateSetup();
    [, $checkout] = startedCheckout($store, $variant);

    $response = $this->putJson(checkoutStateApiUrl($store, "/checkouts/{$checkout->id}/address"), [
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'country' => 'DE',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['shipping_address.city']);

    expect($checkout->refresh()->status)->toBe(CheckoutStatus::Started);
});

test('transitions from addressed to shipping selected', function () {
    [$store, $variant, $rate] = checkoutStateSetup();
    [, $checkout] = startedCheckout($store, $variant);
    $service = app(CheckoutService::class);

    $checkout = $service->setAddress($checkout, ['shipping_address' => stateAddress()]);
    $checkout = $service->setShippingMethod($checkout, $rate->id);

    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected)
        ->and($checkout->shipping_method_id)->toBe($rate->id)
        ->and($checkout->totals_json['shipping'])->toBe(499);
});

test('rejects shipping selection with rate from wrong zone', function () {
    [$store, $variant] = checkoutStateSetup();
    [, $checkout] = startedCheckout($store, $variant);
    $service = app(CheckoutService::class);
    $checkout = $service->setAddress($checkout, ['shipping_address' => stateAddress('DE')]);

    $usZone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['US']]);
    $usRate = ShippingRate::factory()->flat(999)->create(['zone_id' => $usZone->id]);

    $service->setShippingMethod($checkout, $usRate->id);
})->throws(ValidationException::class);

test('skips shipping selection when no items require shipping', function () {
    [$store, $variant] = checkoutStateSetup(['requires_shipping' => false]);
    [, $checkout] = startedCheckout($store, $variant);
    $service = app(CheckoutService::class);

    // Digital-only carts are serviceable without a zone match.
    $checkout = $service->setAddress($checkout, ['shipping_address' => stateAddress('FR')]);
    $checkout = $service->setShippingMethod($checkout, null);

    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected)
        ->and($checkout->shipping_method_id)->toBeNull()
        ->and($checkout->totals_json['shipping'])->toBe(0);
});

test('transitions from shipping selected to payment selected', function () {
    [$store, $variant, $rate] = checkoutStateSetup();
    [, $checkout] = startedCheckout($store, $variant, quantity: 3);
    $service = app(CheckoutService::class);

    $checkout = $service->setAddress($checkout, ['shipping_address' => stateAddress()]);
    $checkout = $service->setShippingMethod($checkout, $rate->id);
    $checkout = $service->selectPaymentMethod($checkout, 'paypal');

    expect($checkout->status)->toBe(CheckoutStatus::PaymentSelected)
        ->and($checkout->expires_at->isFuture())->toBeTrue()
        ->and($variant->inventoryItem->refresh()->quantity_reserved)->toBe(3);
});

test('rejects invalid state transitions', function () {
    [$store, $variant] = checkoutStateSetup();
    [, $checkout] = startedCheckout($store, $variant);

    app(CheckoutService::class)->selectPaymentMethod($checkout, 'credit_card');
})->throws(InvalidCheckoutTransitionException::class);

test('recalculates pricing on address change', function () {
    [$store, $variant, $rate] = checkoutStateSetup();

    TaxSettings::factory()->withRate(1900)->create([
        'store_id' => $store->id,
        'config_json' => [
            'default_rate_bps' => 1900,
            'zone_rates' => [],
        ],
    ]);

    $frZone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['FR']]);
    ShippingRate::factory()->flat(499)->create(['zone_id' => $frZone->id]);

    // Different rate for the FR zone.
    $taxSettings = TaxSettings::find($store->id);
    $config = $taxSettings->config_json;
    $deZone = ShippingZone::where('store_id', $store->id)->where('countries_json', 'like', '%DE%')->first();
    $config['zone_rates'] = [$deZone->id => 1900, $frZone->id => 700];
    $taxSettings->update(['config_json' => $config]);

    $service = app(CheckoutService::class);
    [, $checkout] = startedCheckout($store, $variant, quantity: 2);

    $checkout = $service->setAddress($checkout, ['shipping_address' => stateAddress('DE')]);
    $checkout = $service->setShippingMethod($checkout, $rate->id);
    $taxDe = $checkout->totals_json['tax'];

    $checkout = $service->setAddress($checkout, ['shipping_address' => stateAddress('FR')]);
    $frRate = $frZone->rates()->first();
    $checkout = $service->setShippingMethod($checkout, $frRate->id);
    $taxFr = $checkout->totals_json['tax'];

    // 19% of 5499 = 1044 vs 7% of 5499 = 384
    expect($taxDe)->toBe(1044)
        ->and($taxFr)->toBe(384);
});
