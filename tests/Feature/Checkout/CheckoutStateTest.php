<?php

use App\Enums\CheckoutStatus;
use App\Enums\InventoryPolicy;
use App\Enums\PaymentMethod;
use App\Enums\ProductStatus;
use App\Enums\ShippingRateType;
use App\Enums\VariantStatus;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\CheckoutService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->cartService = app(CartService::class);
    $this->checkoutService = app(CheckoutService::class);

    // Create a standard cart with items for each test
    $this->cart = $this->cartService->create($this->store);
    $this->variant = createStateTestVariant($this->store);
    $this->cartService->addLine($this->cart, $this->variant->id, 1);

    $this->zone = ShippingZone::create([
        'store_id' => $this->store->id,
        'name' => 'Zone',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);
    $this->rate = ShippingRate::create([
        'zone_id' => $this->zone->id,
        'name' => 'Standard',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);
});

it('transitions from started to addressed', function () {
    $checkout = Checkout::create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'user@example.com',
        'shipping_address' => ['country' => 'DE', 'city' => 'Berlin'],
    ]);

    expect($checkout->status)->toBe(CheckoutStatus::Addressed);
    expect($checkout->email)->toBe('user@example.com');
    expect($checkout->shipping_address_json)->toHaveKey('country', 'DE');
});

it('transitions from addressed to shipping_selected', function () {
    $checkout = Checkout::create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => CheckoutStatus::Started,
    ]);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'user@example.com',
        'shipping_address' => ['country' => 'DE'],
    ]);

    $checkout = $this->checkoutService->setShippingMethod($checkout, $this->rate->id);

    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected);
    expect($checkout->shipping_method_id)->toBe($this->rate->id);
});

it('transitions from shipping_selected to payment_selected', function () {
    $checkout = Checkout::create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => CheckoutStatus::Started,
    ]);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'user@example.com',
        'shipping_address' => ['country' => 'DE'],
    ]);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $this->rate->id);

    $checkout = $this->checkoutService->selectPaymentMethod($checkout, PaymentMethod::Paypal);

    expect($checkout->status)->toBe(CheckoutStatus::PaymentSelected);
    expect($checkout->payment_method)->toBe(PaymentMethod::Paypal);
});

it('rejects setAddress when not in started state', function () {
    $checkout = Checkout::create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => CheckoutStatus::Started,
    ]);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'user@example.com',
        'shipping_address' => ['country' => 'DE'],
    ]);

    // Now in addressed state, cannot setAddress again
    expect(fn () => $this->checkoutService->setAddress($checkout, [
        'email' => 'new@example.com',
        'shipping_address' => ['country' => 'US'],
    ]))->toThrow(InvalidArgumentException::class);
});

it('rejects setShippingMethod when not in addressed state', function () {
    $checkout = Checkout::create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    expect(fn () => $this->checkoutService->setShippingMethod($checkout, $this->rate->id))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects selectPaymentMethod when not in shipping_selected state', function () {
    $checkout = Checkout::create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    expect(fn () => $this->checkoutService->selectPaymentMethod($checkout, PaymentMethod::CreditCard))
        ->toThrow(InvalidArgumentException::class);
});

it('sets billing address to shipping address when not provided', function () {
    $checkout = Checkout::create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    $shippingAddress = ['country' => 'DE', 'city' => 'Munich'];
    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'user@example.com',
        'shipping_address' => $shippingAddress,
    ]);

    expect($checkout->billing_address_json)->toEqual($shippingAddress);
});

it('uses separate billing address when provided', function () {
    $checkout = Checkout::create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'user@example.com',
        'shipping_address' => ['country' => 'DE', 'city' => 'Berlin'],
        'billing_address' => ['country' => 'DE', 'city' => 'Munich'],
    ]);

    expect($checkout->shipping_address_json)->toHaveKey('city', 'Berlin');
    expect($checkout->billing_address_json)->toHaveKey('city', 'Munich');
});

it('expires checkout from any active state', function () {
    // Test expiring from addressed state (no inventory reserved)
    $checkout = Checkout::create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => CheckoutStatus::Started,
    ]);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'user@example.com',
        'shipping_address' => ['country' => 'DE'],
    ]);

    $this->checkoutService->expireCheckout($checkout);
    $checkout->refresh();

    expect($checkout->status)->toBe(CheckoutStatus::Expired);
});

// --- Helper ---

function createStateTestVariant($store): ProductVariant
{
    $product = Product::factory()->create([
        'store_id' => $store->id,
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 5000,
        'status' => VariantStatus::Active,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    return $variant;
}
