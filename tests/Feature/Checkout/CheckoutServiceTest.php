<?php

use App\Enums\CheckoutStatus;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Models\Checkout;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\CheckoutService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->cartService = app(CartService::class);
    $this->checkoutService = app(CheckoutService::class);
});

it('creates a checkout from a cart', function () {
    $cart = $this->cartService->create($this->store);

    $checkout = $this->checkoutService->createFromCart($cart);

    expect($checkout->store_id)->toBe($this->store->id)
        ->and($checkout->cart_id)->toBe($cart->id)
        ->and($checkout->status)->toBe(CheckoutStatus::Started)
        ->and($checkout->expires_at)->not->toBeNull();
});

it('sets address and transitions to addressed', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 1000,
        'status' => VariantStatus::Active,
    ]);
    $this->cartService->addLine($cart, $variant->id, 1);

    $checkout = $this->checkoutService->createFromCart($cart);

    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'john@example.com',
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'New York',
            'province' => 'NY',
            'country' => 'US',
            'postal_code' => '10001',
        ],
    ]);

    expect($checkout->status)->toBe(CheckoutStatus::Addressed)
        ->and($checkout->email)->toBe('john@example.com')
        ->and($checkout->shipping_address_json['city'])->toBe('New York')
        ->and($checkout->billing_address_json)->not->toBeNull();
});

it('sets shipping method and transitions to shipping_selected', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 1000,
        'status' => VariantStatus::Active,
        'requires_shipping' => true,
    ]);
    $this->cartService->addLine($cart, $variant->id, 1);

    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['US'],
    ]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'config_json' => ['amount' => 500],
    ]);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '456 Oak Ave',
            'city' => 'LA',
            'country' => 'US',
            'postal_code' => '90001',
        ],
    ]);

    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);

    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected)
        ->and($checkout->shipping_method_id)->toBe($rate->id);
});

it('skips shipping for digital-only carts', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 1000,
        'status' => VariantStatus::Active,
        'requires_shipping' => false,
    ]);
    $this->cartService->addLine($cart, $variant->id, 1);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '456 Oak Ave',
            'city' => 'LA',
            'country' => 'US',
            'postal_code' => '90001',
        ],
    ]);

    $checkout = $this->checkoutService->setShippingMethod($checkout, null);

    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected)
        ->and($checkout->shipping_method_id)->toBeNull();
});

it('rejects setting address from wrong state', function () {
    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'status' => CheckoutStatus::Completed,
    ]);

    $this->checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => ['city' => 'Test'],
    ]);
})->throws(InvalidCheckoutTransitionException::class);

it('selects payment method and reserves inventory', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 1000,
        'status' => VariantStatus::Active,
        'requires_shipping' => false,
    ]);
    $inventoryItem = InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
    ]);
    $this->cartService->addLine($cart, $variant->id, 2);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '456 Oak Ave',
            'city' => 'LA',
            'country' => 'US',
            'postal_code' => '90001',
        ],
    ]);
    $checkout = $this->checkoutService->setShippingMethod($checkout, null);
    $checkout = $this->checkoutService->selectPaymentMethod($checkout, 'credit_card');

    expect($checkout->status)->toBe(CheckoutStatus::PaymentPending)
        ->and($checkout->payment_method)->toBe('credit_card')
        ->and($inventoryItem->fresh()->quantity_reserved)->toBe(2);
});

it('expires a checkout and releases reserved inventory', function () {
    $cart = $this->cartService->create($this->store);
    $product = Product::factory()->create([
        'store_id' => $this->store->id,
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 1000,
        'status' => VariantStatus::Active,
        'requires_shipping' => false,
    ]);
    $inventoryItem = InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
    ]);
    $this->cartService->addLine($cart, $variant->id, 3);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '456 Oak Ave',
            'city' => 'LA',
            'country' => 'US',
            'postal_code' => '90001',
        ],
    ]);
    $checkout = $this->checkoutService->setShippingMethod($checkout, null);
    $checkout = $this->checkoutService->selectPaymentMethod($checkout, 'credit_card');

    expect($inventoryItem->fresh()->quantity_reserved)->toBe(3);

    $this->checkoutService->expireCheckout($checkout->fresh());

    expect($checkout->fresh()->status)->toBe(CheckoutStatus::Expired)
        ->and($inventoryItem->fresh()->quantity_reserved)->toBe(0);
});
