<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\PricingEngine;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-02-14 12:00:00'));
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

function pricingEngineService(): PricingEngine
{
    expect(class_exists(PricingEngine::class))
        ->toBeTrue('App\\Services\\PricingEngine is expected to exist.');

    /** @var PricingEngine $engine */
    $engine = app(PricingEngine::class);

    expect(method_exists($engine, 'calculate'))
        ->toBeTrue('PricingEngine must expose calculate(...).');

    return $engine;
}

function pricingEngineCreateStore(string $suffix): Store
{
    $organization = Organization::query()->create([
        'name' => 'Org '.$suffix,
        'billing_email' => 'billing+'.$suffix.'@example.test',
    ]);

    return Store::query()->create([
        'organization_id' => $organization->id,
        'name' => 'Store '.$suffix,
        'handle' => 'store-'.$suffix,
        'status' => 'active',
        'default_currency' => 'EUR',
        'default_locale' => 'en',
        'timezone' => 'UTC',
    ]);
}

/**
 * @param  array<int, array{unit_price:int, quantity:int}>  $lineDefinitions
 */
function pricingEngineCreateCheckout(Store $store, array $lineDefinitions, ?string $discountCode = null): Checkout
{
    $cart = Cart::query()->create([
        'store_id' => $store->id,
        'customer_id' => null,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => 'active',
    ]);

    foreach ($lineDefinitions as $index => $lineDefinition) {
        $product = Product::query()->create([
            'store_id' => $store->id,
            'title' => 'Pricing Product '.$index,
            'handle' => 'pricing-product-'.$index,
            'status' => 'active',
            'description_html' => null,
            'vendor' => null,
            'product_type' => null,
            'tags' => [],
            'published_at' => now(),
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'SKU-PRC-'.$index,
            'barcode' => null,
            'price_amount' => $lineDefinition['unit_price'],
            'compare_at_amount' => null,
            'currency' => 'EUR',
            'weight_g' => 250,
            'requires_shipping' => true,
            'is_default' => true,
            'position' => 1,
            'status' => 'active',
        ]);

        $lineSubtotal = $lineDefinition['unit_price'] * $lineDefinition['quantity'];

        CartLine::query()->create([
            'cart_id' => $cart->id,
            'variant_id' => $variant->id,
            'quantity' => $lineDefinition['quantity'],
            'unit_price_amount' => $lineDefinition['unit_price'],
            'line_subtotal_amount' => $lineSubtotal,
            'line_discount_amount' => 0,
            'line_total_amount' => $lineSubtotal,
        ]);
    }

    return Checkout::query()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'customer_id' => null,
        'status' => 'started',
        'payment_method' => null,
        'email' => 'checkout@example.test',
        'shipping_address_json' => null,
        'billing_address_json' => null,
        'shipping_method_id' => null,
        'discount_code' => $discountCode,
        'tax_provider_snapshot_json' => null,
        'totals_json' => null,
        'expires_at' => null,
    ]);
}

function pricingEngineCreateDiscount(Store $store, string $code, DiscountValueType $valueType, int $valueAmount): Discount
{
    return Discount::query()->create([
        'store_id' => $store->id,
        'type' => DiscountType::Code,
        'code' => $code,
        'value_type' => $valueType,
        'value_amount' => $valueAmount,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'usage_limit' => null,
        'usage_count' => 0,
        'rules_json' => [],
        'status' => DiscountStatus::Active,
    ]);
}

function pricingResultValue(mixed $result, array $keys): mixed
{
    if (is_array($result)) {
        foreach ($keys as $key) {
            if (array_key_exists($key, $result)) {
                return $result[$key];
            }
        }
    }

    if (is_object($result)) {
        foreach ($keys as $key) {
            if (isset($result->{$key})) {
                return $result->{$key};
            }
        }

        foreach ($keys as $key) {
            $getter = 'get'.str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));

            if (method_exists($result, $getter)) {
                return $result->{$getter}();
            }
        }

        if (method_exists($result, 'toArray')) {
            /** @var array<string, mixed> $asArray */
            $asArray = $result->toArray();

            return pricingResultValue($asArray, $keys);
        }
    }

    return null;
}

function pricingResultSummary(mixed $result): array
{
    return [
        'subtotal' => (int) pricingResultValue($result, ['subtotal']),
        'discount' => (int) pricingResultValue($result, ['discount', 'discount_amount', 'total_discount']),
        'shipping' => (int) pricingResultValue($result, ['shipping', 'shipping_amount']),
        'tax' => (int) pricingResultValue($result, ['tax', 'tax_total', 'taxTotal']),
        'total' => (int) pricingResultValue($result, ['total']),
        'currency' => (string) pricingResultValue($result, ['currency']),
    ];
}

test('calculates subtotal and total for a checkout without shipping or discount', function (): void {
    $store = pricingEngineCreateStore('subtotal');
    $checkout = pricingEngineCreateCheckout($store, [
        ['unit_price' => 1000, 'quantity' => 2],
        ['unit_price' => 2500, 'quantity' => 1],
    ]);
    $engine = pricingEngineService();

    $result = $engine->calculate($checkout);

    $summary = pricingResultSummary($result);

    expect($summary['subtotal'])->toBe(4500)
        ->and($summary['discount'])->toBe(0)
        ->and($summary['shipping'])->toBe(0)
        ->and($summary['tax'])->toBe(0)
        ->and($summary['total'])->toBe(4500)
        ->and($summary['currency'])->toBe('EUR');
});

test('caps fixed discount so totals never go negative', function (): void {
    $store = pricingEngineCreateStore('fixed-cap');
    pricingEngineCreateDiscount($store, 'BIGSAVE', DiscountValueType::Fixed, 500);
    $checkout = pricingEngineCreateCheckout($store, [
        ['unit_price' => 300, 'quantity' => 1],
    ], discountCode: 'BIGSAVE');

    $engine = pricingEngineService();
    $result = $engine->calculate($checkout);
    $summary = pricingResultSummary($result);

    expect($summary['subtotal'])->toBe(300)
        ->and($summary['discount'])->toBe(300)
        ->and($summary['total'])->toBe(0);
});

test('returns identical outputs for identical inputs', function (): void {
    $store = pricingEngineCreateStore('deterministic');
    pricingEngineCreateDiscount($store, 'TENPERCENT', DiscountValueType::Percent, 10);
    $checkout = pricingEngineCreateCheckout($store, [
        ['unit_price' => 2499, 'quantity' => 2],
        ['unit_price' => 799, 'quantity' => 1],
    ], discountCode: 'TENPERCENT');

    $engine = pricingEngineService();

    $first = pricingResultSummary($engine->calculate($checkout));
    $second = pricingResultSummary($engine->calculate($checkout));

    expect($first)->toBe($second);
});
