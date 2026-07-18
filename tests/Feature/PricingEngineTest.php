<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Services\PricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates pricing in discount shipping and tax order', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 1000]);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 1000,
        'line_subtotal_amount' => 2000,
        'line_total_amount' => 2000,
    ]);
    $zone = ShippingZone::factory()->create(['store_id' => $store->id]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'config_json' => ['amount' => 500]]);
    TaxSettings::factory()->create(['store_id' => $store->id, 'config_json' => ['default_rate' => 1000]]);
    Discount::factory()->create(['store_id' => $store->id, 'code' => 'SAVE10']);
    $checkout = Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'shipping_method_id' => $rate->id,
        'shipping_address_json' => ['country_code' => 'US'],
        'discount_code' => 'SAVE10',
    ]);

    $result = app(PricingEngine::class)->calculate($checkout);

    expect($result->subtotal)->toBe(2000)
        ->and($result->discount)->toBe(200)
        ->and($result->shipping)->toBe(500)
        ->and($result->taxTotal)->toBe(180)
        ->and($result->total)->toBe(2480)
        ->and($checkout->refresh()->totals_json['total'])->toBe(2480);
});
