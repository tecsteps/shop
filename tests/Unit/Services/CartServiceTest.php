<?php

use App\Exceptions\CartVersionMismatchException;
use App\Models\Store;
use App\Services\CartService;

it('adds, updates, and removes cart lines while incrementing cart version', function () {
    $store = Store::factory()->create();
    $variant = createSellableVariant(
        $store,
        ['title' => 'Daily Tee'],
        ['price_amount' => 1500],
        ['quantity_on_hand' => 20],
    );

    $service = app(CartService::class);
    $cart = $service->create($store);

    $cart = $service->addLine($cart, $variant->id, 2, $cart->cart_version);

    expect($cart->cart_version)->toBe(2)
        ->and($cart->lines)->toHaveCount(1)
        ->and($cart->lines->first()?->quantity)->toBe(2);

    $lineId = $cart->lines->firstOrFail()->id;
    $cart = $service->updateLineQuantity($cart, $lineId, 5, $cart->cart_version);

    $totals = $service->totals($cart);

    expect($cart->cart_version)->toBe(3)
        ->and($cart->lines->first()?->quantity)->toBe(5)
        ->and($totals['subtotal'])->toBe(7500)
        ->and($totals['item_count'])->toBe(5);

    $cart = $service->removeLine($cart, $lineId, $cart->cart_version);

    expect($cart->cart_version)->toBe(4)
        ->and($cart->lines)->toHaveCount(0);
});

it('throws a conflict exception when expected cart version is stale', function () {
    $store = Store::factory()->create();
    $variant = createSellableVariant($store);

    $service = app(CartService::class);
    $cart = $service->create($store);
    $cart = $service->addLine($cart, $variant->id, 1, $cart->cart_version);

    expect(fn () => $service->addLine($cart, $variant->id, 1, 1))
        ->toThrow(CartVersionMismatchException::class);
});
