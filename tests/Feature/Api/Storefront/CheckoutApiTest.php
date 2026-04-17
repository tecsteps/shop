<?php

use App\Enums\CheckoutStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Product;
use App\Models\ProductVariant;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $context = createStoreContext();
    $this->store = $context['store'];
    $this->domain = $context['domain'];

    $this->product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'price_amount' => 2500,
    ]);
    $this->cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $this->cart->id,
        'variant_id' => $this->variant->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 5000,
        'line_discount_amount' => 0,
        'line_total_amount' => 5000,
    ]);
});

it('creates a checkout from cart', function () {
    $response = $this->postJson(
        'http://'.$this->domain->hostname.'/api/storefront/v1/checkouts',
        ['cart_id' => $this->cart->id, 'email' => 'test@example.com'],
    );

    $response->assertStatus(201)
        ->assertJsonPath('status', 'started')
        ->assertJsonPath('email', 'test@example.com')
        ->assertJsonPath('cart_id', $this->cart->id);
});

it('rejects checkout with empty cart', function () {
    $emptyCart = Cart::factory()->create(['store_id' => $this->store->id]);

    $response = $this->postJson(
        'http://'.$this->domain->hostname.'/api/storefront/v1/checkouts',
        ['cart_id' => $emptyCart->id, 'email' => 'test@example.com'],
    );

    $response->assertStatus(422)
        ->assertJsonPath('errors.cart_id.0', 'Cart must have at least one line item.');
});

it('rejects checkout with invalid email', function () {
    $response = $this->postJson(
        'http://'.$this->domain->hostname.'/api/storefront/v1/checkouts',
        ['cart_id' => $this->cart->id, 'email' => 'not-an-email'],
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('returns 404 for non-existent cart', function () {
    $response = $this->postJson(
        'http://'.$this->domain->hostname.'/api/storefront/v1/checkouts',
        ['cart_id' => 99999, 'email' => 'test@example.com'],
    );

    $response->assertNotFound();
});

it('shows a checkout', function () {
    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'email' => 'show@example.com',
        'status' => CheckoutStatus::Started,
    ]);

    $response = $this->getJson(
        'http://'.$this->domain->hostname.'/api/storefront/v1/checkouts/'.$checkout->id,
    );

    $response->assertOk()
        ->assertJsonPath('id', $checkout->id)
        ->assertJsonPath('status', 'started');
});

it('returns 410 for expired checkout', function () {
    $checkout = Checkout::factory()->expired()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
    ]);

    $response = $this->getJson(
        'http://'.$this->domain->hostname.'/api/storefront/v1/checkouts/'.$checkout->id,
    );

    $response->assertStatus(410);
});

it('sets address on checkout', function () {
    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'email' => 'address@example.com',
        'status' => CheckoutStatus::Started,
    ]);

    $response = $this->putJson(
        'http://'.$this->domain->hostname.'/api/storefront/v1/checkouts/'.$checkout->id.'/address',
        [
            'shipping_address' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'address1' => '123 Main St',
                'city' => 'Berlin',
                'country' => 'Germany',
                'country_code' => 'DE',
                'postal_code' => '10115',
            ],
        ],
    );

    $response->assertOk()
        ->assertJsonPath('status', 'addressed');
});

it('rejects address with missing required fields', function () {
    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'email' => 'addr@example.com',
        'status' => CheckoutStatus::Started,
    ]);

    $response = $this->putJson(
        'http://'.$this->domain->hostname.'/api/storefront/v1/checkouts/'.$checkout->id.'/address',
        ['shipping_address' => ['first_name' => 'Jane']],
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'shipping_address.last_name',
            'shipping_address.address1',
            'shipping_address.city',
            'shipping_address.country',
            'shipping_address.country_code',
            'shipping_address.postal_code',
        ]);
});

it('selects payment method', function () {
    $checkout = Checkout::factory()->shippingSelected()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
    ]);

    $response = $this->putJson(
        'http://'.$this->domain->hostname.'/api/storefront/v1/checkouts/'.$checkout->id.'/payment-method',
        ['payment_method' => 'credit_card'],
    );

    $response->assertOk()
        ->assertJsonPath('status', 'payment_selected')
        ->assertJsonPath('payment_method', 'credit_card');
});
