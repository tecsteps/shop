<?php

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Enums\ShippingRateType;
use App\Exceptions\InvalidCheckoutStateException;
use App\Exceptions\InvalidDiscountException;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Services\CartService;
use App\Services\CheckoutService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function bootCheckoutFixture(): array
{
    $store = Store::factory()->create();
    $product = Product::factory()->active()->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->getKey(),
        'price_amount' => 1500,
        'requires_shipping' => 1,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $store->getKey(),
        'variant_id' => $variant->getKey(),
        'quantity_on_hand' => 100,
    ]);
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, (int) $variant->getKey(), 1);

    $zone = ShippingZone::factory()->create([
        'store_id' => $store->getKey(),
        'countries_json' => ['US'],
    ]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->getKey(),
        'type' => ShippingRateType::Flat->value,
        'config_json' => ['amount' => 800],
    ]);

    TaxSettings::factory()->create([
        'store_id' => $store->getKey(),
        'config_json' => ['default_rate_bps' => 0],
    ]);

    return [$store, $cart, $variant, $rate];
}

it('starts a checkout in the started state and snapshots totals', function () {
    [$store, $cart] = bootCheckoutFixture();

    $checkout = app(CheckoutService::class)->start($store, $cart);

    expect($checkout->status)->toBe(CheckoutStatus::Started)
        ->and($checkout->totals_json['subtotal'])->toBe(1500);
});

it('transitions through address, shipping, payment', function () {
    [$store, $cart, , $rate] = bootCheckoutFixture();
    $service = app(CheckoutService::class);

    $checkout = $service->start($store, $cart);
    $checkout = $service->setAddress($checkout, [
        'email' => 'buyer@example.test',
        'shipping_address' => [
            'first_name' => 'Sam',
            'last_name' => 'Shopper',
            'address1' => '1 Main St',
            'city' => 'Somewhere',
            'country_code' => 'US',
            'postal_code' => '12345',
        ],
    ]);

    expect($checkout->status)->toBe(CheckoutStatus::Addressed);

    $checkout = $service->setShippingMethod($checkout, (int) $rate->getKey());

    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected)
        ->and($checkout->totals_json['shipping'])->toBe(800);

    $checkout = $service->selectPaymentMethod($checkout, PaymentMethod::CreditCard);

    expect($checkout->status)->toBe(CheckoutStatus::PaymentSelected)
        ->and($checkout->payment_method)->toBe(PaymentMethod::CreditCard);
});

it('rejects shipping rates that do not belong to a matching zone', function () {
    [$store, $cart] = bootCheckoutFixture();
    $service = app(CheckoutService::class);
    $checkout = $service->start($store, $cart);
    $service->setAddress($checkout, [
        'email' => 'buyer@example.test',
        'shipping_address' => [
            'first_name' => 'A',
            'last_name' => 'B',
            'address1' => '1 St',
            'city' => 'X',
            'country_code' => 'CA',
            'postal_code' => 'K1A',
        ],
    ]);

    $otherZone = ShippingZone::factory()->create([
        'store_id' => $store->getKey(),
        'countries_json' => ['DE'],
    ]);
    $otherRate = ShippingRate::factory()->create(['zone_id' => $otherZone->getKey()]);

    $service->setShippingMethod($checkout->refresh(), (int) $otherRate->getKey());
})->throws(InvalidCheckoutStateException::class);

it('applies a valid discount code to the checkout', function () {
    [$store, $cart] = bootCheckoutFixture();
    $service = app(CheckoutService::class);
    $checkout = $service->start($store, $cart);

    Discount::factory()->percent(20)->create([
        'store_id' => $store->getKey(),
        'code' => 'TWENTY',
    ]);

    $checkout = $service->applyDiscount($checkout, 'twenty');

    expect($checkout->discount_code)->toBe('TWENTY')
        ->and($checkout->totals_json['discount'])->toBe(300);
});

it('rejects an invalid discount code', function () {
    [$store, $cart] = bootCheckoutFixture();
    $service = app(CheckoutService::class);
    $checkout = $service->start($store, $cart);

    $service->applyDiscount($checkout, 'NOPE');
})->throws(InvalidDiscountException::class);
