<?php

use App\Enums\CartStatus;
use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Models\CartLine;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;

beforeEach(function (): void {
    $ctx = $this->createStoreContext();
    $this->store = $ctx['store'];
    $this->service = app(CartService::class);
});

function makeVariant(int $storeId, int $stock = 100, int $price = 1000): ProductVariant
{
    $product = Product::factory()->create([
        'store_id' => $storeId,
        'status' => ProductStatus::Active,
    ]);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => $price,
    ]);

    InventoryItem::factory()->create([
        'store_id' => $storeId,
        'variant_id' => $variant->id,
        'quantity_on_hand' => $stock,
        'policy' => InventoryPolicy::Deny,
    ]);

    return $variant->fresh(['product', 'inventoryItem']);
}

it('creates a cart with store currency and version 1', function (): void {
    $cart = $this->service->create($this->store);

    expect($cart->currency)->toBe($this->store->default_currency ?? 'USD');
    expect($cart->cart_version)->toBe(1);
    expect($cart->status)->toBe(CartStatus::Active);
});

it('adds a line and increments version', function (): void {
    $variant = makeVariant($this->store->id);
    $cart = $this->service->create($this->store);

    $line = $this->service->addLine($cart, $variant->id, 2);

    expect($line->quantity)->toBe(2);
    expect($line->line_total_amount)->toBe(2000);
    expect($cart->fresh()->cart_version)->toBe(2);
});

it('increments quantity when adding an existing variant', function (): void {
    $variant = makeVariant($this->store->id);
    $cart = $this->service->create($this->store);

    $this->service->addLine($cart, $variant->id, 1);
    $this->service->addLine($cart, $variant->id, 2);

    $lines = CartLine::query()->where('cart_id', $cart->id)->get();
    expect($lines)->toHaveCount(1);
    expect($lines->first()->quantity)->toBe(3);
});

it('rejects adding more than available inventory when policy is deny', function (): void {
    $variant = makeVariant($this->store->id, stock: 2);
    $cart = $this->service->create($this->store);

    $this->service->addLine($cart, $variant->id, 2);

    expect(fn () => $this->service->addLine($cart, $variant->id, 1))
        ->toThrow(InsufficientInventoryException::class);
});

it('updates line quantity and recalculates amounts', function (): void {
    $variant = makeVariant($this->store->id);
    $cart = $this->service->create($this->store);
    $line = $this->service->addLine($cart, $variant->id, 1);

    $updated = $this->service->updateLineQuantity($cart, $line->id, 4);

    expect($updated->quantity)->toBe(4);
    expect($updated->line_total_amount)->toBe(4000);
});

it('removes a line and increments version', function (): void {
    $variant = makeVariant($this->store->id);
    $cart = $this->service->create($this->store);
    $line = $this->service->addLine($cart, $variant->id, 1);

    $this->service->removeLine($cart, $line->id);

    expect(CartLine::query()->where('cart_id', $cart->id)->count())->toBe(0);
    expect($cart->fresh()->cart_version)->toBe(3);
});

it('merges guest cart into customer cart keeping max quantity', function (): void {
    $variant = makeVariant($this->store->id);

    $guest = $this->service->create($this->store);
    $customer = $this->service->create($this->store);

    $this->service->addLine($guest, $variant->id, 2);
    $this->service->addLine($customer, $variant->id, 1);

    $merged = $this->service->mergeOnLogin($guest, $customer);

    $mergedLine = $merged->fresh()->lines->firstWhere('variant_id', $variant->id);
    expect($mergedLine->quantity)->toBe(2);
    expect($guest->fresh()->status)->toBe(CartStatus::Abandoned);
});
