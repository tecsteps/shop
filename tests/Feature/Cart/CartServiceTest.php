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

function createCartTestContext(array $overrides = []): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'title' => 'Cart Test Product',
        'handle' => 'cart-test-'.rand(1000, 9999),
        'status' => $overrides['product_status'] ?? ProductStatus::Active,
        'published_at' => now(),
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'price_amount' => $overrides['price'] ?? 2500,
        'currency' => 'EUR',
        'is_default' => true,
        'position' => 0,
        'status' => $overrides['variant_status'] ?? VariantStatus::Active,
    ]);

    InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => $overrides['on_hand'] ?? 50,
        'quantity_reserved' => 0,
        'policy' => $overrides['policy'] ?? InventoryPolicy::Deny,
    ]);

    return array_merge($ctx, compact('product', 'variant'));
}

it('creates a cart for the current store', function () {
    $ctx = createCartTestContext();
    $cartService = app(CartService::class);

    $cart = $cartService->create($ctx['store']);

    expect($cart->store_id)->toBe($ctx['store']->id)
        ->and($cart->currency)->toBe($ctx['store']->default_currency)
        ->and($cart->cart_version)->toBe(1)
        ->and($cart->status)->toBe(CartStatus::Active);
});

it('adds a line item to the cart', function () {
    $ctx = createCartTestContext();
    $cartService = app(CartService::class);
    $cart = $cartService->create($ctx['store']);

    $line = $cartService->addLine($cart, $ctx['variant']->id, 2);

    expect($line->unit_price_amount)->toBe(2500)
        ->and($line->quantity)->toBe(2)
        ->and($line->line_subtotal_amount)->toBe(5000)
        ->and($line->line_total_amount)->toBe(5000);
});

it('increments quantity when adding an existing variant', function () {
    $ctx = createCartTestContext();
    $cartService = app(CartService::class);
    $cart = $cartService->create($ctx['store']);

    $cartService->addLine($cart, $ctx['variant']->id, 1);
    $cartService->addLine($cart, $ctx['variant']->id, 2);

    $cart->refresh();
    expect($cart->lines)->toHaveCount(1)
        ->and($cart->lines->first()->quantity)->toBe(3)
        ->and($cart->lines->first()->line_subtotal_amount)->toBe(7500);
});

it('rejects add when product is not active', function () {
    $ctx = createCartTestContext(['product_status' => ProductStatus::Draft]);
    $cartService = app(CartService::class);
    $cart = $cartService->create($ctx['store']);

    expect(fn () => $cartService->addLine($cart, $ctx['variant']->id, 1))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects add when inventory is insufficient and policy is deny', function () {
    $ctx = createCartTestContext(['on_hand' => 2, 'policy' => InventoryPolicy::Deny]);
    $cartService = app(CartService::class);
    $cart = $cartService->create($ctx['store']);

    expect(fn () => $cartService->addLine($cart, $ctx['variant']->id, 5))
        ->toThrow(InsufficientInventoryException::class);
});

it('allows add when inventory is insufficient but policy is continue', function () {
    $ctx = createCartTestContext(['on_hand' => 2, 'policy' => InventoryPolicy::Continue]);
    $cartService = app(CartService::class);
    $cart = $cartService->create($ctx['store']);

    $line = $cartService->addLine($cart, $ctx['variant']->id, 5);

    expect($line->quantity)->toBe(5);
});

it('updates line quantity', function () {
    $ctx = createCartTestContext();
    $cartService = app(CartService::class);
    $cart = $cartService->create($ctx['store']);
    $line = $cartService->addLine($cart, $ctx['variant']->id, 2);

    $updated = $cartService->updateLineQuantity($cart, $line->id, 5);

    expect($updated->quantity)->toBe(5)
        ->and($updated->line_subtotal_amount)->toBe(12500);
});

