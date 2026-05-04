<?php

use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
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
use App\Services\ShippingCalculator;
use App\Services\TaxCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function pricingStore(): Store
{
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    TaxSettings::withoutGlobalScopes()->create([
        'store_id' => $store->getKey(),
        'mode' => 'manual',
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => [
            'name' => 'VAT',
            'default_rate_bps' => 1900,
            'shipping_taxable' => true,
            'rates' => [
                ['country' => 'DE', 'rate_bps' => 1900, 'name' => 'VAT'],
            ],
        ],
    ]);

    return $store;
}

function pricingVariant(Store $store, int $price = 2500, bool $requiresShipping = true): ProductVariant
{
    $product = Product::factory()
        ->withDefaultVariant($price)
        ->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::withoutGlobalScopes()
        ->where('product_id', $product->getKey())
        ->firstOrFail();

    $variant->forceFill([
        'requires_shipping' => $requiresShipping,
        'weight_g' => $requiresShipping ? 500 : 0,
    ])->save();
    InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $variant->getKey())
        ->update(['quantity_on_hand' => 20]);

    return $variant->refresh();
}

function pricingCheckout(Store $store, ProductVariant $variant, int $quantity = 2): Checkout
{
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->getKey(), $quantity);

    return Checkout::withoutGlobalScopes()->create([
        'store_id' => $store->getKey(),
        'cart_id' => $cart->getKey(),
        'status' => 'shipping_selected',
        'email' => 'buyer@example.test',
        'shipping_address_json' => [
            'first_name' => 'Test',
            'last_name' => 'Buyer',
            'address1' => 'Main Street 1',
            'city' => 'Berlin',
            'country' => 'DE',
            'postal_code' => '10115',
        ],
    ]);
}

test('pricing engine calculates deterministic exclusive totals with shipping tax', function () {
    $store = pricingStore();
    $variant = pricingVariant($store);
    $zone = ShippingZone::withoutGlobalScopes()->create([
        'store_id' => $store->getKey(),
        'name' => 'Germany',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);
    $rate = ShippingRate::withoutGlobalScopes()->create([
        'zone_id' => $zone->getKey(),
        'name' => 'Standard',
        'type' => 'flat',
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);
    $checkout = pricingCheckout($store, $variant);
    $checkout->forceFill(['shipping_method_id' => $rate->getKey()])->save();

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->subtotal)->toBe(5000)
        ->and($result->shipping)->toBe(499)
        ->and($result->taxTotal)->toBe(1045)
        ->and($result->total)->toBe(6544)
        ->and($checkout->refresh()->totals_json['total'])->toBe(6544);
});

test('pricing engine applies percent and free shipping discounts', function () {
    $store = pricingStore();
    $variant = pricingVariant($store);
    $zone = ShippingZone::withoutGlobalScopes()->create([
        'store_id' => $store->getKey(),
        'name' => 'Germany',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);
    $rate = ShippingRate::withoutGlobalScopes()->create([
        'zone_id' => $zone->getKey(),
        'name' => 'Standard',
        'type' => 'flat',
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);
    Discount::factory()->create(['store_id' => $store->getKey(), 'code' => 'SAVE10']);
    Discount::factory()->freeShipping()->create(['store_id' => $store->getKey(), 'code' => 'FREESHIP']);
    $checkout = pricingCheckout($store, $variant);
    $checkout->forceFill([
        'shipping_method_id' => $rate->getKey(),
        'discount_code' => 'save10',
    ])->save();

    $discounted = app(PricingEngine::class)->calculate($checkout);

    expect($discounted->discount)->toBe(500)
        ->and($discounted->shipping)->toBe(499)
        ->and($discounted->total)->toBe(5949);

    $checkout->forceFill(['discount_code' => 'FREESHIP'])->save();
    $freeShipping = app(PricingEngine::class)->calculate($checkout);

    expect($freeShipping->discount)->toBe(0)
        ->and($freeShipping->shipping)->toBe(0)
        ->and($freeShipping->total)->toBe(5950);
});

test('pricing engine applies active automatic discounts', function () {
    $store = pricingStore();
    $variant = pricingVariant($store);
    $checkout = pricingCheckout($store, $variant);
    Discount::factory()->create([
        'store_id' => $store->getKey(),
        'type' => DiscountType::Automatic,
        'code' => null,
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);
    $line = $checkout->cart->lines()->firstOrFail();

    expect($result->discount)->toBe(500)
        ->and($result->total)->toBe(5355)
        ->and($line->refresh()->line_discount_amount)->toBe(500)
        ->and($line->line_total_amount)->toBe(4500);
});

test('pricing engine stacks automatic discounts after explicit code discounts', function () {
    $store = pricingStore();
    $variant = pricingVariant($store);
    $checkout = pricingCheckout($store, $variant);
    Discount::factory()->create([
        'store_id' => $store->getKey(),
        'code' => 'SAVE10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
    ]);
    Discount::factory()->create([
        'store_id' => $store->getKey(),
        'type' => DiscountType::Automatic,
        'code' => null,
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
    ]);
    $checkout->forceFill(['discount_code' => 'SAVE10'])->save();

    $result = app(PricingEngine::class)->calculate($checkout);
    $line = $checkout->cart->lines()->firstOrFail();

    expect($result->discount)->toBe(950)
        ->and($result->total)->toBe(4820)
        ->and($line->refresh()->line_discount_amount)->toBe(950)
        ->and($line->line_total_amount)->toBe(4050);
});

test('shipping and tax calculators handle matching ranges and inclusive extraction', function () {
    $store = pricingStore();
    $physicalVariant = pricingVariant($store, requiresShipping: true);
    $digitalVariant = pricingVariant($store, requiresShipping: false);
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $physicalVariant->getKey(), 2);
    app(CartService::class)->addLine($cart, $digitalVariant->getKey(), 1);
    $zone = ShippingZone::withoutGlobalScopes()->create([
        'store_id' => $store->getKey(),
        'name' => 'Berlin',
        'countries_json' => ['DE'],
        'regions_json' => ['DE-BE'],
    ]);
    $rate = ShippingRate::withoutGlobalScopes()->create([
        'zone_id' => $zone->getKey(),
        'name' => 'Weight',
        'type' => 'weight',
        'config_json' => [
            'ranges' => [
                ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
                ['min_g' => 501, 'max_g' => 2000, 'amount' => 899],
            ],
        ],
        'is_active' => true,
    ]);

    $rates = app(ShippingCalculator::class)->getAvailableRates($store, ['country' => 'DE', 'province_code' => 'DE-BE']);

    expect($rates->first()?->getKey())->toBe($rate->getKey())
        ->and(app(ShippingCalculator::class)->calculate($rate, $cart))->toBe(899)
        ->and(app(TaxCalculator::class)->extractInclusive(11900, 1900))->toBe(1900)
        ->and(app(TaxCalculator::class)->addExclusive(10000, 1900))->toBe(1900);
});
