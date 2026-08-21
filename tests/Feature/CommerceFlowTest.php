<?php

use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\Discount;
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
            'country' => 'Germany',
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
        'card_expiry' => '12/28',
        'card_cvc' => '123',
        'card_holder' => 'Flow Tester',
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
    $this->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkout['id']}/address", ['shipping_address' => ['first_name' => 'Declined', 'last_name' => 'Tester', 'address1' => '1 Test Street', 'city' => 'Berlin', 'country' => 'Germany', 'country_code' => 'DE', 'postal_code' => '10115']]);
    $rate = ShippingRate::query()->firstOrFail();
    $this->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkout['id']}/shipping-method", ['shipping_method_id' => $rate->getKey()]);

    $this->postJson("http://shop.test/api/storefront/v1/checkouts/{$checkout['id']}/pay", ['payment_method' => 'credit_card', 'card_number' => '4000000000000002', 'card_expiry' => '12/28', 'card_cvc' => '123', 'card_holder' => 'Declined Tester'])->assertUnprocessable()->assertJsonPath('error_code', 'card_declined');

    expect(InventoryItem::query()->where('variant_id', $variant->getKey())->firstOrFail()->quantity_reserved)->toBe($inventoryBefore)
        ->and(Order::query()->where('email', 'declined@example.test')->exists())->toBeFalse();
});

test('automatic discounts stack sequentially during checkout pricing', function (): void {
    $variant = Product::query()->where('handle', 'classic-cotton-t-shirt')->firstOrFail()->variants()->firstOrFail();
    Discount::withoutGlobalScopes()->create(['store_id' => $this->store->getKey(), 'code' => null, 'type' => 'automatic', 'value_type' => 'percent', 'value_amount' => 10, 'status' => 'active', 'starts_at' => now()->subMinute(), 'ends_at' => now()->addDay(), 'rules_json' => []]);
    Discount::withoutGlobalScopes()->create(['store_id' => $this->store->getKey(), 'code' => null, 'type' => 'automatic', 'value_type' => 'fixed', 'value_amount' => 100, 'status' => 'active', 'starts_at' => now()->subMinute(), 'ends_at' => now()->addDay(), 'rules_json' => []]);

    $cart = $this->postJson('http://shop.test/api/storefront/v1/carts')->json();
    $this->postJson("http://shop.test/api/storefront/v1/carts/{$cart['id']}/lines", ['variant_id' => $variant->getKey(), 'quantity' => 1, 'cart_version' => 1]);
    $checkout = $this->postJson('http://shop.test/api/storefront/v1/checkouts', ['cart_id' => $cart['id'], 'email' => 'automatic@example.test'])
        ->assertCreated()
        ->json();

    expect($checkout['totals']['discount'])->toBeGreaterThan(100)
        ->and($checkout['totals']['discount_allocations'])->not->toBeEmpty();
});

test('stale cart versions return a conflict response', function (): void {
    $variant = Product::query()->where('handle', 'classic-cotton-t-shirt')->firstOrFail()->variants()->firstOrFail();
    $cart = $this->postJson('http://shop.test/api/storefront/v1/carts')->json();

    $this->postJson("http://shop.test/api/storefront/v1/carts/{$cart['id']}/lines", ['variant_id' => $variant->getKey(), 'quantity' => 1, 'cart_version' => 1])->assertCreated();
    $this->postJson("http://shop.test/api/storefront/v1/carts/{$cart['id']}/lines", ['variant_id' => $variant->getKey(), 'quantity' => 1, 'cart_version' => 1])->assertConflict();
});

test('guest cart ids authorize stateless access within the current tenant', function (): void {
    $cart = $this->postJson('http://shop.test/api/storefront/v1/carts')->assertCreated()->json();

    $this->flushSession();

    $this->getJson("http://shop.test/api/storefront/v1/carts/{$cart['id']}")
        ->assertOk()
        ->assertJsonPath('id', $cart['id']);
});

test('guest cart ids cannot cross tenant boundaries', function (): void {
    $otherStore = Store::factory()->create();
    $otherCart = \App\Models\Cart::withoutEvents(fn (): \App\Models\Cart => \App\Models\Cart::withoutGlobalScopes()->create([
        'store_id' => $otherStore->getKey(),
        'currency' => 'USD',
        'cart_version' => 1,
        'status' => 'active',
    ]));

    $this->flushSession();

    $this->getJson("http://shop.test/api/storefront/v1/carts/{$otherCart->getKey()}")
        ->assertNotFound();
});

test('guest cart endpoints only expose active carts', function (): void {
    $cart = $this->postJson('http://shop.test/api/storefront/v1/carts')->assertCreated()->json();
    Cart::withoutGlobalScopes()->whereKey($cart['id'])->update(['status' => 'converted']);

    $this->getJson("http://shop.test/api/storefront/v1/carts/{$cart['id']}")
        ->assertNotFound();
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
    $this->putJson("http://shop.test/api/storefront/v1/checkouts/{$checkout['id']}/address", ['shipping_address' => ['first_name' => 'Bank', 'last_name' => 'Tester', 'address1' => '1 Test Street', 'city' => 'Berlin', 'country' => 'Germany', 'country_code' => 'DE', 'postal_code' => '10115']]);
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
