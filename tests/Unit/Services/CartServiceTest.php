<?php

use App\Enums\InventoryPolicy;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\InventoryItem;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\CartService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function cartService(): CartService
{
    expect(class_exists(CartService::class))
        ->toBeTrue('App\\Services\\CartService is expected to exist.');

    /** @var CartService $service */
    $service = app(CartService::class);

    foreach (['create', 'addLine', 'updateLineQuantity', 'removeLine'] as $method) {
        expect(method_exists($service, $method))
            ->toBeTrue(sprintf('CartService must expose %s(...).', $method));
    }

    return $service;
}

function cartServiceCreateStore(string $suffix): Store
{
    $organization = Organization::query()->create([
        'name' => 'Org '.$suffix,
        'billing_email' => 'billing+'.$suffix.'@example.test',
    ]);

    return Store::query()->create([
        'organization_id' => $organization->id,
        'name' => 'Store '.$suffix,
        'handle' => 'store-'.$suffix,
        'status' => 'active',
        'default_currency' => 'EUR',
        'default_locale' => 'en',
        'timezone' => 'UTC',
    ]);
}

function cartServiceCreateVariant(
    Store $store,
    string $suffix,
    int $price = 2500,
    int $quantityOnHand = 10,
    InventoryPolicy $policy = InventoryPolicy::Deny,
    string $productStatus = 'active',
    string $variantStatus = 'active',
): ProductVariant {
    $product = Product::query()->create([
        'store_id' => $store->id,
        'title' => 'Cart Product '.$suffix,
        'handle' => 'cart-product-'.$suffix,
        'status' => $productStatus,
        'description_html' => null,
        'vendor' => null,
        'product_type' => null,
        'tags' => [],
        'published_at' => $productStatus === 'active' ? now() : null,
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'SKU-CART-'.$suffix,
        'barcode' => null,
        'price_amount' => $price,
        'compare_at_amount' => null,
        'currency' => 'EUR',
        'weight_g' => 200,
        'requires_shipping' => true,
        'is_default' => true,
        'position' => 1,
        'status' => $variantStatus,
    ]);

    InventoryItem::query()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => $quantityOnHand,
        'quantity_reserved' => 0,
        'policy' => $policy,
    ]);

    return $variant;
}

test('creates a cart with store currency and version one', function (): void {
    $store = cartServiceCreateStore('create');
    $service = cartService();

    $cart = $service->create($store, null);

    expect($cart)->toBeInstanceOf(Cart::class)
        ->and($cart->store_id)->toBe($store->id)
        ->and($cart->currency)->toBe('EUR')
        ->and($cart->cart_version)->toBe(1)
        ->and($cart->status->value)->toBe('active');
});

test('adds and merges lines for the same variant', function (): void {
    $store = cartServiceCreateStore('merge-lines');
    $variant = cartServiceCreateVariant($store, 'merge');
    $service = cartService();
    $cart = $service->create($store, null);

    $service->addLine($cart, $variant->id, 1);
    $service->addLine($cart, $variant->id, 2);
    $cart->refresh();

    /** @var CartLine|null $line */
    $line = $cart->lines()->first();

    expect($cart->lines()->count())->toBe(1)
        ->and($line)->not->toBeNull()
        ->and($line?->quantity)->toBe(3)
        ->and($line?->line_subtotal_amount)->toBe(7500)
        ->and($line?->line_total_amount)->toBe(7500)
        ->and($cart->cart_version)->toBe(3);
});

test('increments version on add update and remove mutations', function (): void {
    $store = cartServiceCreateStore('versioning');
    $variant = cartServiceCreateVariant($store, 'version');
    $service = cartService();
    $cart = $service->create($store, null); // v1

    $line = $service->addLine($cart, $variant->id, 1); // v2
    $cart->refresh();

    $service->updateLineQuantity($cart, $line->id, 4); // v3
    $cart->refresh();

    $service->removeLine($cart, $line->id); // v4
    $cart->refresh();

    expect($cart->cart_version)->toBe(4);
});

test('removes a line when updated quantity is zero', function (): void {
    $store = cartServiceCreateStore('remove-zero');
    $variant = cartServiceCreateVariant($store, 'remove-zero');
    $service = cartService();
    $cart = $service->create($store, null);
    $line = $service->addLine($cart, $variant->id, 2);

    $service->updateLineQuantity($cart, $line->id, 0);

    expect($cart->lines()->whereKey($line->id)->exists())->toBeFalse();
});

test('rejects adding variants from inactive products', function (): void {
    $store = cartServiceCreateStore('inactive-product');
    $variant = cartServiceCreateVariant($store, 'inactive', productStatus: 'draft');
    $service = cartService();
    $cart = $service->create($store, null);

    expect(fn () => $service->addLine($cart, $variant->id, 1))
        ->toThrow(ModelNotFoundException::class);
});

test('rejects add line when inventory is insufficient for deny policy', function (): void {
    $store = cartServiceCreateStore('insufficient');
    $variant = cartServiceCreateVariant(
        store: $store,
        suffix: 'insufficient',
        quantityOnHand: 1,
        policy: InventoryPolicy::Deny,
    );
    $service = cartService();
    $cart = $service->create($store, null);

    expect(fn () => $service->addLine($cart, $variant->id, 2))
        ->toThrow(\App\Exceptions\InsufficientInventoryException::class);
});
