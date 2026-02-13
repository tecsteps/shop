<?php

use App\Enums\CartStatus;
use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\CartVersionMismatchException;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->service = app(CartService::class);
});

function createActiveProductWithVariant(Store $store, int $price = 2500, int $stock = 10, InventoryPolicy $policy = InventoryPolicy::Deny): ProductVariant
{
    $product = Product::factory()->active()->create(['store_id' => $store->id]);

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
        'policy' => $policy,
    ]);

    return $variant;
}

test('creates a cart for the current store', function () {
    $cart = $this->service->create($this->store);

    expect($cart)->toBeInstanceOf(Cart::class);
    expect($cart->store_id)->toBe($this->store->id);
    expect($cart->currency)->toBe('USD');
    expect($cart->cart_version)->toBe(1);
    expect($cart->status)->toBe(CartStatus::Active);
    expect($cart->customer_id)->toBeNull();
});

test('creates a cart linked to customer', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    $cart = $this->service->create($this->store, $customer);

    expect($cart->customer_id)->toBe($customer->id);
});

test('adds a line item to the cart', function () {
    $variant = createActiveProductWithVariant($this->store, price: 2500);
    $cart = $this->service->create($this->store);

    $line = $this->service->addLine($cart, $variant->id, 2);

    expect($line->variant_id)->toBe($variant->id);
    expect($line->quantity)->toBe(2);
    expect($line->unit_price_amount)->toBe(2500);
    expect($line->line_subtotal_amount)->toBe(5000);
    expect($line->line_total_amount)->toBe(5000);
});

test('increments quantity when adding an existing variant', function () {
    $variant = createActiveProductWithVariant($this->store, price: 2500);
    $cart = $this->service->create($this->store);

    $this->service->addLine($cart, $variant->id, 1);
    $line = $this->service->addLine($cart, $variant->id, 2);

    expect($line->quantity)->toBe(3);
    expect($line->line_subtotal_amount)->toBe(7500);
});

test('rejects add when product is not active', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => ProductStatus::Draft]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'status' => VariantStatus::Active,
    ]);

    $cart = $this->service->create($this->store);

    expect(fn () => $this->service->addLine($cart, $variant->id, 1))
        ->toThrow(InvalidArgumentException::class, 'Product is not active');
});

test('rejects add when variant is not active', function () {
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->archived()->create([
        'product_id' => $product->id,
    ]);

    $cart = $this->service->create($this->store);

    expect(fn () => $this->service->addLine($cart, $variant->id, 1))
        ->toThrow(InvalidArgumentException::class, 'Variant is not active');
});

test('rejects add when inventory is insufficient and policy is deny', function () {
    $variant = createActiveProductWithVariant($this->store, stock: 2, policy: InventoryPolicy::Deny);
    $cart = $this->service->create($this->store);

    expect(fn () => $this->service->addLine($cart, $variant->id, 5))
        ->toThrow(InsufficientInventoryException::class);
});

test('allows add when inventory is insufficient but policy is continue', function () {
    $variant = createActiveProductWithVariant($this->store, stock: 2, policy: InventoryPolicy::Continue);
    $cart = $this->service->create($this->store);

    $line = $this->service->addLine($cart, $variant->id, 5);

    expect($line->quantity)->toBe(5);
});

test('updates line quantity', function () {
    $variant = createActiveProductWithVariant($this->store, price: 2500);
    $cart = $this->service->create($this->store);
    $line = $this->service->addLine($cart, $variant->id, 2);

    $updated = $this->service->updateLineQuantity($cart, $line->id, 5);

    expect($updated->quantity)->toBe(5);
    expect($updated->line_subtotal_amount)->toBe(12500);
    expect($updated->line_total_amount)->toBe(12500);
});

test('removes a line when quantity set to zero', function () {
    $variant = createActiveProductWithVariant($this->store, price: 2500);
    $cart = $this->service->create($this->store);
    $line = $this->service->addLine($cart, $variant->id, 2);

    $this->service->updateLineQuantity($cart, $line->id, 0);

    expect($cart->lines()->count())->toBe(0);
});

test('removes a specific line item', function () {
    $variant1 = createActiveProductWithVariant($this->store, price: 2500);
    $variant2 = createActiveProductWithVariant($this->store, price: 1500);
    $cart = $this->service->create($this->store);

    $line1 = $this->service->addLine($cart, $variant1->id, 1);
    $this->service->addLine($cart, $variant2->id, 1);

    $this->service->removeLine($cart, $line1->id);

    expect($cart->lines()->count())->toBe(1);
});

test('increments cart version on every mutation', function () {
    $variant = createActiveProductWithVariant($this->store, price: 2500);
    $cart = $this->service->create($this->store);

    expect($cart->cart_version)->toBe(1);

    $line = $this->service->addLine($cart, $variant->id, 1);
    $cart->refresh();
    expect($cart->cart_version)->toBe(2);

    $this->service->updateLineQuantity($cart, $line->id, 3);
    $cart->refresh();
    expect($cart->cart_version)->toBe(3);

    $this->service->removeLine($cart, $line->id);
    $cart->refresh();
    expect($cart->cart_version)->toBe(4);
});

test('detects version mismatch', function () {
    $variant = createActiveProductWithVariant($this->store, price: 2500);
    $cart = $this->service->create($this->store);

    // Cart is at version 1, but we send expected_version 5
    expect(fn () => $this->service->addLine($cart, $variant->id, 1, expectedVersion: 5))
        ->toThrow(CartVersionMismatchException::class);
});

test('merges guest cart into customer cart on login', function () {
    $variant1 = createActiveProductWithVariant($this->store, price: 2500);
    $variant2 = createActiveProductWithVariant($this->store, price: 1500);

    $customer = Customer::factory()->create(['store_id' => $this->store->id]);

    // Guest cart: variant1 qty 2
    $guestCart = $this->service->create($this->store);
    $this->service->addLine($guestCart, $variant1->id, 2);

    // Customer cart: variant1 qty 1, variant2 qty 3
    $customerCart = $this->service->create($this->store, $customer);
    $this->service->addLine($customerCart, $variant1->id, 1);
    $this->service->addLine($customerCart, $variant2->id, 3);

    $mergedCart = $this->service->mergeOnLogin($guestCart, $customerCart);

    $mergedCart->load('lines');

    // variant1 should have max(2, 1) = 2
    $line1 = $mergedCart->lines->firstWhere('variant_id', $variant1->id);
    expect($line1->quantity)->toBe(2);

    // variant2 should remain 3
    $line2 = $mergedCart->lines->firstWhere('variant_id', $variant2->id);
    expect($line2->quantity)->toBe(3);

    // Guest cart should be abandoned
    $guestCart->refresh();
    expect($guestCart->status)->toBe(CartStatus::Abandoned);
});

test('returns cart via session for guest users', function () {
    $cart = $this->service->create($this->store);
    session(['cart_id' => $cart->id]);

    $retrieved = $this->service->getOrCreateForSession($this->store);

    expect($retrieved->id)->toBe($cart->id);
});