it('removes a line when quantity set to zero', function () {
    $ctx = createCartTestContext();
    $cartService = app(CartService::class);
    $cart = $cartService->create($ctx['store']);
    $line = $cartService->addLine($cart, $ctx['variant']->id, 2);

    $cartService->updateLineQuantity($cart, $line->id, 0);

    expect($cart->fresh()->lines)->toHaveCount(0);
});

it('removes a specific line item', function () {
    $ctx = createCartTestContext();
    $store = $ctx['store'];

    // Create second variant
    $product2 = Product::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'title' => 'Second Product',
        'handle' => 'second-product-'.rand(1000, 9999),
        'status' => ProductStatus::Active,
        'published_at' => now(),
    ]);
    $variant2 = ProductVariant::create([
        'product_id' => $product2->id,
        'price_amount' => 3000,
        'currency' => 'EUR',
        'is_default' => true,
        'position' => 0,
        'status' => VariantStatus::Active,
    ]);
    InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'variant_id' => $variant2->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    $cartService = app(CartService::class);
    $cart = $cartService->create($store);
    $line1 = $cartService->addLine($cart, $ctx['variant']->id, 1);
    $cartService->addLine($cart, $variant2->id, 1);

    $cartService->removeLine($cart, $line1->id);

    expect($cart->fresh()->lines)->toHaveCount(1);
});

it('increments cart version on every mutation', function () {
    $ctx = createCartTestContext();
    $cartService = app(CartService::class);
    $cart = $cartService->create($ctx['store']);

    expect($cart->cart_version)->toBe(1);

    $line = $cartService->addLine($cart, $ctx['variant']->id, 1);
    expect($cart->fresh()->cart_version)->toBe(2);

    $cartService->updateLineQuantity($cart, $line->id, 3);
    expect($cart->fresh()->cart_version)->toBe(3);

    $cartService->removeLine($cart, $line->id);
    expect($cart->fresh()->cart_version)->toBe(4);
});

it('returns cart via session for guest users', function () {
    $ctx = createCartTestContext();
    $cartService = app(CartService::class);

    $cart = $cartService->getOrCreateForSession($ctx['store']);
    $sameCart = $cartService->getOrCreateForSession($ctx['store']);

    expect($sameCart->id)->toBe($cart->id);
});

it('merges guest cart into customer cart on login', function () {
    $ctx = createCartTestContext();
    $store = $ctx['store'];

    // Second variant
    $product2 = Product::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'title' => 'Merge Product',
        'handle' => 'merge-product-'.rand(1000, 9999),
        'status' => ProductStatus::Active,
        'published_at' => now(),
    ]);
    $variant2 = ProductVariant::create([
        'product_id' => $product2->id,
        'price_amount' => 3000,
        'currency' => 'EUR',
        'is_default' => true,
        'position' => 0,
        'status' => VariantStatus::Active,
    ]);
    InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'variant_id' => $variant2->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    $cartService = app(CartService::class);

    // Guest cart: variant A qty 2
    $guestCart = $cartService->create($store);
    $cartService->addLine($guestCart, $ctx['variant']->id, 2);

    // Customer cart: variant A qty 1, variant B qty 3
    $customer = Customer::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'email' => 'merge@test.com',
        'name' => 'Test',
    ]);
    $customerCart = $cartService->create($store, $customer);
    $cartService->addLine($customerCart, $ctx['variant']->id, 1);
    $cartService->addLine($customerCart, $variant2->id, 3);

    $merged = $cartService->mergeOnLogin($guestCart, $customerCart);

    $lines = $merged->lines->sortBy('variant_id')->values();

    // Variant A: max(1, 2) = 2; Variant B: 3
    expect($lines)->toHaveCount(2);

    $lineA = $lines->firstWhere('variant_id', $ctx['variant']->id);
    $lineB = $lines->firstWhere('variant_id', $variant2->id);

    expect($lineA->quantity)->toBe(2)
        ->and($lineB->quantity)->toBe(3)
        ->and($guestCart->fresh()->status)->toBe(CartStatus::Abandoned);
});
