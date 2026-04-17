<?php

use App\Enums\CheckoutStatus;
use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Enums\ProductStatus;
use App\Enums\ShippingRateType;
use App\Enums\TaxMode;
use App\Enums\VariantStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\PricingEngine;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->engine = app(PricingEngine::class);
});

it('calculates subtotal from multiple line items', function () {
    $checkout = buildCheckout([
        ['price' => 2000, 'qty' => 2],
        ['price' => 3000, 'qty' => 1],
    ]);

    $result = $this->engine->calculate($checkout);

    expect($result->subtotal)->toBe(7000);
    expect($result->discount)->toBe(0);
    expect($result->total)->toBe(7000);
});

it('calculates subtotal for single line', function () {
    $checkout = buildCheckout([
        ['price' => 5000, 'qty' => 3],
    ]);

    $result = $this->engine->calculate($checkout);

    expect($result->subtotal)->toBe(15000);
});

it('handles empty cart with zero totals', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    $result = $this->engine->calculate($checkout);

    expect($result->subtotal)->toBe(0);
    expect($result->total)->toBe(0);
});

it('applies percent discount', function () {
    Discount::create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => 'PCT10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subDay(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $checkout = buildCheckout([
        ['price' => 10000, 'qty' => 1],
    ], discountCode: 'PCT10');

    $result = $this->engine->calculate($checkout);

    expect($result->subtotal)->toBe(10000);
    expect($result->discount)->toBe(1000);
    expect($result->total)->toBe(9000);
});

it('applies fixed discount', function () {
    Discount::create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => 'FIXED',
        'value_type' => DiscountValueType::Fixed,
        'value_amount' => 500,
        'starts_at' => now()->subDay(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $checkout = buildCheckout([
        ['price' => 5000, 'qty' => 1],
    ], discountCode: 'FIXED');

    $result = $this->engine->calculate($checkout);

    expect($result->discount)->toBe(500);
    expect($result->total)->toBe(4500);
});

it('caps fixed discount at subtotal', function () {
    Discount::create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => 'HUGE',
        'value_type' => DiscountValueType::Fixed,
        'value_amount' => 99999,
        'starts_at' => now()->subDay(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $checkout = buildCheckout([
        ['price' => 1000, 'qty' => 1],
    ], discountCode: 'HUGE');

    $result = $this->engine->calculate($checkout);

    expect($result->discount)->toBe(1000);
    expect($result->total)->toBe(0);
});

it('applies free shipping discount', function () {
    Discount::create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => 'FREESHIP',
        'value_type' => DiscountValueType::FreeShipping,
        'value_amount' => 0,
        'starts_at' => now()->subDay(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $zone = ShippingZone::create([
        'store_id' => $this->store->id,
        'name' => 'Zone',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);
    $rate = ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Standard',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 599],
        'is_active' => true,
    ]);

    $checkout = buildCheckout(
        [['price' => 5000, 'qty' => 1]],
        discountCode: 'FREESHIP',
        shippingRateId: $rate->id,
        shippingAddress: ['country' => 'DE'],
    );

    $result = $this->engine->calculate($checkout);

    expect($result->discount)->toBe(0);
    expect($result->shipping)->toBe(0);
});

it('calculates exclusive tax', function () {
    TaxSettings::create([
        'store_id' => $this->store->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => ['default_rate' => 1900, 'default_name' => 'VAT'],
    ]);

    $checkout = buildCheckout([
        ['price' => 10000, 'qty' => 1],
    ], shippingAddress: ['country' => 'DE']);

    $result = $this->engine->calculate($checkout);

    expect($result->subtotal)->toBe(10000);
    expect($result->taxTotal)->toBe(1900);
    expect($result->total)->toBe(11900);
    expect($result->taxLines)->toHaveCount(1);
    expect($result->taxLines[0]->name)->toBe('VAT');
});

it('calculates inclusive tax without changing total', function () {
    TaxSettings::create([
        'store_id' => $this->store->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => true,
        'config_json' => ['default_rate' => 1900, 'default_name' => 'VAT'],
    ]);

    $checkout = buildCheckout([
        ['price' => 11900, 'qty' => 1],
    ], shippingAddress: ['country' => 'DE']);

    $result = $this->engine->calculate($checkout);

    expect($result->subtotal)->toBe(11900);
    // Tax is extracted from the price, not added
    expect($result->taxTotal)->toBe(1900);
});

it('handles zero tax rate', function () {
    TaxSettings::create([
        'store_id' => $this->store->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => ['default_rate' => 0],
    ]);

    $checkout = buildCheckout([
        ['price' => 5000, 'qty' => 1],
    ], shippingAddress: ['country' => 'DE']);

    $result = $this->engine->calculate($checkout);

    expect($result->taxTotal)->toBe(0);
    expect($result->taxLines)->toBeEmpty();
    expect($result->total)->toBe(5000);
});

it('calculates shipping with flat rate', function () {
    $zone = ShippingZone::create([
        'store_id' => $this->store->id,
        'name' => 'Domestic',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);
    $rate = ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Standard',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);

    $checkout = buildCheckout(
        [['price' => 5000, 'qty' => 1]],
        shippingRateId: $rate->id,
    );

    $result = $this->engine->calculate($checkout);

    expect($result->shipping)->toBe(499);
    expect($result->total)->toBe(5499);
});

it('calculates full end-to-end totals', function () {
    TaxSettings::create([
        'store_id' => $this->store->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => ['default_rate' => 1900, 'default_name' => 'VAT'],
    ]);

    Discount::create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => 'SAVE10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subDay(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $zone = ShippingZone::create([
        'store_id' => $this->store->id,
        'name' => 'Domestic',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);
    $rate = ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Standard',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);

    $checkout = buildCheckout(
        [['price' => 10000, 'qty' => 1]],
        discountCode: 'SAVE10',
        shippingRateId: $rate->id,
        shippingAddress: ['country' => 'DE'],
    );

    $result = $this->engine->calculate($checkout);

    // subtotal=10000, discount=1000, discountedSubtotal=9000
    // shipping=499, tax on 9000 = round(9000*1900/10000)=1710
    // total = 9000 + 499 + 1710 = 11209
    expect($result->subtotal)->toBe(10000);
    expect($result->discount)->toBe(1000);
    expect($result->shipping)->toBe(499);
    expect($result->taxTotal)->toBe(1710);
    expect($result->total)->toBe(11209);
    expect($result->currency)->toBe($this->store->default_currency);
});

it('handles rounding with odd cent amounts', function () {
    Discount::create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => 'ODD',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 33,
        'starts_at' => now()->subDay(),
        'status' => DiscountStatus::Active,
        'rules_json' => [],
    ]);

    $checkout = buildCheckout([
        ['price' => 999, 'qty' => 1],
    ], discountCode: 'ODD');

    $result = $this->engine->calculate($checkout);

    // 33% of 999 = round(329.67) = 330
    expect($result->discount)->toBe(330);
    expect($result->total)->toBe(669);
});

