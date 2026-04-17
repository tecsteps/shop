<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\InvalidDiscountException;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\DiscountService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->discountService = app(DiscountService::class);
    $this->cartService = app(CartService::class);
});

it('validates a valid discount code', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 5000,
        'status' => VariantStatus::Active,
    ]);
    $this->cartService->addLine($cart, $variant->id, 1);

    $discount = Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'SAVE10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'status' => DiscountStatus::Active,
        'starts_at' => now()->subDay(),
    ]);

    $result = $this->discountService->validate('save10', $this->store, $cart->fresh());

    expect($result->id)->toBe($discount->id);
});

it('rejects a non-existent discount code', function () {
    $cart = $this->cartService->create($this->store);

    $this->discountService->validate('NONEXISTENT', $this->store, $cart);
})->throws(InvalidDiscountException::class, 'discount_not_found');

it('rejects an inactive discount', function () {
    $cart = $this->cartService->create($this->store);

    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'DRAFT10',
        'status' => DiscountStatus::Draft,
    ]);

    $this->discountService->validate('DRAFT10', $this->store, $cart);
})->throws(InvalidDiscountException::class, 'discount_expired');

it('rejects a discount that has not started yet', function () {
    $cart = $this->cartService->create($this->store);

    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'FUTURE',
        'status' => DiscountStatus::Active,
        'starts_at' => now()->addWeek(),
    ]);

    $this->discountService->validate('FUTURE', $this->store, $cart);
})->throws(InvalidDiscountException::class, 'discount_not_yet_active');

it('rejects a discount past its end date', function () {
    $cart = $this->cartService->create($this->store);

    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'ENDED',
        'status' => DiscountStatus::Active,
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->subDay(),
    ]);

    $this->discountService->validate('ENDED', $this->store, $cart);
})->throws(InvalidDiscountException::class, 'discount_expired');

it('rejects a discount that exceeded usage limit', function () {
    $cart = $this->cartService->create($this->store);

    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'LIMITED',
        'status' => DiscountStatus::Active,
        'starts_at' => now()->subDay(),
        'usage_limit' => 5,
        'usage_count' => 5,
    ]);

    $this->discountService->validate('LIMITED', $this->store, $cart);
})->throws(InvalidDiscountException::class, 'discount_usage_limit_reached');

it('rejects when minimum purchase not met', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 1000,
        'status' => VariantStatus::Active,
    ]);
    $this->cartService->addLine($cart, $variant->id, 1);

    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'MINPURCHASE',
        'status' => DiscountStatus::Active,
        'starts_at' => now()->subDay(),
        'rules_json' => ['min_purchase_amount' => 5000],
    ]);

    $this->discountService->validate('MINPURCHASE', $this->store, $cart->fresh());
})->throws(InvalidDiscountException::class, 'discount_min_purchase_not_met');

it('calculates a percent discount', function () {
    $discount = Discount::factory()->create([
        'store_id' => $this->store->id,
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 20,
    ]);

    $lines = [
        ['line_id' => 1, 'line_subtotal_amount' => 3000, 'product_id' => 1, 'collection_ids' => []],
        ['line_id' => 2, 'line_subtotal_amount' => 2000, 'product_id' => 2, 'collection_ids' => []],
    ];

    $result = $this->discountService->calculate($discount, 5000, $lines);

    expect($result['total_discount'])->toBe(1000)
        ->and($result['line_allocations'][1])->toBe(600)
        ->and($result['line_allocations'][2])->toBe(400);
});

it('calculates a fixed discount capped at subtotal', function () {
    $discount = Discount::factory()->fixedAmount(10000)->create([
        'store_id' => $this->store->id,
    ]);

    $lines = [
        ['line_id' => 1, 'line_subtotal_amount' => 3000, 'product_id' => 1, 'collection_ids' => []],
    ];

    $result = $this->discountService->calculate($discount, 3000, $lines);

    expect($result['total_discount'])->toBe(3000);
});

it('returns free shipping flag for free_shipping discount', function () {
    $discount = Discount::factory()->freeShipping()->create([
        'store_id' => $this->store->id,
    ]);

    $result = $this->discountService->calculate($discount, 5000, []);

    expect($result['free_shipping'])->toBeTrue()
        ->and($result['total_discount'])->toBe(0);
});
