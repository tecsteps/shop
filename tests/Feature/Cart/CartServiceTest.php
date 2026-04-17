<?php

use App\Enums\CartStatus;
use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->cartService = app(CartService::class);
});

it('creates a cart for a store', function () {
    $cart = $this->cartService->create($this->store);

    expect($cart->store_id)->toBe($this->store->id);
    expect($cart->customer_id)->toBeNull();
    expect($cart->status)->toBe(CartStatus::Active);
    expect($cart->cart_version)->toBe(1);
    expect($cart->currency)->toBe($this->store->default_currency);
});

it('adds a line to the cart', function () {
    $cart = $this->cartService->create($this->store);
    $variant = createActiveVariantForCart($this->store, 2500, 10);

    $line = $this->cartService->addLine($cart, $variant->id, 2);

    expect($line->variant_id)->toBe($variant->id);
    expect($line->quantity)->toBe(2);
    expect($line->unit_price_amount)->toBe(2500);
    expect($line->line_subtotal_amount)->toBe(5000);
    expect($line->line_total_amount)->toBe(5000);
});

it('increments quantity for existing variant in cart', function () {
    $cart = $this->cartService->create($this->store);
    $variant = createActiveVariantForCart($this->store, 1000, 10);

    $this->cartService->addLine($cart, $variant->id, 2);
    $line = $this->cartService->addLine($cart, $variant->id, 3);

    expect($line->quantity)->toBe(5);
    expect($line->line_subtotal_amount)->toBe(5000);
    expect($cart->fresh()->lines)->toHaveCount(1);
});

it('rejects inactive product', function () {
    $cart = $this->cartService->create($this->store);

    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => ProductStatus::Draft,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 1000,
        'status' => VariantStatus::Active,
    ]);

    expect(fn () => $this->cartService->addLine($cart, $variant->id, 1))
        ->toThrow(InvalidArgumentException::class, 'Product is not active');
});

it('rejects insufficient inventory with deny policy', function () {
    $cart = $this->cartService->create($this->store);
    $variant = createActiveVariantForCart($this->store, 1000, 2);

    expect(fn () => $this->cartService->addLine($cart, $variant->id, 5))
        ->toThrow(InsufficientInventoryException::class);
});

it('allows overselling with continue policy', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 1000,
        'status' => VariantStatus::Active,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 2,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Continue,
    ]);

    $line = $this->cartService->addLine($cart, $variant->id, 10);

    expect($line->quantity)->toBe(10);
});

it('updates line quantity', function () {
    $cart = $this->cartService->create($this->store);
    $variant = createActiveVariantForCart($this->store, 2000, 20);
    $line = $this->cartService->addLine($cart, $variant->id, 1);

    $updated = $this->cartService->updateLineQuantity($cart, $line->id, 5);

    expect($updated->quantity)->toBe(5);
    expect($updated->line_subtotal_amount)->toBe(10000);
    expect($updated->line_total_amount)->toBe(10000);
});

it('removes line when quantity set to zero', function () {
    $cart = $this->cartService->create($this->store);
    $variant = createActiveVariantForCart($this->store, 2000, 10);
    $line = $this->cartService->addLine($cart, $variant->id, 2);

    $result = $this->cartService->updateLineQuantity($cart, $line->id, 0);

    expect($result)->toBeNull();
    expect($cart->fresh()->lines)->toHaveCount(0);
});

it('removes a specific line', function () {
    $cart = $this->cartService->create($this->store);
    $variant1 = createActiveVariantForCart($this->store, 2000, 10);
    $variant2 = createActiveVariantForCart($this->store, 3000, 10);
    $line1 = $this->cartService->addLine($cart, $variant1->id, 1);
    $this->cartService->addLine($cart, $variant2->id, 1);

    $this->cartService->removeLine($cart, $line1->id);

    $cart->refresh()->load('lines');
    expect($cart->lines)->toHaveCount(1);
    expect($cart->lines->first()->variant_id)->toBe($variant2->id);
});

it('increments version on every mutation', function () {
    $cart = $this->cartService->create($this->store);
    expect($cart->cart_version)->toBe(1);

    $variant = createActiveVariantForCart($this->store, 1000, 20);

    $this->cartService->addLine($cart, $variant->id, 1);
    expect($cart->fresh()->cart_version)->toBe(2);

    $line = $cart->fresh()->lines->first();
    $this->cartService->updateLineQuantity($cart->fresh(), $line->id, 3);
    expect($cart->fresh()->cart_version)->toBe(3);

    $this->cartService->removeLine($cart->fresh(), $line->id);
    expect($cart->fresh()->cart_version)->toBe(4);
});

it('binds cart to session via getOrCreateForSession', function () {
    $cart = $this->cartService->getOrCreateForSession($this->store);

    expect($cart)->toBeInstanceOf(Cart::class);
    expect($cart->store_id)->toBe($this->store->id);
    expect(session('cart_id'))->toBe($cart->id);

    // Second call returns same cart
    $cart2 = $this->cartService->getOrCreateForSession($this->store);
    expect($cart2->id)->toBe($cart->id);
});

it('merges guest cart into customer cart on login', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    $variant1 = createActiveVariantForCart($this->store, 1000, 10);
    $variant2 = createActiveVariantForCart($this->store, 2000, 10);

    $guestCart = $this->cartService->create($this->store);
    $this->cartService->addLine($guestCart, $variant1->id, 3);
    $this->cartService->addLine($guestCart, $variant2->id, 1);

    $customerCart = $this->cartService->create($this->store, $customer);
    $this->cartService->addLine($customerCart, $variant1->id, 1);

    $merged = $this->cartService->mergeOnLogin($guestCart, $customerCart);

    $merged->load('lines');
    expect($merged->lines)->toHaveCount(2);

    $v1Line = $merged->lines->firstWhere('variant_id', $variant1->id);
    expect($v1Line->quantity)->toBe(3); // max(1, 3)

    $v2Line = $merged->lines->firstWhere('variant_id', $variant2->id);
    expect($v2Line->quantity)->toBe(1);

    expect($guestCart->fresh()->status)->toBe(CartStatus::Abandoned);
});

// --- Helper ---

function createActiveVariantForCart($store, int $price, int $stock): ProductVariant
{
    $product = Product::factory()->create([
        'store_id' => $store->id,
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => $price,
        'status' => VariantStatus::Active,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => $stock,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    return $variant;
}
