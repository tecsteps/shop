<?php

use App\Enums\CheckoutStatus;
use App\Enums\InventoryPolicy;
use App\Enums\VariantStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;

beforeEach(function () {
    $this->ctx = createStoreContext('shop.test');
    $this->store = $this->ctx['store'];
    $this->product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $this->variant = ProductVariant::factory()->default()->create([
        'product_id' => $this->product->id,
        'price_amount' => 2500,
        'status' => VariantStatus::Active,
    ]);
    InventoryItem::factory()->withStock(50)->create([
        'store_id' => $this->store->id,
        'variant_id' => $this->variant->id,
        'policy' => InventoryPolicy::Deny,
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
    $this->zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);
    $this->rate = ShippingRate::factory()->create([
        'zone_id' => $this->zone->id,
        'name' => 'Standard Shipping',
        'config_json' => ['amount' => 500],
    ]);
});

it('creates a checkout from a cart', function () {
    $response = $this->withHeaders(['Host' => 'shop.test'])
        ->postJson('/api/storefront/v1/checkouts', [
            'cart_id' => $this->cart->id,
            'email' => 'customer@example.com',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'started')
        ->assertJsonPath('email', 'customer@example.com');
});

it('sets checkout address', function () {
    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    $response = $this->withHeaders(['Host' => 'shop.test'])
        ->putJson("/api/storefront/v1/checkouts/{$checkout->id}/address", [
            'email' => 'jane@example.com',
            'shipping_address' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'address1' => '123 Main St',
                'city' => 'Berlin',
                'province' => 'Berlin',
                'province_code' => 'BE',
                'country' => 'Germany',
                'country_code' => 'DE',
                'postal_code' => '10115',
            ],
            'use_shipping_as_billing' => true,
        ]);

    $response->assertOk()
        ->assertJsonPath('status', 'addressed');
});

it('selects a shipping method', function () {
    $checkout = Checkout::factory()->addressed()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'shipping_address_json' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country' => 'DE',
            'postal_code' => '10115',
        ],
    ]);

    $response = $this->withHeaders(['Host' => 'shop.test'])
        ->putJson("/api/storefront/v1/checkouts/{$checkout->id}/shipping-method", [
            'shipping_method_id' => $this->rate->id,
        ]);

    $response->assertOk()
        ->assertJsonPath('status', 'shipping_selected');
});

it('applies a discount code', function () {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'WELCOME10',
        'value_amount' => 10,
    ]);

    $checkout = Checkout::factory()->shippingSelected()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'shipping_method_id' => $this->rate->id,
    ]);

    $response = $this->withHeaders(['Host' => 'shop.test'])
        ->postJson("/api/storefront/v1/checkouts/{$checkout->id}/apply-discount", [
            'code' => 'WELCOME10',
        ]);

    $response->assertOk()
        ->assertJsonPath('discount_code', 'WELCOME10');
});

it('retrieves checkout with totals', function () {
    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => CheckoutStatus::Started,
        'totals_json' => [
            'subtotal' => 5000,
            'discount' => 0,
            'shipping' => 0,
            'tax_total' => 0,
            'total' => 5000,
            'currency' => 'EUR',
        ],
    ]);

    $response = $this->withHeaders(['Host' => 'shop.test'])
        ->getJson("/api/storefront/v1/checkouts/{$checkout->id}");

    $response->assertOk()
        ->assertJsonPath('totals.subtotal', 5000)
        ->assertJsonPath('totals.total', 5000);
});

it('selects a payment method', function () {
    $checkout = Checkout::factory()->shippingSelected()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'shipping_method_id' => $this->rate->id,
    ]);

    $response = $this->withHeaders(['Host' => 'shop.test'])
        ->putJson("/api/storefront/v1/checkouts/{$checkout->id}/payment-method", [
            'payment_method' => 'credit_card',
        ]);

    $response->assertOk()
        ->assertJsonPath('status', 'payment_selected')
        ->assertJsonPath('payment_method', 'credit_card');
});

it('completes checkout with credit card payment', function () {
    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => CheckoutStatus::PaymentSelected,
        'payment_method' => 'credit_card',
        'email' => 'customer@example.com',
        'shipping_address_json' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
        'billing_address_json' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
        'totals_json' => [
            'subtotal' => 5000,
            'discount' => 0,
            'shipping' => 500,
            'tax_total' => 0,
            'total' => 5500,
            'currency' => 'EUR',
        ],
    ]);

    $response = $this->withHeaders(['Host' => 'shop.test'])
        ->postJson("/api/storefront/v1/checkouts/{$checkout->id}/pay", [
            'payment_method' => 'credit_card',
            'card_number' => '4242424242424242',
            'card_expiry' => '12/28',
            'card_cvc' => '123',
            'card_holder' => 'Jane Doe',
        ]);

    $response->assertOk()
        ->assertJsonPath('status', 'completed')
        ->assertJsonStructure(['order' => ['id', 'order_number', 'status']]);
});

it('rejects payment with declined card', function () {
    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => CheckoutStatus::PaymentSelected,
        'payment_method' => 'credit_card',
        'email' => 'customer@example.com',
        'totals_json' => [
            'subtotal' => 5000,
            'discount' => 0,
            'shipping' => 0,
            'tax_total' => 0,
            'total' => 5000,
            'currency' => 'EUR',
        ],
    ]);

    $response = $this->withHeaders(['Host' => 'shop.test'])
        ->postJson("/api/storefront/v1/checkouts/{$checkout->id}/pay", [
            'payment_method' => 'credit_card',
            'card_number' => '4000000000000002',
            'card_expiry' => '12/28',
            'card_cvc' => '123',
            'card_holder' => 'Jane Doe',
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('error_code', 'card_declined');
});

it('validates required address fields', function () {
    $checkout = Checkout::factory()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    $response = $this->withHeaders(['Host' => 'shop.test'])
        ->putJson("/api/storefront/v1/checkouts/{$checkout->id}/address", [
            'email' => 'jane@example.com',
            'shipping_address' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                // missing city
                'address1' => '123 Main St',
                'country' => 'Germany',
                'country_code' => 'DE',
                'postal_code' => '10115',
            ],
        ]);

    $response->assertStatus(422);
});
