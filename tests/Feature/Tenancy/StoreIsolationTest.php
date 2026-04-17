<?php

use App\Models\Cart;
use App\Models\Store;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('binds current_store in the container', function () {
    $ctx = createStoreContext();

    expect(app()->bound('current_store'))->toBeTrue();
    expect(app('current_store')->id)->toBe($ctx['store']->id);
});

test('StoreScope filters queries to current store', function () {
    $ctx = createStoreContext();
    $otherStore = Store::factory()->create();

    Cart::factory()->create(['store_id' => $ctx['store']->id]);
    Cart::factory()->create(['store_id' => $ctx['store']->id]);
    Cart::factory()->create(['store_id' => $otherStore->id]);

    $carts = Cart::all();

    expect($carts)->toHaveCount(2);
    expect($carts->pluck('store_id')->unique()->values()->all())->toBe([$ctx['store']->id]);
});

test('BelongsToStore trait auto-sets store_id on creation', function () {
    $ctx = createStoreContext();

    $cart = Cart::create([
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => 'active',
    ]);

    expect($cart->store_id)->toBe($ctx['store']->id);
});

test('prevents accessing another store records via StoreScope', function () {
    $ctx = createStoreContext();
    $otherStore = Store::factory()->create();

    $otherCart = Cart::factory()->create(['store_id' => $otherStore->id]);

    expect(Cart::find($otherCart->id))->toBeNull();
});

test('allows cross-store access when global scope is removed', function () {
    $ctx = createStoreContext();
    $otherStore = Store::factory()->create();

    Cart::factory()->create(['store_id' => $ctx['store']->id]);
    $otherCart = Cart::factory()->create(['store_id' => $otherStore->id]);

    $allCarts = Cart::withoutGlobalScopes()->get();

    expect($allCarts->count())->toBeGreaterThanOrEqual(2);
    expect(Cart::withoutGlobalScopes()->find($otherCart->id))->not->toBeNull();
});
