<?php

use App\Enums\CartStatus;
use App\Enums\InventoryPolicy;
use App\Exceptions\CartVersionMismatchException;
use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\InvalidCartOperationException;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function cartServiceStore(): Store
{
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    return $store;
}

function cartServiceVariant(Store $store, int $price = 2500, int $stock = 20, InventoryPolicy $policy = InventoryPolicy::Deny): ProductVariant
{
    $product = Product::factory()
        ->withDefaultVariant($price)
        ->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::withoutGlobalScopes()
        ->where('product_id', $product->getKey())
        ->firstOrFail();

    InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $variant->getKey())
        ->update([
            'quantity_on_hand' => $stock,
            'quantity_reserved' => 0,
            'policy' => $policy,
        ]);

    return $variant->refresh();
}

test('cart service creates carts and mutates lines with version increments and price snapshots', function () {
    $store = cartServiceStore();
    $variant = cartServiceVariant($store, price: 2500, stock: 20);
    $service = app(CartService::class);
    $cart = $service->create($store);

    expect($cart->store_id)->toBe($store->getKey())
        ->and($cart->currency)->toBe('EUR')
        ->and($cart->cart_version)->toBe(1);

    $line = $service->addLine($cart, $variant->getKey(), 2, expectedVersion: 1);

    expect($line->quantity)->toBe(2)
        ->and($line->unit_price_amount)->toBe(2500)
        ->and($line->line_subtotal_amount)->toBe(5000)
        ->and($cart->refresh()->cart_version)->toBe(2);

    $service->addLine($cart, $variant->getKey(), 1, expectedVersion: 2);

    expect(Cart::withoutGlobalScopes()->find($cart->getKey())?->lines()->withoutGlobalScopes()->count())->toBe(1)
        ->and($line->refresh()->quantity)->toBe(3)
        ->and($cart->refresh()->cart_version)->toBe(3);

    $variant->forceFill(['price_amount' => 9999])->save();
    $service->updateLineQuantity($cart, $line->getKey(), 4, expectedVersion: 3);

    expect($line->refresh()->unit_price_amount)->toBe(2500)
        ->and($line->line_subtotal_amount)->toBe(10000)
        ->and($cart->refresh()->cart_version)->toBe(4);

    $service->updateLineQuantity($cart, $line->getKey(), 0, expectedVersion: 4);

    expect($cart->refresh()->cart_version)->toBe(5)
        ->and($cart->lines()->withoutGlobalScopes()->count())->toBe(0);
});

test('cart service rejects stale versions inactive products and insufficient stock', function () {
    $store = cartServiceStore();
    $variant = cartServiceVariant($store, stock: 2);
    $service = app(CartService::class);
    $cart = $service->create($store);

    expect(fn () => $service->addLine($cart, $variant->getKey(), 1, expectedVersion: 99))
        ->toThrow(CartVersionMismatchException::class);

    $draftProduct = Product::factory()
        ->draft()
        ->withDefaultVariant()
        ->create(['store_id' => $store->getKey()]);
    $draftVariant = ProductVariant::withoutGlobalScopes()
        ->where('product_id', $draftProduct->getKey())
        ->firstOrFail();

    expect(fn () => $service->addLine($cart, $draftVariant->getKey(), 1))
        ->toThrow(InvalidCartOperationException::class);

    expect(fn () => $service->addLine($cart, $variant->getKey(), 5))
        ->toThrow(InsufficientInventoryException::class);
});

test('cart service allows oversell for continue policy and merges guest carts into customer carts', function () {
    $store = cartServiceStore();
    $firstVariant = cartServiceVariant($store, stock: 0, policy: InventoryPolicy::Continue);
    $secondVariant = cartServiceVariant($store, stock: 10);
    $service = app(CartService::class);
    $guestCart = $service->create($store);
    $customer = Customer::factory()->create(['store_id' => $store->getKey()]);
    $customerCart = $service->create($store, $customer);

    $service->addLine($guestCart, $firstVariant->getKey(), 5);
    $service->addLine($customerCart, $firstVariant->getKey(), 2);
    $service->addLine($customerCart, $secondVariant->getKey(), 3);

    $merged = $service->mergeOnLogin($guestCart, $customerCart);

    expect($merged->lines()->withoutGlobalScopes()->where('variant_id', $firstVariant->getKey())->first()?->quantity)->toBe(5)
        ->and($merged->lines()->withoutGlobalScopes()->where('variant_id', $secondVariant->getKey())->first()?->quantity)->toBe(3)
        ->and($guestCart->refresh()->status)->toBe(CartStatus::Abandoned);
});
