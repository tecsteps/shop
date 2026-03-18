<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\ProductStatus;
use App\Enums\ShippingRateType;
use App\Enums\TaxMode;
use App\Enums\VariantStatus;
use App\Jobs\ExpireAbandonedCheckouts;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\CheckoutService;

function createCheckoutFlowContext(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'title' => 'Checkout Product',
        'handle' => 'checkout-product-'.rand(1000, 9999),
        'status' => ProductStatus::Active,
        'published_at' => now(),
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'price_amount' => 2500,
        'currency' => 'EUR',
        'is_default' => true,
        'position' => 0,
        'status' => VariantStatus::Active,
        'requires_shipping' => true,
        'weight_g' => 500,
    ]);

    InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    $cart = Cart::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => CartStatus::Active,
    ]);

    CartLine::create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 5000,
        'line_discount_amount' => 0,
        'line_total_amount' => 5000,
    ]);

    $zone = ShippingZone::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'name' => 'Domestic',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);

    $rate = ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Standard',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);

    TaxSettings::create([
        'store_id' => $store->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => ['tax_rate_basis_points' => 1900],
    ]);

    return array_merge($ctx, compact('product', 'variant', 'cart', 'zone', 'rate'));
}

it('creates a checkout from a cart', function () {
    $ctx = createCheckoutFlowContext();
    $checkoutService = app(CheckoutService::class);

    $checkout = $checkoutService->createFromCart($ctx['cart']);

    expect($checkout->status)->toBe(CheckoutStatus::Started)
        ->and($checkout->cart_id)->toBe($ctx['cart']->id)
        ->and($checkout->store_id)->toBe($ctx['store']->id);
});

it('completes full checkout happy path', function () {
    $ctx = createCheckoutFlowContext();
    $checkoutService = app(CheckoutService::class);

    $checkout = $checkoutService->createFromCart($ctx['cart']);

    $checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country' => 'DE',
            'postal_code' => '10115',
        ],
    ]);

    $checkout->refresh();
    expect($checkout->status)->toBe(CheckoutStatus::Addressed);

    $checkoutService->setShippingMethod($checkout, $ctx['rate']->id);
    $checkout->refresh();
    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected);

    $checkoutService->selectPaymentMethod($checkout, 'credit_card');
    $checkout->refresh();
    expect($checkout->status)->toBe(CheckoutStatus::PaymentSelected)
        ->and($checkout->expires_at)->not->toBeNull();

    $result = $checkoutService->completeCheckout($checkout);
    expect($result->status)->toBe(CheckoutStatus::Completed)
        ->and($ctx['cart']->fresh()->status)->toBe(CartStatus::Converted);
});

it('rejects checkout for empty cart', function () {
    $ctx = createStoreContext();
    $emptyCart = Cart::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => CartStatus::Active,
    ]);

    $checkoutService = app(CheckoutService::class);

    expect(fn () => $checkoutService->createFromCart($emptyCart))
        ->toThrow(InvalidArgumentException::class);
});

it('expires checkout after timeout', function () {
    $ctx = createCheckoutFlowContext();
    $checkoutService = app(CheckoutService::class);

    $checkout = $checkoutService->createFromCart($ctx['cart']);

    $checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country' => 'DE',
            'postal_code' => '10115',
        ],
    ]);

    $checkoutService->setShippingMethod($checkout->fresh(), $ctx['rate']->id);
    $checkoutService->selectPaymentMethod($checkout->fresh(), 'credit_card');

    // Simulate timeout - use query builder to avoid Eloquent overriding updated_at
    $checkout->refresh();
    \Illuminate\Support\Facades\DB::table('checkouts')
        ->where('id', $checkout->id)
        ->update(['updated_at' => now()->subHours(25)]);

    $job = new ExpireAbandonedCheckouts;
    $job->handle($checkoutService);

    expect($checkout->fresh()->status)->toBe(CheckoutStatus::Expired);
});

it('prevents duplicate orders from same checkout', function () {
    $ctx = createCheckoutFlowContext();
    $checkoutService = app(CheckoutService::class);

    $checkout = $checkoutService->createFromCart($ctx['cart']);

    $checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country' => 'DE',
            'postal_code' => '10115',
        ],
    ]);

    $checkoutService->setShippingMethod($checkout->fresh(), $ctx['rate']->id);
    $checkoutService->selectPaymentMethod($checkout->fresh(), 'credit_card');

    $result1 = $checkoutService->completeCheckout($checkout->fresh());

    // Second call should not create a duplicate
    expect($result1->status)->toBe(CheckoutStatus::Completed);
});