it('produces identical results for identical inputs', function () {
    $checkout = buildCheckout([
        ['price' => 5000, 'qty' => 2],
    ]);

    $result1 = $this->engine->calculate($checkout);
    $result2 = $this->engine->calculate($checkout);

    expect($result1->subtotal)->toBe($result2->subtotal);
    expect($result1->total)->toBe($result2->total);
    expect($result1->discount)->toBe($result2->discount);
});

// --- Helper ---

function buildCheckout(
    array $items,
    ?string $discountCode = null,
    ?int $shippingRateId = null,
    ?array $shippingAddress = null,
): Checkout {
    $store = app('current_store');
    $cart = Cart::factory()->create([
        'store_id' => $store->id,
        'currency' => $store->default_currency,
    ]);

    foreach ($items as $item) {
        $product = Product::factory()->create([
            'store_id' => $store->id,
            'status' => ProductStatus::Active,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_amount' => $item['price'],
            'status' => VariantStatus::Active,
        ]);
        CartLine::factory()->create([
            'cart_id' => $cart->id,
            'variant_id' => $variant->id,
            'quantity' => $item['qty'],
            'unit_price_amount' => $item['price'],
            'line_subtotal_amount' => $item['price'] * $item['qty'],
            'line_discount_amount' => 0,
            'line_total_amount' => $item['price'] * $item['qty'],
        ]);
    }

    return Checkout::factory()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
        'discount_code' => $discountCode,
        'shipping_method_id' => $shippingRateId,
        'shipping_address_json' => $shippingAddress,
    ]);
}
