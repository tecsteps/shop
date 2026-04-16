<?php

use App\Enums\FinancialStatus;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Services\Orders\OrderService;
use App\Services\Payments\MockPaymentProvider;

beforeEach(function () {
    $this->store = bindCurrentStore(makeStore());

    TaxSettings::create([
        'store_id' => $this->store->id,
        'mode' => 'manual',
        'prices_include_tax' => false,
        'config_json' => ['default_rate' => 0.19],
    ]);

    $zone = ShippingZone::create([
        'store_id' => $this->store->id,
        'name' => 'EU',
        'countries_json' => ['DE', 'AT', 'CH'],
        'regions_json' => [],
    ]);

    $this->shippingRate = ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Standard',
        'type' => 'flat',
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);

    $product = Product::create([
        'store_id' => $this->store->id,
        'title' => 'Widget',
        'handle' => 'widget',
        'status' => 'active',
        'description_html' => '',
        'tags' => [],
        'published_at' => now(),
    ]);
    $this->variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'SKU-1',
        'price_amount' => 2500,
        'currency' => 'EUR',
        'requires_shipping' => true,
        'is_default' => true,
        'position' => 0,
        'status' => 'active',
    ]);
    $this->inventory = InventoryItem::create([
        'store_id' => $this->store->id,
        'variant_id' => $this->variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);
});

it('supports the full cart-to-order flow', function () {
    $cart = Cart::create([
        'store_id' => $this->store->id,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => 'active',
    ]);

    app(CartService::class)->addLine($cart, $this->variant, 2);
    $cart->refresh()->load('lines');
    expect($cart->lines)->toHaveCount(1)
        ->and($cart->lines->first()->line_subtotal_amount)->toBe(5000);

    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->startFromCart($cart);
    $checkoutService->setContact($checkout, 'jane@example.com');
    $checkoutService->setAddresses($checkout, [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'address1' => '123 Main St',
        'city' => 'Berlin',
        'zip' => '10115',
        'country' => 'DE',
    ]);
    $checkoutService->selectShipping($checkout, $this->shippingRate->id);
    $checkoutService->selectPayment($checkout, 'credit_card');
    $checkout->refresh();

    $order = app(OrderService::class)->placeFromCheckout($checkout, [
        'method' => 'credit_card',
        'card_number' => MockPaymentProvider::MAGIC_SUCCESS,
    ]);

    expect($order)->toBeInstanceOf(Order::class)
        ->and($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->lines)->toHaveCount(1)
        ->and($order->total_amount)->toBe(5000 + 499 + (int) round(5000 * 0.19));

    expect($this->inventory->fresh()->quantity_on_hand)->toBe(8);
});

it('rejects payment with the decline magic card', function () {
    $cart = Cart::create([
        'store_id' => $this->store->id,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => 'active',
    ]);
    app(CartService::class)->addLine($cart, $this->variant, 1);

    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->startFromCart($cart);
    $checkoutService->setContact($checkout, 'j@e.test');
    $checkoutService->setAddresses($checkout, ['country' => 'DE']);
    $checkoutService->selectShipping($checkout, $this->shippingRate->id);
    $checkoutService->selectPayment($checkout, 'credit_card');

    app(OrderService::class)->placeFromCheckout($checkout->refresh(), [
        'method' => 'credit_card',
        'card_number' => MockPaymentProvider::MAGIC_DECLINED,
    ]);
})->throws(\App\Services\Orders\PaymentFailedException::class);

it('enforces tenant isolation on products', function () {
    $storeA = $this->store;
    $storeB = makeStore();

    Product::create([
        'store_id' => $storeA->id,
        'title' => 'A only',
        'handle' => 'a-only',
        'status' => 'active',
        'description_html' => '',
        'tags' => [],
        'published_at' => now(),
    ]);
    Product::create([
        'store_id' => $storeB->id,
        'title' => 'B only',
        'handle' => 'b-only',
        'status' => 'active',
        'description_html' => '',
        'tags' => [],
        'published_at' => now(),
    ]);

    bindCurrentStore($storeB);

    expect(Product::query()->pluck('handle')->all())->toContain('b-only')
        ->and(Product::query()->pluck('handle')->all())->not->toContain('a-only');
});

it('customer can register and become authenticatable', function () {
    $customer = Customer::create([
        'store_id' => $this->store->id,
        'email' => 'c@t.test',
        'password_hash' => 'password',
        'name' => 'Test',
        'marketing_opt_in' => false,
    ]);

    expect($customer->getAuthPassword())->not->toBe('password');
    expect(Hash::check('password', $customer->password_hash))->toBeTrue();
});
