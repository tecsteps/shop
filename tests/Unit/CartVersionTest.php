<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\CartService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->service = new CartService(new InventoryService);
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('starts at version 1', function (): void {
    $cart = $this->service->create($this->store);
    expect($cart->cart_version)->toBe(1);
});

it('increments on add line', function (): void {
    $cart = $this->service->create($this->store);
    $variant = ProductVariant::factory()
        ->for(Product::factory()->for($this->store))
        ->create(['price_amount' => 1000]);

    $this->service->addLine($cart, $variant->id, 1);

    expect($cart->fresh()->cart_version)->toBe(2);
});

it('increments on update quantity', function (): void {
    $cart = $this->service->create($this->store);
    $variant = ProductVariant::factory()
        ->for(Product::factory()->for($this->store))
        ->create(['price_amount' => 1000]);
    $line = $this->service->addLine($cart, $variant->id, 1);

    $this->service->updateLineQuantity($cart->fresh(), $line->id, 3);

    expect($cart->fresh()->cart_version)->toBe(3);
});

it('increments on remove line', function (): void {
    $cart = $this->service->create($this->store);
    $variant = ProductVariant::factory()
        ->for(Product::factory()->for($this->store))
        ->create(['price_amount' => 1000]);
    $line = $this->service->addLine($cart, $variant->id, 1);

    $this->service->removeLine($cart->fresh(), $line->id);

    expect($cart->fresh()->cart_version)->toBe(3);
});
