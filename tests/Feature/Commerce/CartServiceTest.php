<?php

use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\CartVersionConflictException;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\CartService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function makeCartContext(int $onHand = 10, int $price = 1000): array
{
    $store = Store::factory()->create();
    $product = Product::factory()->active()->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->getKey(),
        'price_amount' => $price,
        'requires_shipping' => 1,
        'weight_g' => 500,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $store->getKey(),
        'variant_id' => $variant->getKey(),
        'quantity_on_hand' => $onHand,
    ]);

    return [$store, $product, $variant];
}

it('creates a cart with store currency and version 1', function () {
    [$store] = makeCartContext();

    $cart = app(CartService::class)->create($store);

    expect($cart->currency)->toBe($store->default_currency)
        ->and($cart->cart_version)->toBe(1)
        ->and($cart->status->value)->toBe('active');
});

it('adds a line and increments cart version', function () {
    [$store, , $variant] = makeCartContext(10, 1500);
    $service = app(CartService::class);
    $cart = $service->create($store);

    $line = $service->addLine($cart, (int) $variant->getKey(), 2);
    $cart->refresh();

    expect($line->quantity)->toBe(2)
        ->and($line->unit_price_amount)->toBe(1500)
        ->and($line->line_subtotal_amount)->toBe(3000)
        ->and($cart->cart_version)->toBe(2);
});

it('merges duplicate variants into the same cart line', function () {
    [$store, , $variant] = makeCartContext();
    $service = app(CartService::class);
    $cart = $service->create($store);

    $service->addLine($cart, (int) $variant->getKey(), 1);
    $service->addLine($cart, (int) $variant->getKey(), 2);

    expect($cart->lines()->count())->toBe(1)
        ->and($cart->lines()->first()->quantity)->toBe(3);
});

it('rejects adding a non-active product', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->create([
        'store_id' => $store->getKey(),
        'status' => ProductStatus::Draft->value,
    ]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->getKey()]);
    InventoryItem::factory()->create([
        'store_id' => $store->getKey(),
        'variant_id' => $variant->getKey(),
        'quantity_on_hand' => 10,
    ]);

    $cart = app(CartService::class)->create($store);

    app(CartService::class)->addLine($cart, (int) $variant->getKey(), 1);
})->throws(RuntimeException::class, 'Product is not active');

it('rejects adding when inventory is insufficient', function () {
    [$store, , $variant] = makeCartContext(onHand: 1);

    $cart = app(CartService::class)->create($store);

    app(CartService::class)->addLine($cart, (int) $variant->getKey(), 5);
})->throws(InsufficientInventoryException::class);

it('rejects adding when variant is archived', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->active()->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->getKey(),
        'status' => VariantStatus::Archived->value,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $store->getKey(),
        'variant_id' => $variant->getKey(),
        'quantity_on_hand' => 10,
    ]);

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, (int) $variant->getKey(), 1);
})->throws(RuntimeException::class, 'Variant is not active');

it('updates line quantity and recalculates amounts', function () {
    [$store, , $variant] = makeCartContext(10, 800);
    $service = app(CartService::class);
    $cart = $service->create($store);
    $line = $service->addLine($cart, (int) $variant->getKey(), 1);

    $updated = $service->updateLineQuantity($cart, (int) $line->getKey(), 3);

    expect($updated->quantity)->toBe(3)
        ->and($updated->line_subtotal_amount)->toBe(2400);
});

it('removes the line when quantity updates to zero', function () {
    [$store, , $variant] = makeCartContext();
    $service = app(CartService::class);
    $cart = $service->create($store);
    $line = $service->addLine($cart, (int) $variant->getKey(), 2);

    $result = $service->updateLineQuantity($cart, (int) $line->getKey(), 0);

    expect($result)->toBeNull()
        ->and($cart->lines()->count())->toBe(0);
});

it('throws a cart version conflict when expected version does not match', function () {
    [$store, , $variant] = makeCartContext();
    $service = app(CartService::class);
    $cart = $service->create($store);

    $service->addLine($cart, (int) $variant->getKey(), 1, expectedVersion: 99);
})->throws(CartVersionConflictException::class);

it('merges a guest cart into a customer cart taking max quantity', function () {
    [$store, , $variant] = makeCartContext(20, 500);
    $service = app(CartService::class);
    $guest = $service->create($store);
    $customer = $service->create($store);

    $service->addLine($guest, (int) $variant->getKey(), 2);
    $service->addLine($customer, (int) $variant->getKey(), 1);

    $merged = $service->mergeOnLogin($guest, $customer);

    expect($merged->lines()->count())->toBe(1)
        ->and($merged->lines()->first()->quantity)->toBe(2)
        ->and($guest->refresh()->status->value)->toBe('abandoned');
});
