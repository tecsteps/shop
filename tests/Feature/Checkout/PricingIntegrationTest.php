<?php

use App\Enums\DiscountValueType;
use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\ShippingRateType;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\PricingEngine;

beforeEach(function (): void {
    $ctx = $this->createStoreContext();
    $this->store = $ctx['store'];
    $this->cart = app(CartService::class);
    $this->checkout = app(CheckoutService::class);
    $this->pricing = app(PricingEngine::class);

    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => ProductStatus::Active]);
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
        'weight_g' => 200,
        'requires_shipping' => true,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $this->variant->id,
        'quantity_on_hand' => 100,
        'policy' => InventoryPolicy::Deny,
    ]);

    $this->zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['US'],
    ]);
    $this->rate = ShippingRate::factory()->create([
        'zone_id' => $this->zone->id,
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 799],
    ]);
});

it('runs the full pipeline end to end', function (): void {
    TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'prices_include_tax' => false,
        'config_json' => ['default_rate_bps' => 1000],
    ]);
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'SAVE10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
    ]);

    $cart = $this->cart->create($this->store);
    $this->cart->addLine($cart, $this->variant->id, 2); // 5000 cents

    $checkout = $this->checkout->startFromCart($cart);
    $this->checkout->setAddress($checkout, [
        'email' => 'customer@example.com',
        'shipping_address' => [
            'first_name' => 'Jane', 'last_name' => 'Doe',
            'address1' => '1 Main', 'city' => 'LA',
            'country_code' => 'US', 'province_code' => 'US-CA', 'zip' => '10001',
        ],
    ]);
    $this->checkout->setShippingMethod($checkout->fresh(), $this->rate->id);
    $checkout = $this->checkout->applyDiscount($checkout->fresh(), 'SAVE10');

    $result = $this->pricing->calculate($checkout->fresh());

    expect($result->subtotal)->toBe(5000);
    expect($result->discount)->toBe(500);
    expect($result->shipping)->toBe(799);
    expect($result->taxTotal)->toBe(530); // 10% of (4500 + 799) = 529.9 -> 530 rounded
    expect($result->total)->toBe(5000 - 500 + 799 + 530);
});

it('stores totals snapshot on the checkout', function (): void {
    $cart = $this->cart->create($this->store);
    $this->cart->addLine($cart, $this->variant->id, 1);

    $checkout = $this->checkout->startFromCart($cart);
    $result = $this->pricing->calculate($checkout);

    expect($checkout->fresh()->totals_json)->toBe($result->toArray());
});

it('applies free shipping discount to zero out shipping', function (): void {
    Discount::factory()->freeShipping()->create([
        'store_id' => $this->store->id,
        'code' => 'FREESHIP',
    ]);

    $cart = $this->cart->create($this->store);
    $this->cart->addLine($cart, $this->variant->id, 1);

    $checkout = $this->checkout->startFromCart($cart);
    $this->checkout->setAddress($checkout, [
        'email' => 'c@x.co',
        'shipping_address' => [
            'first_name' => 'X', 'last_name' => 'Y',
            'address1' => '1', 'city' => 'NY',
            'country_code' => 'US', 'zip' => '10001',
        ],
    ]);
    $this->checkout->setShippingMethod($checkout->fresh(), $this->rate->id);
    $checkout = $this->checkout->applyDiscount($checkout->fresh(), 'FREESHIP');
    $result = $this->pricing->calculate($checkout->fresh());

    expect($result->shipping)->toBe(0);
});

it('skips shipping for digital-only carts', function (): void {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => ProductStatus::Active]);
    $digital = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 1000,
        'requires_shipping' => false,
    ]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $digital->id, 'quantity_on_hand' => 10, 'policy' => InventoryPolicy::Deny]);

    $cart = $this->cart->create($this->store);
    $this->cart->addLine($cart, $digital->id, 1);

    $result = $this->pricing->calculateForCart($cart->fresh()->load('lines.variant'), $this->store, shippingRateId: $this->rate->id);

    expect($result->shipping)->toBe(0);
});

it('ignores invalid discount codes silently during pricing', function (): void {
    $cart = $this->cart->create($this->store);
    $this->cart->addLine($cart, $this->variant->id, 1);

    $result = $this->pricing->calculateForCart(
        $cart->fresh()->load('lines.variant'),
        $this->store,
        discountCode: 'DOES-NOT-EXIST',
    );

    expect($result->discount)->toBe(0);
});
