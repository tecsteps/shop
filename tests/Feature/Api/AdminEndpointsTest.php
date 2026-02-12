<?php

use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;

it('returns store-scoped admin api payloads for products and orders', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();

    $productA = Product::factory()->for($storeA)->create([
        'title' => 'Store A Product',
        'handle' => 'store-a-product',
    ]);

    $variantA = ProductVariant::factory()
        ->for($productA)
        ->default()
        ->create();

    InventoryItem::factory()
        ->for($storeA)
        ->for($variantA, 'variant')
        ->state([
            'quantity_on_hand' => 7,
            'quantity_reserved' => 2,
        ])
        ->create();

    $productB = Product::factory()->for($storeB)->create([
        'title' => 'Store B Product',
        'handle' => 'store-b-product',
    ]);

    $orderA = Order::factory()->for($storeA)->create([
        'order_number' => '#3001',
    ]);

    $orderLineA = OrderLine::factory()
        ->for($orderA)
        ->for($productA)
        ->for($variantA, 'variant')
        ->create();

    Payment::factory()
        ->for($orderA)
        ->create([
            'amount' => $orderA->total_amount,
            'currency' => $orderA->currency,
        ]);

    $orderB = Order::factory()->for($storeB)->create([
        'order_number' => '#4001',
    ]);

    $this->getJson("/api/admin/v1/stores/{$storeA->id}/products")
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $productA->id);

    $this->getJson("/api/admin/v1/stores/{$storeA->id}/products/{$productA->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $productA->id);

    $this->getJson("/api/admin/v1/stores/{$storeA->id}/products/{$productB->id}")
        ->assertNotFound();

    $this->getJson("/api/admin/v1/stores/{$storeA->id}/orders")
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $orderA->id);

    $this->getJson("/api/admin/v1/stores/{$storeA->id}/orders/{$orderA->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $orderA->id)
        ->assertJsonPath('data.lines.0.id', $orderLineA->id);

    $this->getJson("/api/admin/v1/stores/{$storeA->id}/orders/{$orderB->id}")
        ->assertNotFound();
});
