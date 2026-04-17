<?php

use App\Enums\CartStatus;
use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;

function createCartTestContext(array $overrides = []): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::factory()->for($store)->active()->create();
    $variant = ProductVariant::factory()->for($product)->create(array_merge([
        'price_amount' => 2500,
        'status' => VariantStatus::Active,
    ], $overrides['variant'] ?? []));

    $inventoryItem = InventoryItem::factory()->create(array_merge([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ], $overrides['inventory'] ?? []));

    return array_merge($ctx, [
        'product' => $product,
        'variant' => $variant,
        'inventoryItem' => $inventoryItem,
    ]);
}

it('creates a cart for the current store', function () {
    $ctx = createCartTestContext();
    $store = $ctx['store'];

    $service = app(CartService::class);
    $cart = $service->create($store);

    expect($cart)->toBeInstanceOf(Cart::class)
        ->and($cart->store_id)->toBe($store->id)
        ->and($cart->currency)->toBe('USD')
        ->and($cart->cart_version)->toBe(1)
        ->and($cart->status)->toBe(CartStatus::Active);
});

it('adds a line item to the cart', function () {
    $ctx = createCartTestContext();
    $store = $ctx['store'];
    $variant = $ctx['variant'];

    $service = app(CartService::class);
    $cart = $service->create($store);
    $line = $service->addLine($cart, $variant->id, 2);

    expect($line)->toBeInstanceOf(CartLine::class)
        ->and($line->variant_id)->toBe($variant->id)
        ->and($line->quantity)->toBe(2)
        ->and($line->unit_price_amount)->toBe(2500)
        ->and($line->line_subtotal_amount)->toBe(5000);
});

it('increments quantity when adding an existing variant', function () {
    $ctx = createCartTestContext();
    $store = $ctx['store'];
    $variant = $ctx['variant'];

    $service = app(CartService::class);
    $cart = $service->create($store);
    $service->addLine($cart, $variant->id, 2);
    $line = $service->addLine($cart->fresh(), $variant->id, 3);

    expect($line->quantity)->toBe(5)
        ->and($line->line_subtotal_amount)->toBe(12500);
});

it('rejects add when product is not active', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::factory()->for($store)->create([
        'status' => ProductStatus::Draft,
    ]);

    $variant = ProductVariant::factory()->for($product)->create([
        'status' => VariantStatus::Active,
    ]);

    $service = app(CartService::class);
    $cart = $service->create($store);

    expect(fn () => $service->addLine($cart, $variant->id, 1))
        ->toThrow(\InvalidArgumentException::class, 'Product is not active.');
});

it('rejects add when inventory is insufficient and policy is deny', function () {
    $ctx = createCartTestContext(['inventory' => [
        'quantity_on_hand' => 2,
        'policy' => InventoryPolicy::Deny,
    ]]);

    $store = $ctx['store'];
    $variant = $ctx['variant'];

    $service = app(CartService::class);
    $cart = $service->create($store);

    expect(fn () => $service->addLine($cart, $variant->id, 5))
        ->toThrow(InsufficientInventoryException::class);
});

it('allows add when inventory is insufficient but policy is continue', function () {
    $ctx = createCartTestContext(['inventory' => [
        'quantity_on_hand' => 2,
        'policy' => InventoryPolicy::Continue,
    ]]);

    $store = $ctx['store'];
    $variant = $ctx['variant'];

    $service = app(CartService::class);
    $cart = $service->create($store);
    $line = $service->addLine($cart, $variant->id, 5);

    expect($line->quantity)->toBe(5);
});

it('updates line quantity', function () {
    $ctx = createCartTestContext();
    $store = $ctx['store'];
    $variant = $ctx['variant'];

    $service = app(CartService::class);
    $cart = $service->create($store);
    $line = $service->addLine($cart, $variant->id, 2);

    $updated = $service->updateLineQuantity($cart->fresh(), $line->id, 5);

    expect($updated->quantity)->toBe(5)
        ->and($updated->line_subtotal_amount)->toBe(12500);
});

it('removes a line when quantity set to zero', function () {
    $ctx = createCartTestContext();
    $store = $ctx['store'];
    $variant = $ctx['variant'];

    $service = app(CartService::class);
    $cart = $service->create($store);
    $line = $service->addLine($cart, $variant->id, 2);

    $result = $service->updateLineQuantity($cart->fresh(), $line->id, 0);

    expect($result)->toBeNull()
        ->and($cart->fresh()->lines()->count())->toBe(0);
});

it('removes a specific line item', function () {
    $ctx = createCartTestContext();
    $store = $ctx['store'];
    $variant = $ctx['variant'];

    $service = app(CartService::class);
    $cart = $service->create($store);
    $line = $service->addLine($cart, $variant->id, 2);

    $service->removeLine($cart->fresh(), $line->id);

    expect($cart->fresh()->lines()->count())->toBe(0);
});

it('increments cart version on every mutation', function () {
    $ctx = createCartTestContext();
    $store = $ctx['store'];
    $variant = $ctx['variant'];

    $service = app(CartService::class);
    $cart = $service->create($store);

    expect($cart->cart_version)->toBe(1);

    $line = $service->addLine($cart, $variant->id, 1);
    expect($cart->fresh()->cart_version)->toBe(2);

    $service->updateLineQuantity($cart->fresh(), $line->id, 3);
    expect($cart->fresh()->cart_version)->toBe(3);

    $service->removeLine($cart->fresh(), $line->id);
    expect($cart->fresh()->cart_version)->toBe(4);
});

it('returns cart via session for guest users', function () {
    $ctx = createCartTestContext();
    $store = $ctx['store'];

    $service = app(CartService::class);
    $cart1 = $service->getOrCreateForSession($store);
    $cart2 = $service->getOrCreateForSession($store);

    expect($cart1->id)->toBe($cart2->id);
});

it('merges guest cart into customer cart on login', function () {
    $ctx = createCartTestContext();
    $store = $ctx['store'];
    $variant = $ctx['variant'];

    $product = $ctx['product'];
    $variant2 = ProductVariant::factory()->for($product)->create([
        'price_amount' => 1500,
        'status' => VariantStatus::Active,
    ]);

    InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant2->id,
        'quantity_on_hand' => 50,
    ]);

    $service = app(CartService::class);

    $guestCart = $service->create($store);
    $service->addLine($guestCart, $variant->id, 2);
    $service->addLine($guestCart->fresh(), $variant2->id, 1);

    $customerCart = $service->create($store, 1);
    $service->addLine($customerCart, $variant->id, 1);

    $merged = $service->mergeOnLogin($guestCart->fresh(), $customerCart->fresh());

    expect($merged->lines()->count())->toBe(2);

    $mergedVariantLine = $merged->lines()->where('variant_id', $variant->id)->first();
    expect($mergedVariantLine->quantity)->toBe(3);

    $mergedVariant2Line = $merged->lines()->where('variant_id', $variant2->id)->first();
    expect($mergedVariant2Line->quantity)->toBe(1);

    expect($guestCart->fresh()->status)->toBe(CartStatus::Abandoned);
});
