<?php

use App\Exceptions\CartVersionConflictException;
use App\Models\Cart;
use App\Services\CartService;
use App\Services\InventoryService;

it('rejects stale cart versions', function () {
    $cart = new Cart(['cart_version' => 3]);
    $service = new CartService(new InventoryService);

    expect(fn () => $service->assertExpectedVersion($cart, 2))->toThrow(CartVersionConflictException::class)
        ->and(fn () => $service->assertExpectedVersion($cart, 3))->not->toThrow(CartVersionConflictException::class);
});
