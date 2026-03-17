<?php

use App\Enums\CartStatus;
use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->cartService = app(CartService::class);
});

function createActiveVariant($store, int $price = 2500): ProductVariant
{
    $product = Product::factory()->active()->create(['store_id' => $store->id]);

    return ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => $price,
    ]);
}

it('creates a cart for the current store', function () {
    $cart = $this->cartService->create($this->store);

    expect($cart)->toBeInstanceOf(Cart::class)
        ->and($cart->store_id)->toBe($this->store->id)
        ->and($cart->currency)->toBe($this->store->default_currency)
        ->and($cart->cart_version)->toBe(1)
        ->and($cart->status)->toBe(CartStatus::Active);
});

it('adds a line item to the cart', function () {
    $variant = createActiveVariant($this->store, 2500);
    $cart = $this->cartService->create($this->store);

    $line = $this->cartService->addLine($cart, $variant->id, 2);

    expect($line)->toBeInstanceOf(CartLine::class)
        ->and($line->unit_price_amount)->toBe(2500)
        ->and($line->quantity)->toBe(2)
        ->and($line->line_subtotal_amount)->toBe(5000)
        ->and($line->line_total_amount)->toBe(5000);
});

it('increments quantity when adding an existing variant', function () {
    $variant = createActiveVariant($this->store, 2500);
    $cart = $this->cartService->create($this->store);

    $this->cartService->addLine($cart, $variant->id, 1);
    $line = $this->cartService->addLine($cart, $variant->id, 2);

    expect($line->quantity)->toBe(3)
        ->and($line->line_subtotal_amount)->toBe(7500)
        ->and($cart->fresh()->lines()->count())->toBe(1);
});

it('rejects add when product is not active', function () {
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => ProductStatus::Draft,
    ]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $cart = $this->cartService->create($this->store);

    $this->cartService->addLine($cart, $variant->id, 1);
})->throws(InvalidArgumentException::class, 'Product is not active.');

it('rejects add when inventory is insufficient and policy is deny', function () {
    $variant = createActiveVariant($this->store, 2500);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 2,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    $cart = $this->cartService->create($this->store);

    $this->cartService->addLine($cart, $variant->id, 5);
})->throws(InvalidArgumentException::class, 'Insufficient inventory');

it('allows add when inventory is insufficient but policy is continue', function () {
    $variant = createActiveVariant($this->store, 2500);
    InventoryItem::factory()->allowOversell()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 2,
        'quantity_reserved' => 0,
    ]);

    $cart = $this->cartService->create($this->store);
    $line = $this->cartService->addLine($cart, $variant->id, 5);

    expect($line->quantity)->toBe(5);
});

it('updates line quantity', function () {
    $variant = createActiveVariant($this->store, 2500);
    $cart = $this->cartService->create($this->store);
    $line = $this->cartService->addLine($cart, $variant->id, 2);

    $updated = $this->cartService->updateLineQuantity($cart->fresh(), $line->id, 5);

    expect($updated->quantity)->toBe(5)
        ->and($updated->line_subtotal_amount)->toBe(12500);
});

it('removes a line when quantity set to zero', function () {
    $variant = createActiveVariant($this->store, 2500);
    $cart = $this->cartService->create($this->store);
    $line = $this->cartService->addLine($cart, $variant->id, 2);

    $this->cartService->updateLineQuantity($cart->fresh(), $line->id, 0);

    expect($cart->fresh()->lines()->count())->toBe(0);
});

it('removes a specific line item', function () {
    $variant1 = createActiveVariant($this->store, 2500);
    $variant2 = createActiveVariant($this->store, 3500);
    $cart = $this->cartService->create($this->store);

    $line1 = $this->cartService->addLine($cart, $variant1->id, 1);
    $this->cartService->addLine($cart, $variant2->id, 1);

    $this->cartService->removeLine($cart->fresh(), $line1->id);

    expect($cart->fresh()->lines()->count())->toBe(1);
});

it('increments cart version on every mutation', function () {
    $variant = createActiveVariant($this->store, 2500);
    $cart = $this->cartService->create($this->store);

    expect($cart->cart_version)->toBe(1);

    $line = $this->cartService->addLine($cart, $variant->id, 1);
    expect($cart->fresh()->cart_version)->toBe(2);

    $this->cartService->updateLineQuantity($cart->fresh(), $line->id, 3);
    expect($cart->fresh()->cart_version)->toBe(3);

    $this->cartService->removeLine($cart->fresh(), $line->id);
    expect($cart->fresh()->cart_version)->toBe(4);
});

it('returns cart via session for guest users', function () {
    $cart = $this->cartService->create($this->store);
    session(['cart_id' => $cart->id]);

    $retrieved = $this->cartService->getOrCreateForSession($this->store);

    expect($retrieved->id)->toBe($cart->id);
});

it('merges guest cart into customer cart on login', function () {
    $variant1 = createActiveVariant($this->store, 2500);
    $variant2 = createActiveVariant($this->store, 3500);

    $customer = Customer::factory()->create(['store_id' => $this->store->id]);

    // Guest cart with variant A qty 2
    $guestCart = $this->cartService->create($this->store);
    $this->cartService->addLine($guestCart, $variant1->id, 2);

    // Customer cart with variant A qty 1 and variant B qty 3
    $customerCart = $this->cartService->create($this->store, $customer);
    $this->cartService->addLine($customerCart, $variant1->id, 1);
    $this->cartService->addLine($customerCart, $variant2->id, 3);

    $merged = $this->cartService->mergeOnLogin($guestCart->fresh('lines'), $customerCart->fresh('lines'));

    // Variant A: max(1, 2) = 2, Variant B: 3
    $mergedLines = $merged->lines;
    $lineA = $mergedLines->firstWhere('variant_id', $variant1->id);
    $lineB = $mergedLines->firstWhere('variant_id', $variant2->id);

    expect($lineA->quantity)->toBe(2)
        ->and($lineB->quantity)->toBe(3)
        ->and($guestCart->fresh()->status)->toBe(CartStatus::Abandoned);
});
