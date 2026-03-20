<?php

use App\Enums\CheckoutStatus;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\CheckoutService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->checkoutService = app(CheckoutService::class);

    TaxSettings::factory()->create(['store_id' => $this->store->id, 'rate' => 1900]);

    $this->product = Product::factory()->create(['store_id' => $this->store->id, 'status' => 'active']);
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'price_amount' => 2500,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $this->variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    $this->cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $this->cart->id,
        'variant_id' => $this->variant->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 5000,
        'line_total_amount' => 5000,
    ]);
});

it('creates checkout from cart', function () {
    $checkout = $this->checkoutService->createFromCart($this->store, $this->cart);

    expect($checkout->status)->toBe(CheckoutStatus::Started)
        ->and($checkout->store_id)->toBe($this->store->id)
        ->and($checkout->cart_id)->toBe($this->cart->id);
});

it('rejects checkout from empty cart', function () {
    $emptyCart = Cart::factory()->create(['store_id' => $this->store->id]);

    expect(fn () => $this->checkoutService->createFromCart($this->store, $emptyCart))
        ->toThrow(\App\Exceptions\InvalidCartException::class);
});

it('transitions from started to addressed', function () {
    $checkout = $this->checkoutService->createFromCart($this->store, $this->cart);

    $checkout = $this->checkoutService->setAddress($checkout, [
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

    expect($checkout->status)->toBe(CheckoutStatus::Addressed)
        ->and($checkout->email)->toBe('test@example.com')
        ->and($checkout->shipping_address_json)->toBeArray()
        ->and($checkout->billing_address_json)->toBeArray()
        ->and($checkout->totals_json)->not->toBeNull();
});

it('transitions from addressed to shipping_selected', function () {
    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'config_json' => ['amount' => 499],
    ]);

    $checkout = $this->checkoutService->createFromCart($this->store, $this->cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
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

    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);

    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected)
        ->and($checkout->shipping_method_id)->toBe($rate->id);
});

it('transitions from shipping_selected to payment_selected and reserves inventory', function () {
    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id]);

    $checkout = $this->checkoutService->createFromCart($this->store, $this->cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
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
    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);
    $checkout = $this->checkoutService->selectPaymentMethod($checkout, 'credit_card');

    expect($checkout->status)->toBe(CheckoutStatus::PaymentSelected)
        ->and($checkout->expires_at)->not->toBeNull();

    $this->variant->inventoryItem->refresh();
    expect($this->variant->inventoryItem->quantity_reserved)->toBe(2);
});

it('rejects invalid payment method', function () {
    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id]);

    $checkout = $this->checkoutService->createFromCart($this->store, $this->cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
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
    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);

    expect(fn () => $this->checkoutService->selectPaymentMethod($checkout, 'bitcoin'))
        ->toThrow(InvalidCheckoutTransitionException::class);
});

it('rejects invalid state transitions', function () {
    $checkout = $this->checkoutService->createFromCart($this->store, $this->cart);

    // Cannot go from started to completed
    expect(fn () => $this->checkoutService->completeCheckout($checkout))
        ->toThrow(InvalidCheckoutTransitionException::class);
});

it('rejects skipping shipping step', function () {
    $checkout = $this->checkoutService->createFromCart($this->store, $this->cart);

    // Cannot go from started to shipping
    expect(fn () => $this->checkoutService->setShippingMethod($checkout, 1))
        ->toThrow(InvalidCheckoutTransitionException::class);
});

it('expires checkout and releases inventory', function () {
    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id]);

    $checkout = $this->checkoutService->createFromCart($this->store, $this->cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
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
    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);
    $checkout = $this->checkoutService->selectPaymentMethod($checkout, 'credit_card');

    $this->variant->inventoryItem->refresh();
    expect($this->variant->inventoryItem->quantity_reserved)->toBe(2);

    $checkout = $this->checkoutService->expireCheckout($checkout);

    expect($checkout->status)->toBe(CheckoutStatus::Expired);

    $this->variant->inventoryItem->refresh();
    expect($this->variant->inventoryItem->quantity_reserved)->toBe(0);
});

it('does not expire completed checkouts', function () {
    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => 'completed',
    ]);

    $result = $this->checkoutService->expireCheckout($checkout);
    expect($result->status)->toBe(CheckoutStatus::Completed);
});
