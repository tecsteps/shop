<?php

use App\Exceptions\CartVersionConflictException;
use App\Exceptions\InvalidCartMutationException;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\CartService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function cartVariant(Store $store, int $price = 2500, int $stock = 10): ProductVariant
{
    $product = Product::factory()->for($store)->create();
    $variant = ProductVariant::factory()->for($product)->default()->create([
        'price_amount' => $price,
        'currency' => $store->default_currency,
    ]);

    InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $variant->id)
        ->update([
            'quantity_on_hand' => $stock,
            'quantity_reserved' => 0,
            'policy' => 'deny',
        ]);

    return $variant->refresh();
}

test('cart service creates and increments lines with optimistic versions', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);
    $variant = cartVariant($store, price: 1500, stock: 5);
    $cart = app(CartService::class)->create($store);

    $line = app(CartService::class)->addLine($cart, $variant->id, 2, expectedVersion: 1);

    expect($line->quantity)->toBe(2)
        ->and($line->line_total_amount)->toBe(3000)
        ->and($cart->refresh()->cart_version)->toBe(2);

    app(CartService::class)->addLine($cart->refresh(), $variant->id, 1, expectedVersion: 2);

    expect(Cart::withoutGlobalScopes()->find($cart->id)->lines()->first())
        ->quantity->toBe(3)
        ->line_total_amount->toBe(4500);
});

test('cart service rejects stale versions and unavailable stock', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);
    $variant = cartVariant($store, stock: 1);
    $cart = app(CartService::class)->create($store);

    expect(fn () => app(CartService::class)->addLine($cart, $variant->id, 1, expectedVersion: 99))
        ->toThrow(CartVersionConflictException::class);

    expect(fn () => app(CartService::class)->addLine($cart, $variant->id, 2, expectedVersion: 1))
        ->toThrow(InvalidCartMutationException::class);
});

test('cart service applies and removes persisted discounts', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);
    $variant = cartVariant($store, price: 2500, stock: 5);
    $cart = app(CartService::class)->create($store);

    Discount::factory()->for($store)->create([
        'code' => 'WELCOME10',
        'rules_json' => ['min_purchase_amount' => null],
    ]);

    app(CartService::class)->addLine($cart, $variant->id, 2, expectedVersion: 1);

    $cart = app(CartService::class)->applyDiscount($cart->refresh(), 'welcome10');

    expect($cart->discount_code)->toBe('WELCOME10')
        ->and($cart->discountAmount())->toBe(500)
        ->and($cart->totalAmount())->toBe(4500)
        ->and($cart->cart_version)->toBe(3);

    $line = $cart->lines->first();

    app(CartService::class)->updateLineQuantity($cart, $line->id, 1, expectedVersion: 3);

    $cart = app(CartService::class)->loadForDisplay($cart->refresh());

    expect($cart->discount_code)->toBe('WELCOME10')
        ->and($cart->discountAmount())->toBe(250)
        ->and($cart->totalAmount())->toBe(2250);

    $cart = app(CartService::class)->removeDiscount($cart);

    expect($cart->discount_code)->toBeNull()
        ->and($cart->discountAmount())->toBe(0)
        ->and($cart->totalAmount())->toBe(2500);
});

test('cart service clears stored discounts when cart no longer qualifies', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);
    $variant = cartVariant($store, price: 2500, stock: 5);
    $cart = app(CartService::class)->create($store);

    Discount::factory()->for($store)->create([
        'code' => 'MINIMUM',
        'rules_json' => ['min_purchase_amount' => 5000],
    ]);

    app(CartService::class)->addLine($cart, $variant->id, 2, expectedVersion: 1);
    $cart = app(CartService::class)->applyDiscount($cart->refresh(), 'minimum');

    app(CartService::class)->updateLineQuantity($cart, $cart->lines->first()->id, 1, expectedVersion: 3);

    $cart = app(CartService::class)->loadForDisplay($cart->refresh());

    expect($cart->discount_code)->toBeNull()
        ->and($cart->discountAmount())->toBe(0)
        ->and($cart->totalAmount())->toBe(2500);
});
