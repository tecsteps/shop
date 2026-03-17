<?php

use App\Enums\CheckoutStatus;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\CheckoutService;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->checkoutService = app(CheckoutService::class);
    $this->cartService = app(CartService::class);
});

function createCartWithItems($store, ?array $items = null): Cart
{
    $items = $items ?? [['price' => 2500, 'quantity' => 2]];
    $cart = app(CartService::class)->create($store);

    foreach ($items as $item) {
        $product = Product::factory()->active()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_amount' => $item['price'],
        ]);
        app(CartService::class)->addLine($cart, $variant->id, $item['quantity']);
    }

    return $cart->fresh('lines');
}

it('creates a checkout from a cart', function () {
    $cart = createCartWithItems($this->store, [
        ['price' => 2500, 'quantity' => 1],
        ['price' => 3500, 'quantity' => 1],
    ]);

    $checkout = $this->checkoutService->createFromCart($cart);

    expect($checkout)->toBeInstanceOf(Checkout::class)
        ->and($checkout->status)->toBe(CheckoutStatus::Started)
        ->and($checkout->cart_id)->toBe($cart->id)
        ->and($checkout->store_id)->toBe($this->store->id);
});

it('completes full checkout happy path through payment selection', function () {
    $cart = createCartWithItems($this->store, [['price' => 2500, 'quantity' => 2]]);
    $checkout = $this->checkoutService->createFromCart($cart);

    // Set address
    $addressData = [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'postal_code' => '10001',
        ],
    ];
    $checkout = $this->checkoutService->setAddress($checkout, $addressData);
    expect($checkout->status)->toBe(CheckoutStatus::Addressed);

    // Set shipping (skip for digital or set null for no shipping)
    $checkout = $this->checkoutService->setShippingMethod($checkout, null);
    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected);

    // Select payment
    $checkout = $this->checkoutService->selectPaymentMethod($checkout, 'credit_card');
    expect($checkout->status)->toBe(CheckoutStatus::PaymentSelected)
        ->and($checkout->expires_at)->not->toBeNull();
});

it('rejects checkout for empty cart via API', function () {
    $cart = $this->cartService->create($this->store);

    $response = $this->postJson('http://acme-fashion.test/api/storefront/v1/checkouts', [
        'cart_id' => $cart->id,
        'email' => 'test@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Cart is empty.');
});

it('expires checkout after timeout', function () {
    $cart = createCartWithItems($this->store);
    $checkout = $this->checkoutService->createFromCart($cart);

    // Manually move to PaymentSelected with past expiry
    $checkout->update([
        'status' => CheckoutStatus::PaymentSelected,
        'payment_method' => 'credit_card',
        'expires_at' => now()->subHour(),
    ]);

    $this->checkoutService->expireCheckout($checkout->fresh());

    expect($checkout->fresh()->status)->toBe(CheckoutStatus::Expired);
});

it('prevents operating on expired checkout via API', function () {
    $cart = createCartWithItems($this->store);
    $checkout = Checkout::factory()->expired()->create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
    ]);

    $response = $this->getJson("http://acme-fashion.test/api/storefront/v1/checkouts/{$checkout->id}");

    $response->assertStatus(410);
});
