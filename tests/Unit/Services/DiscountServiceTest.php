<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\DiscountService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-02-14 12:00:00'));
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

function discountService(): DiscountService
{
    expect(class_exists(DiscountService::class))
        ->toBeTrue('App\\Services\\DiscountService is expected to exist.');

    /** @var DiscountService $service */
    $service = app(DiscountService::class);

    foreach (['validate', 'calculate'] as $method) {
        expect(method_exists($service, $method))
            ->toBeTrue(sprintf('DiscountService must expose %s(...).', $method));
    }

    return $service;
}

function discountServiceCreateStore(string $suffix = 'discount'): Store
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

function discountServiceCreateCart(Store $store, array $lineSubtotals): Cart
{
    $cart = Cart::query()->create([
        'store_id' => $store->id,
        'customer_id' => null,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => 'active',
    ]);

    foreach ($lineSubtotals as $index => $lineSubtotal) {
        $product = Product::query()->create([
            'store_id' => $store->id,
            'title' => 'Discount Product '.$index,
            'handle' => 'discount-product-'.$index,
            'status' => 'active',
            'description_html' => null,
            'vendor' => null,
            'product_type' => null,
            'tags' => [],
            'published_at' => now(),
        ]);

        $quantity = 1;
        $unitPrice = (int) $lineSubtotal;

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'SKU-DISC-'.$index,
            'barcode' => null,
            'price_amount' => $unitPrice,
            'compare_at_amount' => null,
            'currency' => 'EUR',
            'weight_g' => 200,
            'requires_shipping' => true,
            'is_default' => true,
            'position' => 1,
            'status' => 'active',
        ]);

        CartLine::query()->create([
            'cart_id' => $cart->id,
            'variant_id' => $variant->id,
            'quantity' => $quantity,
            'unit_price_amount' => $unitPrice,
            'line_subtotal_amount' => $lineSubtotal,
            'line_discount_amount' => 0,
            'line_total_amount' => $lineSubtotal,
        ]);
    }

    return $cart->fresh(['lines']) ?? $cart;
}

function discountServiceCreateDiscount(Store $store, array $overrides = []): Discount
{
    $defaults = [
        'store_id' => $store->id,
        'type' => DiscountType::Code,
        'code' => 'SAVE10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'usage_limit' => null,
        'usage_count' => 0,
        'rules_json' => [],
        'status' => DiscountStatus::Active,
    ];

    return Discount::query()->create(array_merge($defaults, $overrides));
}

function discountResultAmount(mixed $result): ?int
{
    $keys = ['amount', 'discount', 'discount_amount', 'total_discount', 'applied_amount'];

    if (is_array($result)) {
        foreach ($keys as $key) {
            if (array_key_exists($key, $result) && is_numeric($result[$key])) {
                return (int) $result[$key];
            }
        }
    }

    if (is_object($result)) {
        foreach ($keys as $key) {
            if (isset($result->{$key}) && is_numeric($result->{$key})) {
                return (int) $result->{$key};
            }
        }

        if (method_exists($result, 'toArray')) {
            /** @var array<string, mixed> $asArray */
            $asArray = $result->toArray();

            return discountResultAmount($asArray);
        }
    }

    return null;
}

/**
 * @return array<int, int>
 */
function discountResultLineAllocations(mixed $result): array
{
    if (is_array($result)) {
        $allocations = $result['line_allocations'] ?? $result['lineAllocations'] ?? [];

        if (is_array($allocations)) {
            /** @var array<int, int> $normalized */
            $normalized = [];

            foreach ($allocations as $lineId => $amount) {
                $normalized[(int) $lineId] = (int) $amount;
            }

            return $normalized;
        }
    }

    if (is_object($result)) {
        if (isset($result->lineAllocations) && is_array($result->lineAllocations)) {
            /** @var array<int, int> $allocations */
            $allocations = array_map(static fn (mixed $value): int => (int) $value, $result->lineAllocations);

            return $allocations;
        }

        if (method_exists($result, 'toArray')) {
            /** @var array<string, mixed> $asArray */
            $asArray = $result->toArray();

            return discountResultLineAllocations($asArray);
        }
    }

    return [];
}

test('validates an active discount code case insensitively', function (): void {
    $store = discountServiceCreateStore('validate-active');
    $cart = discountServiceCreateCart($store, [5000]);
    $discount = discountServiceCreateDiscount($store, [
        'code' => 'SUMMER20',
        'value_amount' => 20,
    ]);

    $service = discountService();
    $validated = $service->validate('summer20', $store, $cart);

    expect($validated)->toBeInstanceOf(Discount::class)
        ->and($validated->id)->toBe($discount->id);
});

test('rejects unknown discount codes', function (): void {
    $store = discountServiceCreateStore('unknown-code');
    $cart = discountServiceCreateCart($store, [2500]);
    $service = discountService();

    expect(fn () => $service->validate('DOESNOTEXIST', $store, $cart))
        ->toThrow(\App\Exceptions\InvalidDiscountException::class);
});

test('rejects expired discounts', function (): void {
    $store = discountServiceCreateStore('expired');
    $cart = discountServiceCreateCart($store, [2500]);
    discountServiceCreateDiscount($store, [
        'code' => 'OLD10',
        'ends_at' => now()->subMinute(),
    ]);

    $service = discountService();

    expect(fn () => $service->validate('OLD10', $store, $cart))
        ->toThrow(\App\Exceptions\InvalidDiscountException::class);
});

test('rejects discounts that reached usage limit', function (): void {
    $store = discountServiceCreateStore('usage-limit');
    $cart = discountServiceCreateCart($store, [2500]);
    discountServiceCreateDiscount($store, [
        'code' => 'MAXED',
        'usage_limit' => 10,
        'usage_count' => 10,
    ]);

    $service = discountService();

    expect(fn () => $service->validate('MAXED', $store, $cart))
        ->toThrow(\App\Exceptions\InvalidDiscountException::class);
});

test('rejects discounts when minimum purchase is not met', function (): void {
    $store = discountServiceCreateStore('minimum');
    $cart = discountServiceCreateCart($store, [3000]);
    discountServiceCreateDiscount($store, [
        'code' => 'MIN5000',
        'rules_json' => [
            'min_purchase_amount' => 5000,
            'minimum_purchase_amount' => 5000,
        ],
    ]);

    $service = discountService();

    expect(fn () => $service->validate('MIN5000', $store, $cart))
        ->toThrow(\App\Exceptions\InvalidDiscountException::class);
});

test('calculates percent discount amounts deterministically', function (): void {
    $store = discountServiceCreateStore('percent');
    $cart = discountServiceCreateCart($store, [7500, 2500]);
    $discount = discountServiceCreateDiscount($store, [
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
    ]);
    $service = discountService();

    $result = $service->calculate($discount, $cart);
    $allocations = discountResultLineAllocations($result);

    expect(discountResultAmount($result))
        ->toBe(1000, 'Expected 10% discount on 10,000 cents to equal 1,000 cents.')
        ->and(array_sum($allocations))->toBe(1000)
        ->and(count($allocations))->toBe(2);
});

test('caps fixed discount amounts at subtotal', function (): void {
    $store = discountServiceCreateStore('fixed-cap');
    $cart = discountServiceCreateCart($store, [300]);
    $discount = discountServiceCreateDiscount($store, [
        'value_type' => DiscountValueType::Fixed,
        'value_amount' => 500,
    ]);
    $service = discountService();

    $result = $service->calculate($discount, $cart);

    expect(discountResultAmount($result))
        ->toBe(300, 'Fixed discounts must never produce a negative discounted subtotal.');
});
