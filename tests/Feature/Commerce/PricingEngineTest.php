<?php

use App\Enums\CheckoutStatus;
use App\Enums\ShippingRateType;
use App\Enums\TaxMode;
use App\Enums\TaxProviderType;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Services\CartService;
use App\Services\PricingEngine;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function buildPricingFixture(int $price = 1000, int $qty = 2, int $taxRateBps = 1000, bool $pricesInclude = false, array $discount = []): array
{
    $store = Store::factory()->create();
    $product = Product::factory()->active()->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->getKey(),
        'price_amount' => $price,
        'requires_shipping' => 1,
        'weight_g' => 500,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $store->getKey(),
        'variant_id' => $variant->getKey(),
        'quantity_on_hand' => 100,
    ]);

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, (int) $variant->getKey(), $qty);

    $zone = ShippingZone::factory()->create([
        'store_id' => $store->getKey(),
        'countries_json' => ['US'],
    ]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->getKey(),
        'type' => ShippingRateType::Flat->value,
        'config_json' => ['amount' => 500],
    ]);

    TaxSettings::factory()->create([
        'store_id' => $store->getKey(),
        'mode' => TaxMode::Manual->value,
        'provider' => TaxProviderType::None->value,
        'prices_include_tax' => $pricesInclude ? 1 : 0,
        'config_json' => ['default_rate_bps' => $taxRateBps],
    ]);

    $discountCode = null;

    if (! empty($discount)) {
        $discountCode = $discount['code'] ?? 'CODE';
        Discount::factory()->create(array_merge([
            'store_id' => $store->getKey(),
            'code' => $discountCode,
        ], $discount));
    }

    $checkout = Checkout::factory()->create([
        'store_id' => $store->getKey(),
        'cart_id' => $cart->getKey(),
        'status' => CheckoutStatus::ShippingSelected->value,
        'shipping_address_json' => ['country_code' => 'US'],
        'shipping_method_id' => $rate->getKey(),
        'discount_code' => $discountCode,
    ]);

    return [$store, $cart, $checkout, $rate];
}

it('produces subtotal + shipping + tax breakdown', function () {
    [, , $checkout] = buildPricingFixture(price: 1000, qty: 2, taxRateBps: 1000);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->subtotal)->toBe(2000)
        ->and($result->shipping)->toBe(500)
        ->and($result->taxTotal)->toBe(200)
        ->and($result->total)->toBe(2700);
});

it('applies a percent discount before tax', function () {
    [, , $checkout] = buildPricingFixture(
        price: 1000,
        qty: 2,
        taxRateBps: 1000,
        discount: [
            'code' => 'SAVE10',
            'value_type' => \App\Enums\DiscountValueType::Percent->value,
            'value_amount' => 10,
        ],
    );

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->subtotal)->toBe(2000)
        ->and($result->discount)->toBe(200)
        ->and($result->taxTotal)->toBe(180)
        ->and($result->total)->toBe(2480);
});

it('applies free shipping discount by zeroing shipping', function () {
    [, , $checkout] = buildPricingFixture(
        price: 1000,
        qty: 1,
        taxRateBps: 0,
        discount: [
            'code' => 'FREESHIP',
            'value_type' => \App\Enums\DiscountValueType::FreeShipping->value,
            'value_amount' => 0,
        ],
    );

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->shipping)->toBe(0)
        ->and($result->total)->toBe(1000);
});
