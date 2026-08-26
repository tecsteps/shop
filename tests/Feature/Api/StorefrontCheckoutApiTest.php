<?php

use App\Models\Cart;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\ProductService;

it('completes checkout with credit card via API', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];
    $product = app(ProductService::class)->create($store, ['title' => 'Widget', 'price_amount' => 2500, 'quantity_on_hand' => 10]);
    app(ProductService::class)->transitionStatus($product, \App\Enums\ProductStatus::Active);
    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'type' => 'flat', 'config_json' => ['amount' => 499]]);

    $cartResponse = $this->postJson('/api/storefront/v1/carts')->assertStatus(201);
    $cartId = $cartResponse->json('id');

    $this->postJson('/api/storefront/v1/carts/'.$cartId.'/lines', [
        'variant_id' => $product->variants()->first()->id,
        'quantity' => 2,
    ])->assertStatus(201);

    $checkoutResponse = $this->postJson('/api/storefront/v1/checkouts', [
        'cart_id' => $cartId,
        'email' => 'customer@example.com',
    ])->assertStatus(201);
    $checkoutId = $checkoutResponse->json('id');

    $this->putJson('/api/storefront/v1/checkouts/'.$checkoutId.'/address', [
        'email' => 'customer@example.com',
        'shipping_address' => ['first_name' => 'Jane', 'last_name' => 'Doe', 'address1' => 'Main 1', 'city' => 'Berlin', 'country' => 'Germany', 'country_code' => 'DE', 'postal_code' => '10115'],
    ])->assertStatus(200);

    $this->putJson('/api/storefront/v1/checkouts/'.$checkoutId.'/shipping-method', ['shipping_method_id' => $rate->id])
        ->assertStatus(200);

    $this->putJson('/api/storefront/v1/checkouts/'.$checkoutId.'/payment-method', ['payment_method' => 'credit_card'])
        ->assertStatus(200);

    $this->postJson('/api/storefront/v1/checkouts/'.$checkoutId.'/pay', [
        'payment_method' => 'credit_card',
        'card_number' => '4242424242424242',
        'card_expiry' => '12/28',
        'card_cvc' => '123',
        'card_holder' => 'Jane Doe',
    ])->assertStatus(200)
        ->assertJsonPath('status', 'completed')
        ->assertJsonPath('order.financial_status', 'paid');
});

it('rejects payment with declined card', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];
    $product = app(ProductService::class)->create($store, ['title' => 'Widget', 'price_amount' => 2500, 'quantity_on_hand' => 10]);
    app(ProductService::class)->transitionStatus($product, \App\Enums\ProductStatus::Active);
    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'type' => 'flat', 'config_json' => ['amount' => 499]]);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    app(\App\Services\CartService::class)->addLine($cart, $product->variants()->first()->id, 1);
    $checkout = app(\App\Services\CheckoutService::class)->create($cart, 'x@example.com');
    app(\App\Services\CheckoutService::class)->setAddress($checkout, [
        'email' => 'x@example.com',
        'shipping_address' => ['first_name' => 'Jane', 'last_name' => 'Doe', 'address1' => 'Main 1', 'city' => 'Berlin', 'country' => 'Germany', 'country_code' => 'DE', 'postal_code' => '10115'],
    ]);
    app(\App\Services\CheckoutService::class)->setShippingMethod($checkout, $rate->id);
    app(\App\Services\CheckoutService::class)->selectPaymentMethod($checkout, 'credit_card');

    $this->postJson('/api/storefront/v1/checkouts/'.$checkout->id.'/pay', [
        'payment_method' => 'credit_card',
        'card_number' => '4000000000000002',
        'card_expiry' => '12/28',
        'card_cvc' => '123',
        'card_holder' => 'Jane Doe',
    ])->assertStatus(422)
        ->assertJsonPath('error_code', 'card_declined');
});
