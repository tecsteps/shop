<?php

use App\Exceptions\CartVersionMismatchException;
use App\Models\Store;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('starts at version 1', function () {
    $store = Store::factory()->create();

    $cart = app(CartService::class)->create($store);

    expect($cart->cart_version)->toBe(1);
});

it('increments version on add line', function () {
    $store = Store::factory()->create();
    $variant = createPurchasableVariant($store);

    $service = app(CartService::class);
    $cart = $service->create($store);

    $service->addLine($cart, $variant->getKey(), 1);

    expect($cart->refresh()->cart_version)->toBe(2);
});

it('increments version on update quantity', function () {
    $store = Store::factory()->create();
    $variant = createPurchasableVariant($store);

    $service = app(CartService::class);
    $cart = $service->create($store);
    $line = $service->addLine($cart, $variant->getKey(), 1);

    $versionBefore = $cart->refresh()->cart_version;

    $service->updateLineQuantity($cart, $line->getKey(), 3);

    expect($cart->refresh()->cart_version)->toBe($versionBefore + 1);
});

it('increments version on remove line', function () {
    $store = Store::factory()->create();
    $variant = createPurchasableVariant($store);

    $service = app(CartService::class);
    $cart = $service->create($store);
    $line = $service->addLine($cart, $variant->getKey(), 1);

    $versionBefore = $cart->refresh()->cart_version;

    $service->removeLine($cart, $line->getKey());

    expect($cart->refresh()->cart_version)->toBe($versionBefore + 1);
});

it('detects version mismatch', function () {
    $store = Store::factory()->create();

    $service = app(CartService::class);
    $cart = $service->create($store);
    $cart->update(['cart_version' => 3]);

    $service->assertVersion($cart->refresh(), 2);
})->throws(CartVersionMismatchException::class);
