<?php

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TaxSettings;
use App\Services\CartService;
use App\Services\PricingEngine;

beforeEach(function (): void {
    $ctx = $this->createStoreContext();
    $this->store = $ctx['store'];
    $this->cart = app(CartService::class);

    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => ProductStatus::Active]);
    $this->variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 1000]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $this->variant->id,
        'quantity_on_hand' => 100,
        'policy' => InventoryPolicy::Deny,
    ]);
});

it('adds tax on top when prices exclude tax', function (): void {
    TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'prices_include_tax' => false,
        'config_json' => ['default_rate_bps' => 1900, 'rate_name' => 'VAT'],
    ]);

    $cart = $this->cart->create($this->store);
    $this->cart->addLine($cart, $this->variant->id, 1);

    $result = app(PricingEngine::class)->calculateForCart($cart->fresh()->load('lines'), $this->store);

    expect($result->subtotal)->toBe(1000);
    expect($result->taxTotal)->toBe(190);
    expect($result->total)->toBe(1190);
});

it('extracts tax from gross when prices include tax', function (): void {
    TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'prices_include_tax' => true,
        'config_json' => ['default_rate_bps' => 1900, 'rate_name' => 'VAT'],
    ]);

    // variant price stays at 1000 (gross when include_tax)
    $cart = $this->cart->create($this->store);
    $this->cart->addLine($cart, $this->variant->id, 1);

    $result = app(PricingEngine::class)->calculateForCart($cart->fresh()->load('lines'), $this->store);

    expect($result->subtotal)->toBe(1000);
    expect($result->taxTotal)->toBe(160); // 1000 - intdiv(1000*10000, 11900) = 1000 - 840 = 160
});

it('returns zero tax when no settings exist', function (): void {
    $cart = $this->cart->create($this->store);
    $this->cart->addLine($cart, $this->variant->id, 1);

    $result = app(PricingEngine::class)->calculateForCart($cart->fresh()->load('lines'), $this->store);

    expect($result->taxTotal)->toBe(0);
    expect($result->total)->toBe(1000);
});

it('reduces tax proportionally after discount', function (): void {
    TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'prices_include_tax' => false,
        'config_json' => ['default_rate_bps' => 1000],
    ]);

    $cart = $this->cart->create($this->store);
    $this->cart->addLine($cart, $this->variant->id, 1);

    $result = app(PricingEngine::class)->calculateForCart($cart->fresh()->load('lines'), $this->store);

    expect($result->taxTotal)->toBe(100);
});

it('handles region-specific rates', function (): void {
    TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'prices_include_tax' => false,
        'config_json' => [
            'default_rate_bps' => 0,
            'region_rates' => ['US-CA' => 800],
        ],
    ]);

    $cart = $this->cart->create($this->store);
    $this->cart->addLine($cart, $this->variant->id, 1);

    $result = app(PricingEngine::class)->calculateForCart(
        $cart->fresh()->load('lines'),
        $this->store,
        ['country_code' => 'US', 'province_code' => 'US-CA'],
    );

    expect($result->taxTotal)->toBe(80);
});

it('applies tax to shipping when in taxable amount', function (): void {
    TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'prices_include_tax' => false,
        'config_json' => ['default_rate_bps' => 1000],
    ]);

    $cart = $this->cart->create($this->store);
    $this->cart->addLine($cart, $this->variant->id, 1); // 1000 cents

    // No shipping selected, so only lines taxed
    $result = app(PricingEngine::class)->calculateForCart($cart->fresh()->load('lines'), $this->store);
    expect($result->taxTotal)->toBe(100);
});
