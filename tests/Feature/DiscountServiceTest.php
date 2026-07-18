<?php

use App\Enums\DiscountValueType;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\DiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('validates codes case insensitively and allocates percentage discounts', function () {
    $store = Store::factory()->create();
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    $product = Product::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'line_subtotal_amount' => 1999,
        'line_total_amount' => 1999,
    ]);
    $discount = Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'SAVE15',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 15,
    ]);

    $service = app(DiscountService::class);
    $validated = $service->validate('save15', $store, $cart);
    $result = $service->calculate($validated, 1999, $cart->lines()->with('variant.product.collections')->get());

    expect($validated->is($discount))->toBeTrue()
        ->and($result->amount)->toBe(300)
        ->and(array_sum($result->allocations))->toBe(300);
});

it('rejects discounts below their minimum purchase', function () {
    $store = Store::factory()->create();
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'MINIMUM',
        'rules_json' => ['min_purchase_amount' => 5000],
    ]);

    app(DiscountService::class)->validate('MINIMUM', $store, $cart);
})->throws(InvalidDiscountException::class, 'discount_min_purchase_not_met');
