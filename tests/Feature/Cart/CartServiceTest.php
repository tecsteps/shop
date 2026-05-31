<?php

use App\Enums\CartStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = app(CartService::class);
});

/**
 * Create an active variant with stock and the given policy.
 */
function makeVariant(int $price = 2500, int $onHand = 100, string $policy = 'continue', bool $active = true): ProductVariant
{
    $store = app('current_store');
    $product = Product::factory()->create([
        'store_id' => $store->id,
        'status' => $active ? 'active' : 'draft',
    ]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => $price]);
    $variant->inventoryItem->update(['quantity_on_hand' => $onHand, 'policy' => $policy]);

    return $variant;
}

it('creates a cart for the current store', function () {
    $cart = $this->service->create($this->store);

    expect($cart->store_id)->toBe($this->store->id)
        ->and($cart->currency)->toBe($this->store->default_currency)
        ->and($cart->cart_version)->toBe(1)
        ->and($cart->status)->toBe(CartStatus::Active);
});

it('adds a line item to the cart', function () {
    $cart = $this->service->create($this->store);
    $variant = makeVariant(price: 2500);

    $line = $this->service->addLine($cart, $variant->id, 2);

    expect($line->unit_price_amount)->toBe(2500)
        ->and($line->line_subtotal_amount)->toBe(5000)
        ->and($line->line_total_amount)->toBe(5000)
        ->and($line->quantity)->toBe(2);
});

it('increments quantity when adding an existing variant', function () {
    $cart = $this->service->create($this->store);
    $variant = makeVariant(price: 2500);

    $this->service->addLine($cart, $variant->id, 1);
    $this->service->addLine($cart->fresh(), $variant->id, 2);

    expect($cart->lines()->count())->toBe(1)
        ->and($cart->lines()->first()->quantity)->toBe(3)
        ->and($cart->lines()->first()->line_subtotal_amount)->toBe(7500);
});

it('rejects add when product is not active', function () {
    $cart = $this->service->create($this->store);
    $variant = makeVariant(active: false);

    expect(fn () => $this->service->addLine($cart, $variant->id, 1))
        ->toThrow(RuntimeException::class);
});

it('rejects add when inventory is insufficient and policy is deny', function () {
    $cart = $this->service->create($this->store);
    $variant = makeVariant(onHand: 2, policy: 'deny');

    expect(fn () => $this->service->addLine($cart, $variant->id, 5))
        ->toThrow(InsufficientInventoryException::class);
});

it('allows add when inventory is insufficient but policy is continue', function () {
    $cart = $this->service->create($this->store);
    $variant = makeVariant(onHand: 2, policy: 'continue');

    $line = $this->service->addLine($cart, $variant->id, 5);

    expect($line->quantity)->toBe(5);
});

it('updates line quantity', function () {
    $cart = $this->service->create($this->store);
    $variant = makeVariant(price: 2500);
    $line = $this->service->addLine($cart, $variant->id, 2);

    $updated = $this->service->updateLineQuantity($cart->fresh(), $line->id, 5);

    expect($updated->quantity)->toBe(5)
        ->and($updated->line_subtotal_amount)->toBe(12500);
});

it('removes a line when quantity set to zero', function () {
    $cart = $this->service->create($this->store);
    $variant = makeVariant();
    $line = $this->service->addLine($cart, $variant->id, 2);

    $this->service->updateLineQuantity($cart->fresh(), $line->id, 0);

    expect($cart->lines()->count())->toBe(0);
});

it('removes a specific line item', function () {
    $cart = $this->service->create($this->store);
    $variantA = makeVariant();
    $variantB = makeVariant();
    $lineA = $this->service->addLine($cart, $variantA->id, 1);
    $this->service->addLine($cart->fresh(), $variantB->id, 1);

    $this->service->removeLine($cart->fresh(), $lineA->id);

    expect($cart->lines()->count())->toBe(1);
});

it('increments cart version on every mutation', function () {
    $cart = $this->service->create($this->store); // v1
    $variant = makeVariant();
    $line = $this->service->addLine($cart, $variant->id, 1); // v2
    $this->service->updateLineQuantity($cart->fresh(), $line->id, 2); // v3
    $this->service->removeLine($cart->fresh(), $line->id); // v4

    expect($cart->fresh()->cart_version)->toBe(4);
});

it('returns cart via session for guest users', function () {
    $cart = $this->service->getOrCreateForSession($this->store);

    expect(session(CartService::SESSION_KEY))->toBe($cart->id)
        ->and($this->service->getOrCreateForSession($this->store)->id)->toBe($cart->id);
});

it('merges guest cart into customer cart on login', function () {
    $variantA = makeVariant(price: 1000);
    $variantB = makeVariant(price: 2000);
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);

    $guestCart = $this->service->create($this->store);
    $this->service->addLine($guestCart, $variantA->id, 2);

    $customerCart = $this->service->create($this->store, $customer);
    $this->service->addLine($customerCart, $variantA->id, 1);
    $this->service->addLine($customerCart->fresh(), $variantB->id, 3);

    $merged = $this->service->mergeOnLogin($guestCart->fresh('lines'), $customerCart->fresh('lines'));

    $lineA = $merged->lines->firstWhere('variant_id', $variantA->id);
    $lineB = $merged->lines->firstWhere('variant_id', $variantB->id);

    expect($lineA->quantity)->toBe(2)
        ->and($lineB->quantity)->toBe(3)
        ->and($guestCart->fresh()->status)->toBe(CartStatus::Abandoned);
});
