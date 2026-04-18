<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Exceptions\InvalidDiscountException;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\DiscountService;

beforeEach(function (): void {
    $ctx = $this->createStoreContext();
    $this->store = $ctx['store'];
    $this->cartService = app(CartService::class);
    $this->discountService = app(DiscountService::class);

    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => ProductStatus::Active]);
    $this->variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 1000]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $this->variant->id,
        'quantity_on_hand' => 100,
        'policy' => InventoryPolicy::Deny,
    ]);
});

it('looks up codes case-insensitively', function (): void {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'SUMMER20',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 20,
    ]);

    expect($this->discountService->validate('summer20', $this->store))->toBeInstanceOf(Discount::class);
});

it('throws not_found for missing codes', function (): void {
    expect(fn () => $this->discountService->validate('BOGUS', $this->store))
        ->toThrow(InvalidDiscountException::class);
});

it('throws not_yet_active when starts_at is in the future', function (): void {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'FUTURE',
        'starts_at' => now()->addDay(),
    ]);

    try {
        $this->discountService->validate('FUTURE', $this->store);
        $this->fail('Expected InvalidDiscountException');
    } catch (InvalidDiscountException $e) {
        expect($e->reason)->toBe('not_yet_active');
    }
});

it('throws expired when ends_at is in the past', function (): void {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'OLD',
        'starts_at' => now()->subWeek(),
        'ends_at' => now()->subDay(),
    ]);

    try {
        $this->discountService->validate('OLD', $this->store);
        $this->fail('Expected InvalidDiscountException');
    } catch (InvalidDiscountException $e) {
        expect($e->reason)->toBe('expired');
    }
});

it('throws usage_limit_reached when hit', function (): void {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'LIMITED',
        'usage_limit' => 5,
        'usage_count' => 5,
    ]);

    try {
        $this->discountService->validate('LIMITED', $this->store);
        $this->fail('Expected InvalidDiscountException');
    } catch (InvalidDiscountException $e) {
        expect($e->reason)->toBe('usage_limit_reached');
    }
});

it('enforces minimum purchase amount', function (): void {
    $discount = Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'MIN50',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'rules_json' => ['min_purchase_amount' => 5000],
    ]);

    $cart = $this->cartService->create($this->store);
    $this->cartService->addLine($cart, $this->variant->id, 1); // 1000 cents

    try {
        $this->discountService->validate('MIN50', $this->store, $cart->fresh()->load('lines'));
        $this->fail('Expected InvalidDiscountException');
    } catch (InvalidDiscountException $e) {
        expect($e->reason)->toBe('minimum_not_met');
    }
});

it('calculates percent discounts correctly', function (): void {
    $discount = Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'TENPCT',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
    ]);

    $cart = $this->cartService->create($this->store);
    $this->cartService->addLine($cart, $this->variant->id, 3); // 3000 cents

    $result = $this->discountService->calculate($discount, $cart->fresh()->load('lines'));
    expect($result['total'])->toBe(300);
});

it('caps fixed discounts at qualifying subtotal', function (): void {
    $discount = Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'CAPIT',
        'value_type' => DiscountValueType::Fixed,
        'value_amount' => 10000,
    ]);

    $cart = $this->cartService->create($this->store);
    $this->cartService->addLine($cart, $this->variant->id, 2); // 2000 cents

    $result = $this->discountService->calculate($discount, $cart->fresh()->load('lines'));
    expect($result['total'])->toBe(2000);
});

it('returns zero when free_shipping discount', function (): void {
    $discount = Discount::factory()->freeShipping()->create([
        'store_id' => $this->store->id,
        'code' => 'FREESHIP',
    ]);

    $cart = $this->cartService->create($this->store);
    $this->cartService->addLine($cart, $this->variant->id, 1);

    $result = $this->discountService->calculate($discount, $cart->fresh()->load('lines'));
    expect($result['total'])->toBe(0);
});

it('rejects non-active statuses', function (): void {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'OFF',
        'status' => DiscountStatus::Disabled,
    ]);

    try {
        $this->discountService->validate('OFF', $this->store);
        $this->fail('Expected InvalidDiscountException');
    } catch (InvalidDiscountException $e) {
        expect($e->reason)->toBe('expired');
    }
});
