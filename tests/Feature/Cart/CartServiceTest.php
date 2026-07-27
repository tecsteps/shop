<?php

use App\Enums\CartStatus;
use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\CartService;
use Illuminate\Validation\ValidationException;

/**
 * Create a store with an active product/variant pair.
 *
 * @param  array<string, mixed>  $variantAttributes
 * @return array{0: Store, 1: ProductVariant}
 */
function cartServiceSetup(array $variantAttributes = [], int $onHand = 10): array
{
    $store = test()->createStore();
    test()->bindStore($store);

    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->withInventory($onHand)->create(array_merge([
        'product_id' => $product->id,
        'price_amount' => 2500,
    ], $variantAttributes));

    return [$store, $variant];
}

test('creates a cart for the current store', function () {
    [$store] = cartServiceSetup();

    $cart = app(CartService::class)->create($store);

    expect($cart->store_id)->toBe($store->id)
        ->and($cart->currency)->toBe($store->default_currency)
        ->and($cart->cart_version)->toBe(1)
        ->and($cart->status)->toBe(CartStatus::Active);
});

test('adds a line item to the cart', function () {
    [$store, $variant] = cartServiceSetup();
    $cart = app(CartService::class)->create($store);

    $line = app(CartService::class)->addLine($cart, $variant->id, 2);

    expect($line->unit_price_amount)->toBe(2500)
        ->and($line->quantity)->toBe(2)
        ->and($line->line_subtotal_amount)->toBe(5000)
        ->and($line->line_total_amount)->toBe(5000);
});

test('increments quantity when adding an existing variant', function () {
    [$store, $variant] = cartServiceSetup();
    $service = app(CartService::class);
    $cart = $service->create($store);

    $service->addLine($cart, $variant->id, 1);
    $service->addLine($cart->refresh(), $variant->id, 2);

    $cart->refresh();

    expect($cart->lines)->toHaveCount(1)
        ->and($cart->lines->first()->quantity)->toBe(3)
        ->and($cart->lines->first()->line_subtotal_amount)->toBe(7500);
});

test('rejects add when product is not active', function () {
    [$store, $variant] = cartServiceSetup();
    $variant->product->update(['status' => \App\Enums\ProductStatus::Draft]);

    $cart = app(CartService::class)->create($store);

    app(CartService::class)->addLine($cart, $variant->id, 1);
})->throws(ValidationException::class);

test('rejects add when inventory is insufficient and policy is deny', function () {
    [$store, $variant] = cartServiceSetup([], onHand: 2);
    $cart = app(CartService::class)->create($store);

    app(CartService::class)->addLine($cart, $variant->id, 5);
})->throws(InsufficientInventoryException::class);

test('allows add when inventory is insufficient but policy is continue', function () {
    [$store, $variant] = cartServiceSetup([], onHand: 2);
    $variant->inventoryItem->update(['policy' => InventoryPolicy::Continue]);
    $cart = app(CartService::class)->create($store);

    $line = app(CartService::class)->addLine($cart, $variant->id, 5);

    expect($line->quantity)->toBe(5);
});

test('updates line quantity', function () {
    [$store, $variant] = cartServiceSetup();
    $service = app(CartService::class);
    $cart = $service->create($store);
    $line = $service->addLine($cart, $variant->id, 2);

    $service->updateLineQuantity($cart->refresh(), $line->id, 5);

    $line->refresh();

    expect($line->quantity)->toBe(5)
        ->and($line->line_subtotal_amount)->toBe(12500)
        ->and($line->line_total_amount)->toBe(12500);
});

test('removes a line when quantity set to zero', function () {
    [$store, $variant] = cartServiceSetup();
    $service = app(CartService::class);
    $cart = $service->create($store);
    $line = $service->addLine($cart, $variant->id, 2);

    $service->updateLineQuantity($cart->refresh(), $line->id, 0);

    expect($cart->refresh()->lines)->toHaveCount(0);
});

test('removes a specific line item', function () {
    [$store, $variant] = cartServiceSetup();
    $second = ProductVariant::factory()->withInventory(10)->create([
        'product_id' => $variant->product_id,
        'price_amount' => 1000,
    ]);

    $service = app(CartService::class);
    $cart = $service->create($store);
    $first = $service->addLine($cart, $variant->id, 1);
    $service->addLine($cart->refresh(), $second->id, 1);

    $service->removeLine($cart->refresh(), $first->id);

    $cart->refresh();

    expect($cart->lines)->toHaveCount(1)
        ->and($cart->lines->first()->variant_id)->toBe($second->id);
});

test('increments cart version on every mutation', function () {
    [$store, $variant] = cartServiceSetup();
    $service = app(CartService::class);
    $cart = $service->create($store);

    $line = $service->addLine($cart, $variant->id, 1);
    $service->updateLineQuantity($cart->refresh(), $line->id, 2);
    $service->removeLine($cart->refresh(), $line->id);

    expect($cart->refresh()->cart_version)->toBe(4);
});

test('returns cart via session for guest users', function () {
    [$store] = cartServiceSetup();
    $cart = app(CartService::class)->create($store);

    session(['cart_id' => $cart->id]);

    $found = app(CartService::class)->getOrCreateForSession($store);

    expect($found->id)->toBe($cart->id);
});

test('merges guest cart into customer cart on login', function () {
    [$store, $variantA] = cartServiceSetup();
    $variantB = ProductVariant::factory()->withInventory(10)->create([
        'product_id' => $variantA->product_id,
        'price_amount' => 1000,
    ]);

    $service = app(CartService::class);

    $guestCart = $service->create($store);
    $service->addLine($guestCart, $variantA->id, 2);

    $customerCart = $service->create($store);
    $service->addLine($customerCart, $variantA->id, 1);
    $service->addLine($customerCart->refresh(), $variantB->id, 3);

    session(['cart_id' => $guestCart->id]);

    $merged = $service->mergeOnLogin($guestCart->refresh(), $customerCart->refresh());

    expect($merged->lines)->toHaveCount(2)
        ->and($merged->findLineByVariant($variantA->id)->quantity)->toBe(3)
        ->and($merged->findLineByVariant($variantB->id)->quantity)->toBe(3)
        ->and($guestCart->refresh()->status)->toBe(CartStatus::Abandoned)
        ->and(session('cart_id'))->toBeNull();
});

test('cleanup job abandons carts inactive for 14 days', function () {
    [$store] = cartServiceSetup();
    $service = app(CartService::class);

    $stale = $service->create($store);
    $recent = $service->create($store);

    Cart::query()->whereKey($stale->id)->update(['updated_at' => now()->subDays(15)]);
    Cart::query()->whereKey($recent->id)->update(['updated_at' => now()->subDays(2)]);

    (new \App\Jobs\CleanupAbandonedCarts)->handle(app(\App\Services\CheckoutService::class));

    expect($stale->refresh()->status)->toBe(CartStatus::Abandoned)
        ->and($recent->refresh()->status)->toBe(CartStatus::Active);
});
