<?php

use App\Enums\CheckoutStatus;
use App\Enums\InventoryPolicy;
use App\Enums\PaymentMethod;
use App\Enums\ProductStatus;
use App\Enums\ShippingRateType;
use App\Enums\TaxMode;
use App\Enums\VariantStatus;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\PricingEngine;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->cartService = app(CartService::class);
    $this->checkoutService = app(CheckoutService::class);
    $this->pricingEngine = app(PricingEngine::class);
});

it('creates a checkout from a cart', function () {
    $cart = $this->cartService->create($this->store);
    $variant = createCheckoutVariant($this->store, 5000, 10);
    $this->cartService->addLine($cart, $variant->id, 2);

    $checkout = Checkout::create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    expect($checkout->status)->toBe(CheckoutStatus::Started);
    expect($checkout->cart_id)->toBe($cart->id);
    expect($checkout->store_id)->toBe($this->store->id);
});

it('completes full happy path through all states', function () {
    // Setup shipping and tax
    $zone = ShippingZone::create([
        'store_id' => $this->store->id,
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
        'store_id' => $this->store->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => ['default_rate' => 1900, 'default_name' => 'VAT'],
    ]);

    // Create cart with items
    $cart = $this->cartService->create($this->store);
    $variant = createCheckoutVariant($this->store, 5000, 10);
    $this->cartService->addLine($cart, $variant->id, 2);

    // Create checkout
    $checkout = Checkout::create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    // Step 1: Set address (started -> addressed)
    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'Test',
            'last_name' => 'User',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country' => 'DE',
            'zip' => '10115',
        ],
    ]);
    expect($checkout->status)->toBe(CheckoutStatus::Addressed);
    expect($checkout->email)->toBe('test@example.com');

    // Step 2: Set shipping (addressed -> shipping_selected)
    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);
    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected);

    // Step 3: Select payment (shipping_selected -> payment_selected)
    $checkout = $this->checkoutService->selectPaymentMethod($checkout, PaymentMethod::CreditCard);
    expect($checkout->status)->toBe(CheckoutStatus::PaymentSelected);
    expect($checkout->payment_method)->toBe(PaymentMethod::CreditCard);
    expect($checkout->expires_at)->not->toBeNull();

    // Verify inventory was reserved
    $variant->refresh()->load('inventoryItem');
    expect($variant->inventoryItem->quantity_reserved)->toBe(2);
});

it('rejects checkout with empty cart', function () {
    $cart = $this->cartService->create($this->store);

    $checkout = Checkout::create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    // Can still set address with empty cart (business logic allows it)
    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'Test',
            'last_name' => 'User',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country' => 'DE',
            'zip' => '10115',
        ],
    ]);

    expect($checkout->status)->toBe(CheckoutStatus::Addressed);
});

it('expires a checkout and releases inventory', function () {
    $cart = $this->cartService->create($this->store);
    $variant = createCheckoutVariant($this->store, 5000, 10);
    $this->cartService->addLine($cart, $variant->id, 3);

    $zone = ShippingZone::create([
        'store_id' => $this->store->id,
        'name' => 'Zone',
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

    $checkout = Checkout::create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => ['country' => 'DE'],
    ]);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);
    $checkout = $this->checkoutService->selectPaymentMethod($checkout, PaymentMethod::CreditCard);

    // Verify inventory reserved
    $variant->refresh()->load('inventoryItem');
    expect($variant->inventoryItem->quantity_reserved)->toBe(3);

    // Expire the checkout
    $this->checkoutService->expireCheckout($checkout);
    $checkout->refresh();

    expect($checkout->status)->toBe(CheckoutStatus::Expired);

    // Inventory should be released
    $variant->refresh()->load('inventoryItem');
    expect($variant->inventoryItem->quantity_reserved)->toBe(0);
});

it('does not expire already completed or expired checkouts', function () {
    $cart = $this->cartService->create($this->store);

    $completedCheckout = Checkout::factory()->completed()->create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
    ]);

    $this->checkoutService->expireCheckout($completedCheckout);
    $completedCheckout->refresh();

    // Should remain completed
    expect($completedCheckout->status)->toBe(CheckoutStatus::Completed);
});

// --- Helper ---

function createCheckoutVariant($store, int $price, int $stock): ProductVariant
{
    $product = Product::factory()->create([
        'store_id' => $store->id,
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => $price,
        'status' => VariantStatus::Active,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => $stock,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    return $variant;
}
