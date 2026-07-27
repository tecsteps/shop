<?php

use App\Enums\CheckoutStatus;
use App\Models\Checkout;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\CartService;
use App\Services\CheckoutService;

/**
 * Absolute storefront API URL for the store's primary domain.
 */
function payApiUrl(Store $store, string $path): string
{
    return 'http://'.$store->handle.'.test/api/storefront/v1'.$path;
}

/**
 * Store with variant (2500, 10 on hand), DE zone with flat rate 499.
 *
 * @return array{0: Store, 1: ProductVariant, 2: ShippingRate}
 */
function paySetup(): array
{
    $store = test()->createStore();
    test()->bindStore($store);

    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->withInventory(10)->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
    ]);

    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->flat(499)->create(['zone_id' => $zone->id]);

    return [$store, $variant, $rate];
}

/**
 * Drive a checkout to payment_selected with the given method (qty 2).
 */
function payCheckout(Store $store, ProductVariant $variant, ShippingRate $rate, string $method = 'credit_card'): Checkout
{
    $service = app(CheckoutService::class);

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, 2);

    $checkout = $service->createFromCart($cart->refresh(), 'customer@example.com');
    $checkout = $service->setAddress($checkout, [
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country' => 'DE',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
    ]);
    $checkout = $service->setShippingMethod($checkout, $rate->id);

    return $service->selectPaymentMethod($checkout, $method);
}

test('pays with credit card and returns the order payload', function () {
    [$store, $variant, $rate] = paySetup();
    $checkout = payCheckout($store, $variant, $rate);

    $response = $this->postJson(payApiUrl($store, "/checkouts/{$checkout->id}/pay"), [
        'payment_method' => 'credit_card',
        'card_number' => '4242424242424242',
        'card_expiry' => '12/28',
        'card_cvc' => '123',
        'card_holder' => 'Jane Doe',
    ]);

    $response->assertOk()
        ->assertJsonPath('checkout_id', $checkout->id)
        ->assertJsonPath('status', 'completed')
        ->assertJsonPath('order.order_number', '#1001')
        ->assertJsonPath('order.status', 'paid')
        ->assertJsonPath('order.financial_status', 'paid')
        ->assertJsonPath('order.payment_method', 'credit_card')
        ->assertJsonPath('order.total_amount', 5499);

    expect($checkout->refresh()->status)->toBe(CheckoutStatus::Completed);
});

test('returns 422 with error code when card is declined', function () {
    [$store, $variant, $rate] = paySetup();
    $checkout = payCheckout($store, $variant, $rate);

    $this->postJson(payApiUrl($store, "/checkouts/{$checkout->id}/pay"), [
        'payment_method' => 'credit_card',
        'card_number' => '4000000000000002',
        'card_expiry' => '12/28',
        'card_cvc' => '123',
        'card_holder' => 'Jane Doe',
    ])->assertUnprocessable()
        ->assertJsonPath('error_code', 'card_declined')
        ->assertJsonPath('message', 'Your card was declined.');

    expect($checkout->refresh()->status)->toBe(CheckoutStatus::PaymentSelected)
        ->and(Order::withoutGlobalScopes()->count())->toBe(0)
        ->and($variant->inventoryItem->refresh()->quantity_reserved)->toBe(0);
});

test('can retry with a different card after a decline', function () {
    [$store, $variant, $rate] = paySetup();
    $checkout = payCheckout($store, $variant, $rate);

    $this->postJson(payApiUrl($store, "/checkouts/{$checkout->id}/pay"), [
        'payment_method' => 'credit_card',
        'card_number' => '4000000000000002',
        'card_expiry' => '12/28',
        'card_cvc' => '123',
        'card_holder' => 'Jane Doe',
    ])->assertUnprocessable();

    $this->postJson(payApiUrl($store, "/checkouts/{$checkout->id}/pay"), [
        'payment_method' => 'credit_card',
        'card_number' => '4242424242424242',
        'card_expiry' => '12/28',
        'card_cvc' => '123',
        'card_holder' => 'Jane Doe',
    ])->assertOk()
        ->assertJsonPath('status', 'completed');

    $item = $variant->inventoryItem->refresh();
    expect($item->quantity_on_hand)->toBe(8)
        ->and($item->quantity_reserved)->toBe(0);
});

test('returns 422 with insufficient funds error code', function () {
    [$store, $variant, $rate] = paySetup();
    $checkout = payCheckout($store, $variant, $rate);

    $this->postJson(payApiUrl($store, "/checkouts/{$checkout->id}/pay"), [
        'payment_method' => 'credit_card',
        'card_number' => '4000000000009995',
        'card_expiry' => '12/28',
        'card_cvc' => '123',
        'card_holder' => 'Jane Doe',
    ])->assertUnprocessable()
        ->assertJsonPath('error_code', 'insufficient_funds');
});

