<?php

use App\Enums\CartStatus;
use App\Exceptions\CartVersionConflictException;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->product = Product::factory()->for($this->store)->create();
    $this->variant = ProductVariant::factory()->for($this->product)->create(['price_amount' => 2500]);
    $this->variant->inventoryItem->update(['quantity_on_hand' => 20]);
    $this->cartService = app(CartService::class);
    $this->cart = $this->cartService->create($this->store);
});

it('creates and mutates a versioned cart with integer amounts', function () {
    expect($this->cart->currency)->toBe($this->store->default_currency)
        ->and($this->cart->cart_version)->toBe(1);

    $line = $this->cartService->addLine($this->cart->refresh(), $this->variant->id, 2, 1);
    expect($line->quantity)->toBe(2)
        ->and($line->line_subtotal_amount)->toBe(5000)
        ->and($this->cart->refresh()->cart_version)->toBe(2);

    $line = $this->cartService->updateLineQuantity($this->cart->refresh(), $line->id, 3, 2);
    expect($line->line_total_amount)->toBe(7500)
        ->and($this->cart->refresh()->cart_version)->toBe(3);

    $this->cartService->removeLine($this->cart->refresh(), $line->id, 3);
    expect($this->cart->lines()->count())->toBe(0)
        ->and($this->cart->refresh()->cart_version)->toBe(4);
});

it('increments an existing line and rejects stale mutations', function () {
    $line = $this->cartService->addLine($this->cart, $this->variant->id, 1);
    $sameLine = $this->cartService->addLine($this->cart->refresh(), $this->variant->id, 2);

    expect($sameLine->id)->toBe($line->id)
        ->and($sameLine->quantity)->toBe(3)
        ->and(fn () => $this->cartService->updateLineQuantity($this->cart->refresh(), $line->id, 4, 1))
        ->toThrow(CartVersionConflictException::class);
});

it('merges guest carts using the higher duplicate quantity', function () {
    $guest = $this->cart;
    $this->cartService->addLine($guest, $this->variant->id, 2);
    $customer = Cart::factory()->for($this->store)->create();
    $this->cartService->addLine($customer, $this->variant->id, 5);

    $merged = $this->cartService->mergeOnLogin($guest->refresh(), $customer->refresh());

    expect($merged->lines)->toHaveCount(1)
        ->and($merged->lines->first()->quantity)->toBe(5)
        ->and($guest->refresh()->status)->toBe(CartStatus::Abandoned);
});
