<?php

use App\Enums\PaymentStatus;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\Store;
use App\Services\OrderService;
use Database\Seeders\ShopSeeder;

uses(\Illuminate\Foundation\Testing\LazilyRefreshDatabase::class);

beforeEach(function (): void {
    config(['cache.default' => 'array']);
    $this->seed(ShopSeeder::class);
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    app()->instance('current_store', $this->store);
});

test('guest cart and card checkout create a paid order', function (): void {
    $variant = Product::query()->where('handle', 'classic-cotton-t-shirt')->firstOrFail()->variants()->firstOrFail();

    $cart = $this->postJson('http://shop.test/api/storefront/v1/carts', ['currency' => 'EUR'])
        ->assertCreated()
        ->json();

    $this->postJson("http://shop.test/api/storefront/v1/carts/{$cart['id']}/lines", [
        'variant_id' => $variant->getKey(),
        'quantity' => 1,
        'cart_version' => 1,
    ])->assertCreated();

    $checkout = $this->postJson('http://shop.test/api/storefront/v1/checkouts', [
        'cart_id' => $cart['id'],
        'email' => 'flow@example.test',
    ])->assertCreated()->json();

    $this->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkout['id']}/address", [
        'shipping_address' => [
            'first_name' => 'Flow',
            'last_name' => 'Tester',
            'address1' => '1 Test Street',
            'city' => 'Berlin',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
    ])->assertOk()->assertJsonPath('status', 'addressed');

    $rate = ShippingRate::query()->firstOrFail();
    $this->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkout['id']}/shipping-method", [
        'shipping_method_id' => $rate->getKey(),
    ])->assertOk()->assertJsonPath('status', 'shipping_selected');

    $this->postJson("http://shop.test/api/storefront/v1/checkouts/{$checkout['id']}/pay", [
        'payment_method' => 'credit_card',
        'card_number' => '4242424242424242',
    ])->assertOk()->assertJsonPath('order.financial_status', 'paid');

    $order = Order::query()->latest('id')->firstOrFail();

    expect($order->payment_method)->toBe('credit_card')
        ->and($order->financial_status->value)->toBe('paid')
        ->and($order->payments()->firstOrFail()->status)->toBe(PaymentStatus::Captured)
        ->and($order->checkout->status->value)->toBe('completed');
});

test('declined payments release the reservation and do not create an order', function (): void {
    $variant = Product::query()->where('handle', 'classic-cotton-t-shirt')->firstOrFail()->variants()->firstOrFail();
    $inventoryBefore = InventoryItem::query()->where('variant_id', $variant->getKey())->firstOrFail()->quantity_reserved;

    $cart = $this->postJson('http://shop.test/api/storefront/v1/carts')->json();
    $this->postJson("http://shop.test/api/storefront/v1/carts/{$cart['id']}/lines", ['variant_id' => $variant->getKey(), 'quantity' => 1, 'cart_version' => 1]);
    $checkout = $this->postJson('http://shop.test/api/storefront/v1/checkouts', ['cart_id' => $cart['id'], 'email' => 'declined@example.test'])->json();
    $this->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkout['id']}/address", ['shipping_address' => ['first_name' => 'Declined', 'last_name' => 'Tester', 'address1' => '1 Test Street', 'city' => 'Berlin', 'country_code' => 'DE', 'postal_code' => '10115']]);
    $rate = ShippingRate::query()->firstOrFail();
    $this->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkout['id']}/shipping-method", ['shipping_method_id' => $rate->getKey()]);

    $this->postJson("http://shop.test/api/storefront/v1/checkouts/{$checkout['id']}/pay", ['payment_method' => 'credit_card', 'card_number' => '4000000000000002'])->assertUnprocessable();

    expect(InventoryItem::query()->where('variant_id', $variant->getKey())->firstOrFail()->quantity_reserved)->toBe($inventoryBefore)
        ->and(Order::query()->where('email', 'declined@example.test')->exists())->toBeFalse();
});

