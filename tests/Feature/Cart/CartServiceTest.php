<?php

use App\Models\InventoryItem;
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

function makeVariantWithStock(Store $store, int $price = 1000, int $onHand = 10): ProductVariant
{
    $product = Product::factory()->for($store)->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => $price]);
    InventoryItem::factory()
        ->for($store)
        ->for($variant, 'variant')
        ->create(['quantity_on_hand' => $onHand]);

    return $variant;
}

it('adds a line to a cart', function (): void {
    $cart = $this->service->create($this->store);
    $variant = makeVariantWithStock($this->store, price: 2000);

    $line = $this->service->addLine($cart, $variant->id, 2);

    expect($line->quantity)->toBe(2)
        ->and($line->line_subtotal_amount)->toBe(4000)
        ->and($line->line_total_amount)->toBe(4000);
});

it('increments quantity for existing variant', function (): void {
    $cart = $this->service->create($this->store);
    $variant = makeVariantWithStock($this->store, price: 1000);

    $this->service->addLine($cart, $variant->id, 1);
    $line = $this->service->addLine($cart->fresh(), $variant->id, 2);

    expect($cart->lines()->count())->toBe(1)
        ->and($line->quantity)->toBe(3)
        ->and($line->line_subtotal_amount)->toBe(3000);
});

it('validates inventory before adding', function (): void {
    $cart = $this->service->create($this->store);
    $variant = makeVariantWithStock($this->store, price: 1000, onHand: 2);

    $this->service->addLine($cart, $variant->id, 5);
})->throws(RuntimeException::class, 'Insufficient inventory');

it('removes a line', function (): void {
    $cart = $this->service->create($this->store);
    $variant = makeVariantWithStock($this->store);
    $line = $this->service->addLine($cart, $variant->id, 1);

    $this->service->removeLine($cart->fresh(), $line->id);

    expect($cart->lines()->count())->toBe(0);
});

it('updates quantity', function (): void {
    $cart = $this->service->create($this->store);
    $variant = makeVariantWithStock($this->store, price: 500);
    $line = $this->service->addLine($cart, $variant->id, 1);

    $updated = $this->service->updateLineQuantity($cart->fresh(), $line->id, 4);

    expect($updated->quantity)->toBe(4)
        ->and($updated->line_subtotal_amount)->toBe(2000);
});

it('merges guest cart into customer cart on login', function (): void {
    $guestCart = $this->service->create($this->store);
    $customerCart = $this->service->create($this->store);
    $variantA = makeVariantWithStock($this->store, price: 500);
    $variantB = makeVariantWithStock($this->store, price: 1500);

    $this->service->addLine($guestCart, $variantA->id, 2);
    $this->service->addLine($guestCart->fresh(), $variantB->id, 1);
    $this->service->addLine($customerCart, $variantA->id, 1);

    $merged = $this->service->mergeOnLogin($guestCart->fresh(), $customerCart->fresh());

    $lines = $merged->fresh('lines')->lines;
    $variantALine = $lines->firstWhere('variant_id', $variantA->id);
    $variantBLine = $lines->firstWhere('variant_id', $variantB->id);

    expect($lines)->toHaveCount(2)
        ->and($variantALine->quantity)->toBe(3)
        ->and($variantBLine->quantity)->toBe(1);
});
