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

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->cartService = app(CartService::class);
});

it('creates a cart for a store', function () {
    $cart = $this->cartService->create($this->store);

    expect($cart)->toBeInstanceOf(Cart::class)
        ->and($cart->store_id)->toBe($this->store->id)
        ->and($cart->customer_id)->toBeNull()
        ->and($cart->status)->toBe(CartStatus::Active)
        ->and($cart->cart_version)->toBe(1);
});

it('creates a cart for a customer', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);

    $cart = $this->cartService->create($this->store, $customer);

    expect($cart->customer_id)->toBe($customer->id);
});

it('adds a line to the cart', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
        'status' => VariantStatus::Active,
    ]);

    $line = $this->cartService->addLine($cart, $variant->id, 2);

    expect($line->variant_id)->toBe($variant->id)
        ->and($line->quantity)->toBe(2)
        ->and($line->unit_price_amount)->toBe(2500)
        ->and($line->line_subtotal_amount)->toBe(5000)
        ->and($line->line_total_amount)->toBe(5000)
        ->and($cart->fresh()->cart_version)->toBe(2);
});

it('increments quantity when adding an existing variant', function () {
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

    $this->cartService->addLine($cart, $variant->id, 1);
    $line = $this->cartService->addLine($cart, $variant->id, 2);

    expect($line->quantity)->toBe(3)
        ->and($line->line_subtotal_amount)->toBe(3000)
        ->and($cart->fresh()->cart_version)->toBe(3);
});

it('rejects adding a variant from a different store', function () {
    $cart = $this->cartService->create($this->store);
    $otherProduct = Product::factory()->create([
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $otherProduct->id,
        'status' => VariantStatus::Active,
    ]);

    $this->cartService->addLine($cart, $variant->id, 1);
})->throws(\InvalidArgumentException::class, 'Variant does not belong to this store.');

it('rejects adding a variant with inactive product', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => ProductStatus::Draft,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'status' => VariantStatus::Active,
    ]);

    $this->cartService->addLine($cart, $variant->id, 1);
})->throws(\InvalidArgumentException::class, 'Product is not active.');

it('rejects adding when inventory is insufficient with deny policy', function () {
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
        'policy' => InventoryPolicy::Deny,
    ]);

    $this->cartService->addLine($cart, $variant->id, 5);
})->throws(InsufficientInventoryException::class);

it('updates line quantity', function () {
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

    $line = $this->cartService->addLine($cart, $variant->id, 1);
    $updated = $this->cartService->updateLineQuantity($cart, $line->id, 3);

    expect($updated->quantity)->toBe(3)
        ->and($updated->line_subtotal_amount)->toBe(3000)
        ->and($cart->fresh()->cart_version)->toBe(3);
});

it('removes a line from the cart', function () {
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

    $line = $this->cartService->addLine($cart, $variant->id, 1);
    $this->cartService->removeLine($cart, $line->id);

    expect($cart->lines()->count())->toBe(0)
        ->and($cart->fresh()->cart_version)->toBe(3);
});

it('merges guest cart into customer cart on login', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => ProductStatus::Active,
    ]);
    $variant1 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 1000,
        'status' => VariantStatus::Active,
    ]);
    $variant2 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 2000,
        'status' => VariantStatus::Active,
    ]);

    $guestCart = $this->cartService->create($this->store);
    $this->cartService->addLine($guestCart, $variant1->id, 3);
    $this->cartService->addLine($guestCart, $variant2->id, 1);

    $customerCart = $this->cartService->create($this->store, $customer);
    $this->cartService->addLine($customerCart, $variant1->id, 1);

    $merged = $this->cartService->mergeOnLogin($guestCart, $customerCart);

    expect($merged->lines)->toHaveCount(2);

    $v1Line = $merged->lines->firstWhere('variant_id', $variant1->id);
    expect($v1Line->quantity)->toBe(3); // max(1, 3)

    $v2Line = $merged->lines->firstWhere('variant_id', $variant2->id);
    expect($v2Line->quantity)->toBe(1);

    expect($guestCart->fresh()->status)->toBe(CartStatus::Abandoned);
});
