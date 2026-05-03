<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Exceptions\UnserviceableShippingAddressException;
use App\Jobs\CleanupAbandonedCarts;
use App\Jobs\ExpireAbandonedCheckouts;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function checkoutStore(): Store
{
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    TaxSettings::withoutGlobalScopes()->create([
        'store_id' => $store->getKey(),
        'mode' => 'manual',
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => ['default_rate_bps' => 0, 'shipping_taxable' => false],
    ]);

    return $store;
}

function checkoutVariant(Store $store, bool $requiresShipping = true): ProductVariant
{
    $product = Product::factory()
        ->withDefaultVariant(2500)
        ->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::withoutGlobalScopes()
        ->where('product_id', $product->getKey())
        ->firstOrFail();

    $variant->forceFill([
        'requires_shipping' => $requiresShipping,
        'weight_g' => $requiresShipping ? 500 : 0,
    ])->save();
    InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $variant->getKey())
        ->update([
            'quantity_on_hand' => 10,
            'quantity_reserved' => 0,
        ]);

    return $variant->refresh();
}

function checkoutAddress(string $country = 'DE'): array
{
    return [
        'email' => 'buyer@example.test',
        'shipping_address' => [
            'first_name' => 'Test',
            'last_name' => 'Buyer',
            'address1' => 'Main Street 1',
            'city' => 'Berlin',
            'country' => $country,
            'postal_code' => '10115',
        ],
    ];
}

test('checkout service transitions through address shipping payment and expiry', function () {
    $store = checkoutStore();
    $variant = checkoutVariant($store);
    $zone = ShippingZone::withoutGlobalScopes()->create([
        'store_id' => $store->getKey(),
        'name' => 'Germany',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);
    $rate = ShippingRate::withoutGlobalScopes()->create([
        'zone_id' => $zone->getKey(),
        'name' => 'Standard',
        'type' => 'flat',
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->getKey(), 2);
    $checkout = app(CheckoutService::class)->createFromCart($cart);

    $checkout = app(CheckoutService::class)->setAddress($checkout, checkoutAddress());
    expect($checkout->status)->toBe(CheckoutStatus::Addressed)
        ->and($checkout->email)->toBe('buyer@example.test');

    $checkout = app(CheckoutService::class)->setShippingMethod($checkout, $rate->getKey());
    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected)
        ->and($checkout->shipping_method_id)->toBe($rate->getKey());

    $checkout = app(CheckoutService::class)->selectPaymentMethod($checkout, 'credit_card');
    expect($checkout->status)->toBe(CheckoutStatus::PaymentSelected)
        ->and($checkout->expires_at)->not->toBeNull()
        ->and(InventoryItem::withoutGlobalScopes()->where('variant_id', $variant->getKey())->first()?->quantity_reserved)->toBe(2);

    app(CheckoutService::class)->selectPaymentMethod($checkout, 'credit_card');
    expect(InventoryItem::withoutGlobalScopes()->where('variant_id', $variant->getKey())->first()?->quantity_reserved)->toBe(2);

    $expired = app(CheckoutService::class)->expireCheckout($checkout);
    expect($expired->status)->toBe(CheckoutStatus::Expired)
        ->and(InventoryItem::withoutGlobalScopes()->where('variant_id', $variant->getKey())->first()?->quantity_reserved)->toBe(0);
});

test('checkout service rejects unserviceable shipping addresses for physical carts', function () {
    $store = checkoutStore();
    $variant = checkoutVariant($store);
    ShippingZone::withoutGlobalScopes()->create([
        'store_id' => $store->getKey(),
        'name' => 'Germany',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->getKey(), 1);
    $checkout = app(CheckoutService::class)->createFromCart($cart);
    $checkout = app(CheckoutService::class)->setAddress($checkout, checkoutAddress('FR'));

    expect(fn () => app(CheckoutService::class)->setShippingMethod($checkout, null))
        ->toThrow(UnserviceableShippingAddressException::class);
});

test('checkout service skips shipping for digital-only carts', function () {
    $store = checkoutStore();
    $variant = checkoutVariant($store, requiresShipping: false);
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->getKey(), 1);
    $checkout = app(CheckoutService::class)->createFromCart($cart);
    $checkout = app(CheckoutService::class)->setAddress($checkout, checkoutAddress());
    $checkout = app(CheckoutService::class)->setShippingMethod($checkout, null);

    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected)
        ->and($checkout->shipping_method_id)->toBeNull()
        ->and($checkout->totals_json['shipping'])->toBe(0);
});

test('expire abandoned checkouts job expires stale checkouts and releases reservations', function () {
    $store = checkoutStore();
    $variant = checkoutVariant($store);
    $zone = ShippingZone::withoutGlobalScopes()->create([
        'store_id' => $store->getKey(),
        'name' => 'Germany',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);
    $rate = ShippingRate::withoutGlobalScopes()->create([
        'zone_id' => $zone->getKey(),
        'name' => 'Standard',
        'type' => 'flat',
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->getKey(), 2);
    $checkout = app(CheckoutService::class)->createFromCart($cart);
    $checkout = app(CheckoutService::class)->setAddress($checkout, checkoutAddress());
    $checkout = app(CheckoutService::class)->setShippingMethod($checkout, $rate->getKey());
    $checkout = app(CheckoutService::class)->selectPaymentMethod($checkout, 'credit_card');
    $checkout->forceFill(['expires_at' => now()->subMinute()])->save();

    (new ExpireAbandonedCheckouts)->handle(app(CheckoutService::class));

    expect($checkout->refresh()->status)->toBe(CheckoutStatus::Expired)
        ->and(InventoryItem::withoutGlobalScopes()->where('variant_id', $variant->getKey())->first()?->quantity_reserved)->toBe(0);
});

test('cleanup abandoned carts job abandons old carts and expires related checkouts', function () {
    $store = checkoutStore();
    $variant = checkoutVariant($store);
    $zone = ShippingZone::withoutGlobalScopes()->create([
        'store_id' => $store->getKey(),
        'name' => 'Germany',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);
    $rate = ShippingRate::withoutGlobalScopes()->create([
        'zone_id' => $zone->getKey(),
        'name' => 'Standard',
        'type' => 'flat',
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->getKey(), 1);
    $checkout = app(CheckoutService::class)->createFromCart($cart);
    $checkout = app(CheckoutService::class)->setAddress($checkout, checkoutAddress());
    $checkout = app(CheckoutService::class)->setShippingMethod($checkout, $rate->getKey());
    $checkout = app(CheckoutService::class)->selectPaymentMethod($checkout, 'paypal');
    $cart->forceFill(['updated_at' => now()->subDays(15)])->save();

    (new CleanupAbandonedCarts)->handle(app(CheckoutService::class));

    expect($cart->refresh()->status)->toBe(CartStatus::Abandoned)
        ->and($checkout->refresh()->status)->toBe(CheckoutStatus::Expired)
        ->and(InventoryItem::withoutGlobalScopes()->where('variant_id', $variant->getKey())->first()?->quantity_reserved)->toBe(0);
});
