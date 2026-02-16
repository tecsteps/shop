<?php

use App\Enums\CartStatus;
use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->service = app(CartService::class);
});

it('creates a cart for the current store', function () {
    $cart = $this->service->create($this->ctx['store']);

    expect($cart->store_id)->toBe($this->ctx['store']->id)
        ->and($cart->status)->toBe(CartStatus::Active);
});

it('adds a line item to the cart', function () {
    $cart = $this->service->create($this->ctx['store']);
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);
    InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 100,
    ]);

    $line = $this->service->addLine($cart, $variant->id, 2);

    expect($line->quantity)->toBe(2)
        ->and($line->unit_price)->toBe(2500)
        ->and($line->subtotal)->toBe(5000);
});

it('increments quantity when adding an existing variant', function () {
    $cart = $this->service->create($this->ctx['store']);
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);
    InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 100,
    ]);

    $this->service->addLine($cart, $variant->id, 1);
    $line = $this->service->addLine($cart, $variant->id, 2);

    expect($line->quantity)->toBe(3)
        ->and($line->subtotal)->toBe(7500);
});

it('rejects add when product is not active', function () {
    $cart = $this->service->create($this->ctx['store']);
    $product = Product::factory()->for($this->ctx['store'])->create(['status' => ProductStatus::Draft]);
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);

    $this->service->addLine($cart, $variant->id, 1);
})->throws(\InvalidArgumentException::class);

it('rejects add when inventory is insufficient and policy is deny', function () {
    $cart = $this->service->create($this->ctx['store']);
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);
    InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 2,
        'policy' => InventoryPolicy::Deny,
    ]);

    $this->service->addLine($cart, $variant->id, 5);
})->throws(InsufficientInventoryException::class);

it('allows add when inventory is insufficient but policy is continue', function () {
    $cart = $this->service->create($this->ctx['store']);
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);
    InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 2,
        'policy' => InventoryPolicy::Continue,
    ]);

    $line = $this->service->addLine($cart, $variant->id, 5);
    expect($line->quantity)->toBe(5);
});

it('updates line quantity', function () {
    $cart = $this->service->create($this->ctx['store']);
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);
    InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 100,
    ]);

    $line = $this->service->addLine($cart, $variant->id, 2);
    $updated = $this->service->updateLineQuantity($cart, $line->id, 5);

    expect($updated->quantity)->toBe(5)
        ->and($updated->subtotal)->toBe(12500);
});

it('removes a line when quantity set to zero', function () {
    $cart = $this->service->create($this->ctx['store']);
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);
    InventoryItem::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 100,
    ]);

    $line = $this->service->addLine($cart, $variant->id, 2);
    $this->service->updateLineQuantity($cart, $line->id, 0);

    expect($cart->lines()->count())->toBe(0);
});

it('removes a specific line item', function () {
    $cart = $this->service->create($this->ctx['store']);
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $v1 = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);
    $v2 = ProductVariant::factory()->for($product)->create(['price_amount' => 3500, 'is_default' => false]);
    InventoryItem::factory()->create(['store_id' => $this->ctx['store']->id, 'variant_id' => $v1->id, 'quantity_on_hand' => 100]);
    InventoryItem::factory()->create(['store_id' => $this->ctx['store']->id, 'variant_id' => $v2->id, 'quantity_on_hand' => 100]);

    $line1 = $this->service->addLine($cart, $v1->id, 1);
    $this->service->addLine($cart, $v2->id, 1);
    $this->service->removeLine($cart, $line1->id);

    expect($cart->lines()->count())->toBe(1);
});

it('increments cart version on every mutation', function () {
    $cart = $this->service->create($this->ctx['store']);
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);
    InventoryItem::factory()->create(['store_id' => $this->ctx['store']->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 100]);

    // v1 -> addLine -> v2
    $line = $this->service->addLine($cart, $variant->id, 1);
    // v2 -> updateQty -> v3
    $this->service->updateLineQuantity($cart, $line->id, 3);
    // v3 -> removeLine -> v4
    $this->service->removeLine($cart, $line->id);

    $cart->refresh();
    expect($cart->cart_version)->toBe(4);
});

it('merges guest cart into customer cart on login', function () {
    $customer = \App\Models\Customer::factory()->for($this->ctx['store'])->create();
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $vA = ProductVariant::factory()->for($product)->create(['price_amount' => 2500]);
    $vB = ProductVariant::factory()->for($product)->create(['price_amount' => 3500, 'is_default' => false]);
    InventoryItem::factory()->create(['store_id' => $this->ctx['store']->id, 'variant_id' => $vA->id, 'quantity_on_hand' => 100]);
    InventoryItem::factory()->create(['store_id' => $this->ctx['store']->id, 'variant_id' => $vB->id, 'quantity_on_hand' => 100]);

    $guestCart = $this->service->create($this->ctx['store']);
    $this->service->addLine($guestCart, $vA->id, 2);

    $customerCart = $this->service->create($this->ctx['store'], $customer);
    $this->service->addLine($customerCart, $vA->id, 1);
    $this->service->addLine($customerCart, $vB->id, 3);

    $merged = $this->service->mergeOnLogin($guestCart, $customerCart);

    $lines = $merged->lines()->get();
    $lineA = $lines->firstWhere('variant_id', $vA->id);
    $lineB = $lines->firstWhere('variant_id', $vB->id);

    expect($lineA->quantity)->toBe(3)
        ->and($lineB->quantity)->toBe(3)
        ->and($guestCart->fresh()->status)->toBe(CartStatus::Abandoned);
});
