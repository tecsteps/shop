<?php

use App\Enums\CartStatus;
use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = app(CartService::class);
});

it('creates a cart for the current store', function () {
    $cart = $this->service->create($this->store);

    $this->assertDatabaseHas('carts', [
        'id' => $cart->getKey(),
        'store_id' => $this->store->getKey(),
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => 'active',
    ]);
});

it('adds a line item to the cart', function () {
    $variant = createPurchasableVariant($this->store, 2500);
    $cart = $this->service->create($this->store);

    $line = $this->service->addLine($cart, $variant->getKey(), 2);

    expect($line->unit_price_amount)->toBe(2500);
    expect($line->line_subtotal_amount)->toBe(5000);
    expect($line->line_total_amount)->toBe(5000);
    $this->assertDatabaseHas('cart_lines', [
        'cart_id' => $cart->getKey(),
        'variant_id' => $variant->getKey(),
        'quantity' => 2,
    ]);
});

it('increments quantity when adding an existing variant', function () {
    $variant = createPurchasableVariant($this->store, 2500);
    $cart = $this->service->create($this->store);

    $this->service->addLine($cart, $variant->getKey(), 1);
    $line = $this->service->addLine($cart, $variant->getKey(), 2);

    expect($cart->lines()->count())->toBe(1);
    expect($line->quantity)->toBe(3);
    expect($line->line_subtotal_amount)->toBe(7500);
});

it('rejects add when product is not active', function () {
    $product = Product::factory()->for($this->store)->create();
    $variant = ProductVariant::factory()->asDefault()->priced(2500)->for($product)->create();
    $cart = $this->service->create($this->store);

    $this->service->addLine($cart, $variant->getKey(), 1);
})->throws(ValidationException::class);

it('rejects add when inventory is insufficient and policy is deny', function () {
    $variant = createPurchasableVariant($this->store, 2500, 2);
    $cart = $this->service->create($this->store);

    $this->service->addLine($cart, $variant->getKey(), 5);
})->throws(InsufficientInventoryException::class);

it('allows add when inventory is insufficient but policy is continue', function () {
    $variant = createPurchasableVariant($this->store, 2500, 2, policy: InventoryPolicy::Continue);
    $cart = $this->service->create($this->store);

    $line = $this->service->addLine($cart, $variant->getKey(), 5);

    expect($line->quantity)->toBe(5);
});

it('updates line quantity', function () {
    $variant = createPurchasableVariant($this->store, 2500);
    $cart = $this->service->create($this->store);
    $line = $this->service->addLine($cart, $variant->getKey(), 2);

    $updated = $this->service->updateLineQuantity($cart, $line->getKey(), 5);

    expect($updated->quantity)->toBe(5);
    expect($updated->line_subtotal_amount)->toBe(12500);
    expect($updated->line_total_amount)->toBe(12500);
});

it('removes a line when quantity set to zero', function () {
    $variant = createPurchasableVariant($this->store, 2500);
    $cart = $this->service->create($this->store);
    $line = $this->service->addLine($cart, $variant->getKey(), 2);

    $result = $this->service->updateLineQuantity($cart, $line->getKey(), 0);

    expect($result)->toBeNull();
    $this->assertDatabaseMissing('cart_lines', ['id' => $line->getKey()]);
});

it('removes a specific line item', function () {
    $variantA = createPurchasableVariant($this->store, 2500);
    $variantB = createPurchasableVariant($this->store, 3500);
    $cart = $this->service->create($this->store);
    $lineA = $this->service->addLine($cart, $variantA->getKey(), 1);
    $this->service->addLine($cart, $variantB->getKey(), 1);

    $this->service->removeLine($cart, $lineA->getKey());

    expect($cart->lines()->count())->toBe(1);
    expect($cart->lines()->first()->variant_id)->toBe($variantB->getKey());
});

it('increments cart version on every mutation', function () {
    $variant = createPurchasableVariant($this->store, 2500);

    $cart = $this->service->create($this->store);
    expect($cart->cart_version)->toBe(1);

    $line = $this->service->addLine($cart, $variant->getKey(), 1);
    expect($cart->refresh()->cart_version)->toBe(2);

    $this->service->updateLineQuantity($cart, $line->getKey(), 3);
    expect($cart->refresh()->cart_version)->toBe(3);

    $this->service->removeLine($cart, $line->getKey());
    expect($cart->refresh()->cart_version)->toBe(4);
});

it('returns cart via session for guest users', function () {
    $cart = $this->service->getOrCreateForSession($this->store);

    expect(session(CartService::SESSION_KEY))->toBe($cart->getKey());

    $resolved = $this->service->getOrCreateForSession($this->store);

    expect($resolved->getKey())->toBe($cart->getKey());
});

it('merges guest cart into customer cart on login', function () {
    $variantA = createPurchasableVariant($this->store, 2500);
    $variantB = createPurchasableVariant($this->store, 3500);

    $guestCart = Cart::factory()->for($this->store)->create();
    CartLine::factory()->for($guestCart)->priced(2500, 2)->create(['variant_id' => $variantA->getKey()]);

    $customerCart = Cart::factory()->forCustomer()->for($this->store)->create();
    CartLine::factory()->for($customerCart)->priced(2500, 1)->create(['variant_id' => $variantA->getKey()]);
    CartLine::factory()->for($customerCart)->priced(3500, 3)->create(['variant_id' => $variantB->getKey()]);

    $merged = $this->service->mergeOnLogin($guestCart, $customerCart);

    expect($merged->lines()->count())->toBe(2);
    expect($merged->lines()->where('variant_id', $variantA->getKey())->first()->quantity)->toBe(3);
    expect($merged->lines()->where('variant_id', $variantB->getKey())->first()->quantity)->toBe(3);
    expect($guestCart->refresh()->status)->toBe(CartStatus::Abandoned);
});
