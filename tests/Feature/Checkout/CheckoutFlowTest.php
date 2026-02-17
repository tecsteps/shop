<?php

use App\Enums\CheckoutStatus;
use App\Enums\VariantStatus;
use App\Models\Cart;
use App\Models\CartLine;
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

    $this->product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'price_amount' => 2999,
        'status' => VariantStatus::Active,
    ]);
    InventoryItem::factory()->withStock(25)->create([
        'store_id' => $this->store->id,
        'variant_id' => $this->variant->id,
    ]);

    $this->cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $this->cart->id,
        'variant_id' => $this->variant->id,
        'quantity' => 1,
        'unit_price_amount' => 2999,
        'line_subtotal_amount' => 2999,
        'line_total_amount' => 2999,
    ]);

    $this->zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);
    $this->rate = ShippingRate::factory()->create([
        'zone_id' => $this->zone->id,
        'config_json' => ['amount' => 499],
    ]);
});

it('creates a checkout from cart', function () {
    $checkout = $this->service->createFromCart($this->cart);

    expect($checkout->store_id)->toBe($this->store->id)
        ->and($checkout->cart_id)->toBe($this->cart->id)
        ->and($checkout->status)->toBe(CheckoutStatus::Started);
});

it('completes the full flow: address -> shipping -> payment', function () {
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

    expect($checkout->status)->toBe(CheckoutStatus::Addressed)
        ->and($checkout->email)->toBe('test@example.com');

    $checkout = $this->service->setShippingMethod($checkout, $this->rate->id);

    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected);

    $checkout = $this->service->selectPaymentMethod($checkout, 'credit_card');

    expect($checkout->status)->toBe(CheckoutStatus::PaymentSelected)
        ->and($checkout->payment_method)->toBe('credit_card')
        ->and($checkout->expires_at)->not->toBeNull();
});

it('copies shipping address to billing by default', function () {
    $checkout = $this->service->createFromCart($this->cart);

    $address = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address1' => '123 Main St',
        'city' => 'Berlin',
        'country' => 'DE',
        'postal_code' => '10115',
    ];

    $checkout = $this->service->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => $address,
    ]);

    expect($checkout->billing_address_json)->toBe($address);
});

it('stores totals json after address set', function () {
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

    expect($checkout->totals_json)->not->toBeNull()
        ->and($checkout->totals_json)->toHaveKey('subtotal');
});

it('reserves inventory on payment method selection', function () {
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

    $inventoryItem = $this->variant->inventoryItem->fresh();

    expect($inventoryItem->quantity_reserved)->toBe(1);
});
