<?php

use App\Enums\CartStatus;
use App\Exceptions\CartVersionMismatchException;
use App\Exceptions\InsufficientInventoryException;
use App\Services\CartService;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('creates a cart for the current store', function () {
    ['store' => $store] = $this->createStoreContext();

    $cart = app(CartService::class)->create($store);

    expect($cart->store_id)->toBe($store->id);
    expect($cart->currency)->toBe($store->default_currency);
    expect($cart->cart_version)->toBe(1);
    expect($cart->status)->toBe(CartStatus::Active);
});

it('adds a line item to the cart', function () {
    ['store' => $store] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store, ['price_amount' => 2500]);

    $cart = app(CartService::class)->create($store);
    $line = app(CartService::class)->addLine($cart, $variant->id, 2);

    expect($line->unit_price_amount)->toBe(2500);
    expect($line->line_subtotal_amount)->toBe(5000);
    expect($line->line_total_amount)->toBe(5000);
});

it('increments quantity when adding an existing variant', function () {
    ['store' => $store] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store, ['price_amount' => 2500]);

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, 1);
    $line = app(CartService::class)->addLine($cart->refresh(), $variant->id, 2);

    expect($line->quantity)->toBe(3);
    expect($line->line_subtotal_amount)->toBe(7500);
    expect($cart->refresh()->cartLines()->count())->toBe(1);
});

it('rejects add when product is not active', function () {
    ['store' => $store] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store);
    $variant->product->update(['status' => App\Enums\ProductStatus::Draft]);

    $cart = app(CartService::class)->create($store);

    app(CartService::class)->addLine($cart, $variant->id, 1);
})->throws(Illuminate\Validation\ValidationException::class);

it('rejects add when inventory is insufficient and policy is deny', function () {
    ['store' => $store] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store, [], ['quantity_on_hand' => 2, 'policy' => 'deny']);

    $cart = app(CartService::class)->create($store);

    app(CartService::class)->addLine($cart, $variant->id, 5);
})->throws(InsufficientInventoryException::class);

it('allows add when inventory is insufficient but policy is continue', function () {
    ['store' => $store] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store, [], ['quantity_on_hand' => 2, 'policy' => 'continue']);

    $cart = app(CartService::class)->create($store);
    $line = app(CartService::class)->addLine($cart, $variant->id, 5);

    expect($line->quantity)->toBe(5);
});

it('updates line quantity', function () {
    ['store' => $store] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store, ['price_amount' => 2500]);

    $cart = app(CartService::class)->create($store);
    $line = app(CartService::class)->addLine($cart, $variant->id, 2);
    $updated = app(CartService::class)->updateLineQuantity($cart->refresh(), $line->id, 5);

    expect($updated->quantity)->toBe(5);
    expect($updated->line_subtotal_amount)->toBe(12500);
});

it('removes a line when quantity set to zero', function () {
    ['store' => $store] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store);

    $cart = app(CartService::class)->create($store);
    $line = app(CartService::class)->addLine($cart, $variant->id, 2);

    expect(app(CartService::class)->updateLineQuantity($cart->refresh(), $line->id, 0))->toBeNull();
    expect($cart->refresh()->cartLines()->count())->toBe(0);
});

it('removes a specific line item', function () {
    ['store' => $store] = $this->createStoreContext();
    $variantA = CommerceTestHelper::purchasableVariant($store);
    $variantB = CommerceTestHelper::purchasableVariant($store);

    $cart = app(CartService::class)->create($store);
    $lineA = app(CartService::class)->addLine($cart, $variantA->id, 1);
    app(CartService::class)->addLine($cart->refresh(), $variantB->id, 1);
    app(CartService::class)->removeLine($cart->refresh(), $lineA->id);

    expect($cart->refresh()->cartLines()->count())->toBe(1);
});

it('increments cart version on every mutation', function () {
    ['store' => $store] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store);

    $cart = app(CartService::class)->create($store);
    expect($cart->cart_version)->toBe(1);

    $line = app(CartService::class)->addLine($cart, $variant->id, 1);
    expect($cart->refresh()->cart_version)->toBe(2);

    app(CartService::class)->updateLineQuantity($cart->refresh(), $line->id, 2);
    expect($cart->refresh()->cart_version)->toBe(3);

    app(CartService::class)->removeLine($cart->refresh(), $line->id);
    expect($cart->refresh()->cart_version)->toBe(4);
});

it('returns cart via session for guest users', function () {
    ['store' => $store] = $this->createStoreContext();

    $cart = app(CartService::class)->getOrCreateForSession($store);

    expect(session('cart_id'))->toBe($cart->id);
    expect(app(CartService::class)->getOrCreateForSession($store)->id)->toBe($cart->id);
});

it('merges guest cart into customer cart on login', function () {
    ['store' => $store] = $this->createStoreContext();
    $variantA = CommerceTestHelper::purchasableVariant($store);
    $variantB = CommerceTestHelper::purchasableVariant($store);
    $customer = App\Models\Customer::factory()->create(['store_id' => $store->id]);

    $guestCart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($guestCart, $variantA->id, 2);

    $customerCart = app(CartService::class)->create($store, $customer);
    app(CartService::class)->addLine($customerCart, $variantA->id, 1);
    app(CartService::class)->addLine($customerCart->refresh(), $variantB->id, 3);

    $merged = app(CartService::class)->mergeOnLogin($guestCart->refresh(), $customerCart->refresh());

    $lineA = $merged->cartLines()->where('variant_id', $variantA->id)->first();
    $lineB = $merged->cartLines()->where('variant_id', $variantB->id)->first();

    expect($lineA->quantity)->toBe(2);
    expect($lineB->quantity)->toBe(3);
    expect($guestCart->refresh()->status)->toBe(CartStatus::Abandoned);
});

it('abandons stale carts with the cleanup job', function () {
    ['store' => $store] = $this->createStoreContext();

    $stale = app(CartService::class)->create($store);
    $stale->updated_at = now()->subDays(15);
    $stale->save();

    $fresh = app(CartService::class)->create($store);

    app(App\Jobs\CleanupAbandonedCarts::class)->handle();

    expect($stale->refresh()->status)->toBe(CartStatus::Abandoned);
    expect($fresh->refresh()->status)->toBe(CartStatus::Active);
});

it('rejects mutations with a stale expected version', function () {
    ['store' => $store] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store);

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, 1);

    app(CartService::class)->addLine($cart->refresh(), $variant->id, 1, 1);
})->throws(CartVersionMismatchException::class);