test('pays with bank transfer and returns instructions', function () {
    [$store, $variant, $rate] = paySetup();
    $checkout = payCheckout($store, $variant, $rate, 'bank_transfer');

    $response = $this->postJson(payApiUrl($store, "/checkouts/{$checkout->id}/pay"), [
        'payment_method' => 'bank_transfer',
    ]);

    $response->assertOk()
        ->assertJsonPath('status', 'completed')
        ->assertJsonPath('order.status', 'pending')
        ->assertJsonPath('order.financial_status', 'pending')
        ->assertJsonPath('order.payment_method', 'bank_transfer')
        ->assertJsonPath('bank_transfer_instructions.bank_name', 'Mock Bank AG')
        ->assertJsonPath('bank_transfer_instructions.iban', 'DE89 3704 0044 0532 0130 00')
        ->assertJsonPath('bank_transfer_instructions.bic', 'COBADEFFXXX')
        ->assertJsonPath('bank_transfer_instructions.reference', '#1001')
        ->assertJsonPath('bank_transfer_instructions.amount_formatted', '54.99 USD');

    // Inventory stays reserved while awaiting payment.
    expect($variant->inventoryItem->refresh()->quantity_reserved)->toBe(2)
        ->and($variant->inventoryItem->quantity_on_hand)->toBe(10);
});

test('pays with paypal and captures immediately', function () {
    [$store, $variant, $rate] = paySetup();
    $checkout = payCheckout($store, $variant, $rate, 'paypal');

    $this->postJson(payApiUrl($store, "/checkouts/{$checkout->id}/pay"), [
        'payment_method' => 'paypal',
    ])->assertOk()
        ->assertJsonPath('order.status', 'paid')
        ->assertJsonPath('order.payment_method', 'paypal');
});

test('returns 409 when checkout is not in payment selected state', function () {
    [$store, $variant, $rate] = paySetup();

    $service = app(CheckoutService::class);
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, 1);
    $checkout = $service->createFromCart($cart->refresh(), 'customer@example.com');
    $checkout = $service->setAddress($checkout, [
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country' => 'DE',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
    ]);

    $this->postJson(payApiUrl($store, "/checkouts/{$checkout->id}/pay"), [
        'payment_method' => 'credit_card',
        'card_number' => '4242424242424242',
        'card_expiry' => '12/28',
        'card_cvc' => '123',
        'card_holder' => 'Jane Doe',
    ])->assertConflict();
});

test('returns 410 when the checkout has expired', function () {
    [$store, $variant, $rate] = paySetup();
    $checkout = payCheckout($store, $variant, $rate);
    $checkout->update(['expires_at' => now()->subHour()]);

    $this->postJson(payApiUrl($store, "/checkouts/{$checkout->id}/pay"), [
        'payment_method' => 'credit_card',
        'card_number' => '4242424242424242',
        'card_expiry' => '12/28',
        'card_cvc' => '123',
        'card_holder' => 'Jane Doe',
    ])->assertGone();
});

test('returns 422 when credit card fields are missing', function () {
    [$store, $variant, $rate] = paySetup();
    $checkout = payCheckout($store, $variant, $rate);

    $this->postJson(payApiUrl($store, "/checkouts/{$checkout->id}/pay"), [
        'payment_method' => 'credit_card',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['card_number', 'card_expiry', 'card_cvc', 'card_holder']);
});

test('double submit returns the same order without duplicates', function () {
    [$store, $variant, $rate] = paySetup();
    $checkout = payCheckout($store, $variant, $rate);

    $payload = [
        'payment_method' => 'credit_card',
        'card_number' => '4242424242424242',
        'card_expiry' => '12/28',
        'card_cvc' => '123',
        'card_holder' => 'Jane Doe',
    ];

    $first = $this->postJson(payApiUrl($store, "/checkouts/{$checkout->id}/pay"), $payload);
    $second = $this->postJson(payApiUrl($store, "/checkouts/{$checkout->id}/pay"), $payload);

    $first->assertOk();
    $second->assertOk();

    expect($second->json('order.id'))->toBe($first->json('order.id'))
        ->and(Order::withoutGlobalScopes()->count())->toBe(1);
});

test('confirmation page renders the placed order', function () {
    [$store, $variant, $rate] = paySetup();
    $checkout = payCheckout($store, $variant, $rate);

    $this->postJson(payApiUrl($store, "/checkouts/{$checkout->id}/pay"), [
        'payment_method' => 'credit_card',
        'card_number' => '4242424242424242',
        'card_expiry' => '12/28',
        'card_cvc' => '123',
        'card_holder' => 'Jane Doe',
    ])->assertOk();

    $this->get('http://'.$store->handle.'.test/checkout/'.$checkout->id.'/confirmation')
        ->assertOk()
        ->assertSee('#1001')
        ->assertSee('customer@example.com')
        ->assertSee('ending in 4242');
});

test('confirmation page shows bank transfer instructions', function () {
    [$store, $variant, $rate] = paySetup();
    $checkout = payCheckout($store, $variant, $rate, 'bank_transfer');

    $this->postJson(payApiUrl($store, "/checkouts/{$checkout->id}/pay"), [
        'payment_method' => 'bank_transfer',
    ])->assertOk();

    $this->get('http://'.$store->handle.'.test/checkout/'.$checkout->id.'/confirmation')
        ->assertOk()
        ->assertSee('Bank Transfer Instructions')
        ->assertSee('DE89 3704 0044 0532 0130 00')
        ->assertSee('#1001');
});