test('stale cart versions return a conflict response', function (): void {
    $variant = Product::query()->where('handle', 'classic-cotton-t-shirt')->firstOrFail()->variants()->firstOrFail();
    $cart = $this->postJson('http://shop.test/api/storefront/v1/carts')->json();

    $this->postJson("http://shop.test/api/storefront/v1/carts/{$cart['id']}/lines", ['variant_id' => $variant->getKey(), 'quantity' => 1, 'cart_version' => 1])->assertCreated();
    $this->postJson("http://shop.test/api/storefront/v1/carts/{$cart['id']}/lines", ['variant_id' => $variant->getKey(), 'quantity' => 1, 'cart_version' => 1])->assertConflict();
});

test('guest cart API resources are bound to the current session', function (): void {
    $firstCart = $this->postJson('http://shop.test/api/storefront/v1/carts')->assertCreated()->json();
    $secondCart = $this->postJson('http://shop.test/api/storefront/v1/carts')->assertCreated()->json();

    $this->getJson("http://shop.test/api/storefront/v1/carts/{$firstCart['id']}")->assertNotFound();
    $this->getJson("http://shop.test/api/storefront/v1/carts/{$secondCart['id']}")->assertOk();
});

test('cart and checkout APIs return domain errors as unprocessable responses', function (): void {
    $soldOutVariant = Product::query()->where('handle', 'sold-out-limited-tee')->firstOrFail()->variants()->firstOrFail();
    $cart = $this->postJson('http://shop.test/api/storefront/v1/carts')->json();

    $this->postJson("http://shop.test/api/storefront/v1/carts/{$cart['id']}/lines", [
        'variant_id' => $soldOutVariant->getKey(),
        'quantity' => 1,
        'cart_version' => 1,
    ])->assertUnprocessable()->assertJsonPath('code', 'insufficient_inventory');

    $variant = Product::query()->where('handle', 'classic-cotton-t-shirt')->firstOrFail()->variants()->firstOrFail();
    $this->postJson("http://shop.test/api/storefront/v1/carts/{$cart['id']}/lines", ['variant_id' => $variant->getKey(), 'quantity' => 1, 'cart_version' => 1])->assertCreated();
    $checkout = $this->postJson('http://shop.test/api/storefront/v1/checkouts', ['cart_id' => $cart['id'], 'email' => 'discount-error@example.test'])->assertCreated()->json();

    $this->postJson("http://shop.test/api/storefront/v1/checkouts/{$checkout['id']}/apply-discount", ['code' => 'MISSING'])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'discount_not_found');
});

test('bank transfer keeps inventory reserved until admin confirmation', function (): void {
    $variant = Product::query()->where('handle', 'classic-cotton-t-shirt')->firstOrFail()->variants()->firstOrFail();
    $cart = $this->postJson('http://shop.test/api/storefront/v1/carts')->json();
    $this->postJson("http://shop.test/api/storefront/v1/carts/{$cart['id']}/lines", ['variant_id' => $variant->getKey(), 'quantity' => 1, 'cart_version' => 1]);
    $checkout = $this->postJson('http://shop.test/api/storefront/v1/checkouts', ['cart_id' => $cart['id'], 'email' => 'bank@example.test'])->json();
    $this->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkout['id']}/address", ['shipping_address' => ['first_name' => 'Bank', 'last_name' => 'Tester', 'address1' => '1 Test Street', 'city' => 'Berlin', 'country_code' => 'DE', 'postal_code' => '10115']]);
    $rate = ShippingRate::query()->firstOrFail();
    $this->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkout['id']}/shipping-method", ['shipping_method_id' => $rate->getKey()]);

    $response = $this->postJson("http://shop.test/api/storefront/v1/checkouts/{$checkout['id']}/pay", ['payment_method' => 'bank_transfer'])->assertOk()->json();
    $order = Order::query()->whereKey($response['order']['id'])->firstOrFail();
    $inventory = InventoryItem::query()->where('variant_id', $variant->getKey())->firstOrFail();

    expect($order->financial_status->value)->toBe('pending')->and($inventory->quantity_reserved)->toBe(1);

    app(OrderService::class)->confirmPayment($order);
    $inventory = $inventory->refresh();

    expect($order->refresh()->financial_status->value)->toBe('paid')
        ->and($inventory->quantity_on_hand)->toBe(79)
        ->and($inventory->quantity_reserved)->toBe(0);
});
