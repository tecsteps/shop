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
use App\Models\Store;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(CheckoutService::class);
    $this->store = Store::factory()->create();

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 1000,
    ]);
    InventoryItem::factory()->withStock(10)->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
    ]);

    $this->cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $this->cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'line_subtotal_amount' => 1000,
    ]);

    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);
    $this->rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'config_json' => ['amount' => 500],
    ]);
});

it('rejects transition from started to shipping_selected', function () {
    $checkout = $this->service->createFromCart($this->cart);

    $this->service->setShippingMethod($checkout, $this->rate->id);
})->throws(InvalidCheckoutTransitionException::class);

it('rejects transition from started to payment_selected', function () {
    $checkout = $this->service->createFromCart($this->cart);

    $this->service->selectPaymentMethod($checkout, 'credit_card');
})->throws(InvalidCheckoutTransitionException::class);

it('rejects transition from addressed to payment_selected', function () {
    $checkout = $this->service->createFromCart($this->cart);
    $checkout = $this->service->setAddress($checkout, [
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

    $this->service->selectPaymentMethod($checkout, 'credit_card');
})->throws(InvalidCheckoutTransitionException::class);

it('rejects invalid payment method', function () {
    $checkout = $this->service->createFromCart($this->cart);
    $checkout = $this->service->setAddress($checkout, [
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
    $checkout = $this->service->setShippingMethod($checkout, $this->rate->id);

    $this->service->selectPaymentMethod($checkout, 'bitcoin');
})->throws(InvalidArgumentException::class);

it('allows valid transition sequence', function () {
    $checkout = $this->service->createFromCart($this->cart);
    expect($checkout->status)->toBe(CheckoutStatus::Started);

    $checkout = $this->service->setAddress($checkout, [
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
    expect($checkout->status)->toBe(CheckoutStatus::Addressed);

    $checkout = $this->service->setShippingMethod($checkout, $this->rate->id);
    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected);

    $checkout = $this->service->selectPaymentMethod($checkout, 'paypal');
    expect($checkout->status)->toBe(CheckoutStatus::PaymentSelected);
});

it('expires a checkout with started status', function () {
    $checkout = $this->service->createFromCart($this->cart);

    $this->service->expireCheckout($checkout);

    expect($checkout->fresh()->status)->toBe(CheckoutStatus::Expired);
});

it('releases inventory when expiring a payment_selected checkout', function () {
    $checkout = $this->service->createFromCart($this->cart);
    $checkout = $this->service->setAddress($checkout, [
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
    $checkout = $this->service->setShippingMethod($checkout, $this->rate->id);
    $checkout = $this->service->selectPaymentMethod($checkout, 'credit_card');

    $line = $this->cart->lines->first();
    $inventoryBefore = $line->variant->inventoryItem->fresh()->quantity_reserved;

    $this->service->expireCheckout($checkout);

    $inventoryAfter = $line->variant->inventoryItem->fresh()->quantity_reserved;

    expect($checkout->fresh()->status)->toBe(CheckoutStatus::Expired)
        ->and($inventoryAfter)->toBe($inventoryBefore - 1);
});

it('does not expire already completed checkout', function () {
    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => CheckoutStatus::Completed,
    ]);

    $this->service->expireCheckout($checkout);

    expect($checkout->fresh()->status)->toBe(CheckoutStatus::Completed);
});

it('rejects shipping rate from wrong zone', function () {
    $otherStore = Store::factory()->create();
    $otherZone = ShippingZone::factory()->create([
        'store_id' => $otherStore->id,
        'countries_json' => ['US'],
    ]);
    $wrongRate = ShippingRate::factory()->create(['zone_id' => $otherZone->id]);

    $checkout = $this->service->createFromCart($this->cart);
    $checkout = $this->service->setAddress($checkout, [
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

    $this->service->setShippingMethod($checkout, $wrongRate->id);
})->throws(InvalidArgumentException::class);
