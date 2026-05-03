<?php

use App\Enums\CheckoutStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Enums\PaymentMethod;
use App\Enums\ShippingRateType;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Services\CartService;
use App\Services\CheckoutService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function checkoutFixture(): array
{
    $store = Store::factory()->create(['default_currency' => 'EUR']);
    app()->instance('current_store', $store);

    $product = Product::factory()->for($store)->create();
    $variant = ProductVariant::factory()->for($product)->default()->create([
        'price_amount' => 1000,
        'currency' => 'EUR',
        'weight_g' => 250,
    ]);
    InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $variant->id)
        ->update(['quantity_on_hand' => 10, 'quantity_reserved' => 0, 'policy' => 'deny']);

    $zone = ShippingZone::factory()->for($store)->create(['countries_json' => ['DE']]);
    $rate = $zone->rates()->create([
        'name' => 'Standard',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 500],
        'is_active' => true,
    ]);

    TaxSettings::factory()->for($store)->create([
        'config_json' => ['default_rate_basis_points' => 1900, 'shipping_taxable' => true],
    ]);

    Discount::factory()->for($store)->create([
        'code' => 'WELCOME10',
        'type' => DiscountType::Code,
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'rules_json' => ['min_purchase_amount' => null],
    ]);

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, 2);

    return [$store, $cart->refresh(), $variant->refresh(), $rate];
}

test('checkout service calculates shipping discounts taxes and reserves inventory', function () {
    [, $cart, $variant, $rate] = checkoutFixture();

    $checkout = app(CheckoutService::class)->createFromCart($cart, 'buyer@example.com');
    $checkout = app(CheckoutService::class)->setAddress($checkout, [
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => 'Street 1',
            'city' => 'Berlin',
            'country' => 'Germany',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
        'use_shipping_as_billing' => true,
    ]);
    $checkout = app(CheckoutService::class)->setShippingMethod($checkout, $rate->id);
    $checkout = app(CheckoutService::class)->applyDiscount($checkout, 'welcome10');
    $checkout = app(CheckoutService::class)->selectPaymentMethod($checkout, PaymentMethod::CreditCard);

    expect($checkout->status)->toBe(CheckoutStatus::PaymentSelected)
        ->and($checkout->totals_json['subtotal'])->toBe(2000)
        ->and($checkout->totals_json['discount'])->toBe(200)
        ->and($checkout->totals_json['shipping'])->toBe(500)
        ->and($checkout->totals_json['tax'])->toBe(437)
        ->and($checkout->totals_json['total'])->toBe(2737)
        ->and($variant->inventoryItem->refresh()->quantity_reserved)->toBe(2);
});

test('expiring a payment selected checkout releases reserved inventory', function () {
    [, $cart, $variant, $rate] = checkoutFixture();

    $checkout = app(CheckoutService::class)->createFromCart($cart, 'buyer@example.com');
    $checkout = app(CheckoutService::class)->setAddress($checkout, [
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => 'Street 1',
            'city' => 'Berlin',
            'country' => 'Germany',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
        'use_shipping_as_billing' => true,
    ]);
    $checkout = app(CheckoutService::class)->setShippingMethod($checkout, $rate->id);
    $checkout = app(CheckoutService::class)->selectPaymentMethod($checkout, PaymentMethod::Paypal);

    app(CheckoutService::class)->expireCheckout($checkout);

    expect($checkout->refresh()->status)->toBe(CheckoutStatus::Expired)
        ->and($variant->inventoryItem->refresh()->quantity_reserved)->toBe(0);
});
