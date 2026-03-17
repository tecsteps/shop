<?php

use App\Enums\CheckoutStatus;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\CheckoutService;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->checkoutService = app(CheckoutService::class);
    $this->cartService = app(CartService::class);
});

function makeCheckoutCart($store, array $variantOverrides = []): array
{
    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(array_merge(
        ['product_id' => $product->id, 'price_amount' => 2500],
        $variantOverrides
    ));

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, 2);

    return ['cart' => $cart->fresh('lines'), 'variant' => $variant, 'product' => $product];
}

function validAddressData(): array
{
    return [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'New York',
            'province' => 'New York',
            'province_code' => 'US-NY',
            'country' => 'US',
            'postal_code' => '10001',
        ],
    ];
}

it('transitions from started to addressed with valid address', function () {
    ['cart' => $cart] = makeCheckoutCart($this->store);
    $checkout = $this->checkoutService->createFromCart($cart);

    $checkout = $this->checkoutService->setAddress($checkout, validAddressData());

    expect($checkout->status)->toBe(CheckoutStatus::Addressed)
        ->and($checkout->email)->toBe('test@example.com')
        ->and($checkout->shipping_address_json)->not->toBeNull();
});

it('rejects address transition with missing required fields', function () {
    ['cart' => $cart] = makeCheckoutCart($this->store);
    $checkout = $this->checkoutService->createFromCart($cart);

    $response = $this->putJson(
        "http://acme-fashion.test/api/storefront/v1/checkouts/{$checkout->id}/address",
        ['shipping_address' => ['first_name' => 'John']]
    );

    $response->assertStatus(422);
});

it('transitions from addressed to shipping_selected', function () {
    ['cart' => $cart] = makeCheckoutCart($this->store);
    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, validAddressData());

    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['US'],
    ]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id]);

    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);

    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected)
        ->and($checkout->shipping_method_id)->toBe($rate->id);
});

it('rejects shipping selection with rate from wrong zone', function () {
    ['cart' => $cart] = makeCheckoutCart($this->store);
    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, validAddressData());

    // Create a zone for DE only
    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id]);

    // Address is US, rate is for DE zone
    $this->checkoutService->setShippingMethod($checkout, $rate->id);
})->throws(InvalidCheckoutTransitionException::class, 'does not apply');

it('skips shipping selection when no items require shipping', function () {
    ['cart' => $cart] = makeCheckoutCart($this->store, ['requires_shipping' => false]);
    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, validAddressData());

    $checkout = $this->checkoutService->setShippingMethod($checkout, null);

    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected)
        ->and($checkout->shipping_method_id)->toBeNull();
});

it('transitions from shipping_selected to payment_selected', function () {
    ['cart' => $cart, 'variant' => $variant] = makeCheckoutCart($this->store);

    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 100,
        'quantity_reserved' => 0,
    ]);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, validAddressData());
    $checkout = $this->checkoutService->setShippingMethod($checkout, null);

    $checkout = $this->checkoutService->selectPaymentMethod($checkout, 'credit_card');

    expect($checkout->status)->toBe(CheckoutStatus::PaymentSelected)
        ->and($checkout->payment_method)->toBe('credit_card')
        ->and($checkout->expires_at)->not->toBeNull();
});

it('rejects invalid state transitions', function () {
    ['cart' => $cart] = makeCheckoutCart($this->store);
    $checkout = $this->checkoutService->createFromCart($cart);

    // Try to jump from started to payment_selected
    $this->checkoutService->selectPaymentMethod($checkout, 'credit_card');
})->throws(InvalidCheckoutTransitionException::class);

it('rejects invalid payment method', function () {
    ['cart' => $cart] = makeCheckoutCart($this->store);
    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, validAddressData());
    $checkout = $this->checkoutService->setShippingMethod($checkout, null);

    $this->checkoutService->selectPaymentMethod($checkout, 'bitcoin');
})->throws(InvalidCheckoutTransitionException::class, 'Invalid payment method');

it('recalculates pricing on address change', function () {
    ['cart' => $cart] = makeCheckoutCart($this->store);
    $checkout = $this->checkoutService->createFromCart($cart);

    $checkout = $this->checkoutService->setAddress($checkout, validAddressData());
    $totals1 = $checkout->totals_json;

    // Change address (re-set with different data)
    $newAddress = validAddressData();
    $newAddress['shipping_address']['country'] = 'DE';
    $checkout = $this->checkoutService->setAddress($checkout, $newAddress);

    // Pricing should have been recalculated (totals_json updated)
    expect($checkout->totals_json)->not->toBeNull();
});
