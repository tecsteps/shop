<?php

use App\Enums\CartStatus;
use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Models\CartLine;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->cartService = new CartService;
});

it('creates a cart for the current store', function () {
    $cart = $this->cartService->create($this->store);

    expect($cart->store_id)->toBe($this->store->id)
        ->and($cart->currency)->toBe('EUR')
        ->and($cart->cart_version)->toBe(1)
        ->and($cart->status)->toBe(CartStatus::Active);
});

it('adds a line item to the cart', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $line = $this->cartService->addLine($cart, $variant->id, 2);

    expect($line->unit_price_amount)->toBe(2500)
        ->and($line->quantity)->toBe(2)
        ->and($line->line_subtotal_amount)->toBe(5000)
        ->and($line->line_total_amount)->toBe(5000);
});

it('increments quantity when adding an existing variant', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $this->cartService->addLine($cart, $variant->id, 1);
    $line = $this->cartService->addLine($cart, $variant->id, 2);

    expect($line->quantity)->toBe(3)
        ->and($line->line_subtotal_amount)->toBe(7500);
});

it('rejects add when product is not active', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => ProductStatus::Draft]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $this->cartService->addLine($cart, $variant->id, 1);
})->throws(InvalidArgumentException::class, 'Product is not active');

it('rejects add when inventory is insufficient and policy is deny', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 2, 'policy' => InventoryPolicy::Deny]);

    $this->cartService->addLine($cart, $variant->id, 5);
})->throws(InsufficientInventoryException::class);

it('allows add when inventory is insufficient but policy is continue', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    InventoryItem::factory()->continuePolicy()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 2]);

    $line = $this->cartService->addLine($cart, $variant->id, 5);

    expect($line->quantity)->toBe(5);
});

it('updates line quantity', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $line = $this->cartService->addLine($cart, $variant->id, 2);
    $updatedLine = $this->cartService->updateLineQuantity($cart, $line->id, 5);

    expect($updatedLine->quantity)->toBe(5)
        ->and($updatedLine->line_subtotal_amount)->toBe(12500);
});

it('removes a line when quantity set to zero', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    $line = $this->cartService->addLine($cart, $variant->id, 2);
    $this->cartService->updateLineQuantity($cart, $line->id, 0);

    expect(CartLine::where('cart_id', $cart->id)->count())->toBe(0);
});

it('removes a specific line item', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant1 = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    $variant2 = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 3000]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant1->id, 'quantity_on_hand' => 10]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant2->id, 'quantity_on_hand' => 10]);

    $line1 = $this->cartService->addLine($cart, $variant1->id, 1);
    $this->cartService->addLine($cart, $variant2->id, 1);

    $this->cartService->removeLine($cart, $line1->id);

    expect(CartLine::where('cart_id', $cart->id)->count())->toBe(1);
});

it('increments cart version on every mutation', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variant->id, 'quantity_on_hand' => 10]);

    expect($cart->cart_version)->toBe(1);

    $line = $this->cartService->addLine($cart, $variant->id, 1);
    expect($cart->fresh()->cart_version)->toBe(2);

    $this->cartService->updateLineQuantity($cart, $line->id, 3);
    expect($cart->fresh()->cart_version)->toBe(3);

    $this->cartService->removeLine($cart, $line->id);
    expect($cart->fresh()->cart_version)->toBe(4);
});

it('returns cart via session for guest users', function () {
    $cart = $this->cartService->getOrCreateForSession($this->store, null, 'session-123');

    $found = $this->cartService->getOrCreateForSession($this->store, null, 'session-123');

    expect($found->id)->toBe($cart->id);
});

it('merges guest cart into customer cart on login', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variantA = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    $variantB = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 3000]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variantA->id, 'quantity_on_hand' => 10]);
    InventoryItem::factory()->create(['store_id' => $this->store->id, 'variant_id' => $variantB->id, 'quantity_on_hand' => 10]);

    $guestCart = $this->cartService->create($this->store);
    $this->cartService->addLine($guestCart, $variantA->id, 2);

    $customerCart = $this->cartService->create($this->store, $customer);
    $this->cartService->addLine($customerCart, $variantA->id, 1);
    $this->cartService->addLine($customerCart, $variantB->id, 3);

    $merged = $this->cartService->mergeOnLogin($guestCart, $customerCart);

    $lines = CartLine::where('cart_id', $merged->id)->get();
    $lineA = $lines->firstWhere('variant_id', $variantA->id);
    $lineB = $lines->firstWhere('variant_id', $variantB->id);

    expect($lineA->quantity)->toBe(3)
        ->and($lineB->quantity)->toBe(3)
        ->and($guestCart->fresh()->status)->toBe(CartStatus::Abandoned);
});
