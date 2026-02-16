<?php

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Livewire\Storefront\Products\Show;
use App\Services\ProductService;
use Livewire\Livewire;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->productService = app(ProductService::class);
});

it('shows in stock for products with available inventory', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'Stocked Product',
        'price_amount' => 2500,
    ]);
    $product->update(['status' => ProductStatus::Active]);

    $variant = $product->variants->first();
    $variant->inventoryItem->update([
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    Livewire::test(Show::class, ['handle' => $product->handle])
        ->assertSee('In stock')
        ->assertDontSee('Sold out')
        ->assertDontSee('backorder');
});

it('shows sold out for deny policy products with zero stock', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'Sold Out Product',
        'price_amount' => 2500,
    ]);
    $product->update(['status' => ProductStatus::Active]);

    $variant = $product->variants->first();
    $variant->inventoryItem->update([
        'quantity_on_hand' => 0,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    Livewire::test(Show::class, ['handle' => $product->handle])
        ->assertSee('Sold out')
        ->assertDontSee('In stock');
});

it('shows backorder for continue policy products with zero stock', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'Backorder Product',
        'price_amount' => 2500,
    ]);
    $product->update(['status' => ProductStatus::Active]);

    $variant = $product->variants->first();
    $variant->inventoryItem->update([
        'quantity_on_hand' => 0,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Continue,
    ]);

    Livewire::test(Show::class, ['handle' => $product->handle])
        ->assertSee('Available on backorder')
        ->assertDontSee('Sold out')
        ->assertDontSee('In stock');
});
