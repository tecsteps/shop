<?php

use App\Models\Cart;
use App\Models\ShippingRate;
use App\Models\ShippingZone;

beforeEach(function () {
    $this->context = createStoreContext(['hostname' => 'shop.test']);
    $this->store = $this->context['store'];
    bindCurrentStore($this->store);

    $zone = ShippingZone::factory()->for($this->store)->countries(['DE'])->create();
    $this->rate = ShippingRate::factory()->for($zone, 'zone')->flat(500)->create(['name' => 'Standard']);

    $variant = makeSellableVariant(['price' => 2500, 'requires_shipping' => true]);
    $this->cart = Cart::factory()->create(['store_id' => $this->store->id, 'currency' => 'EUR']);
    $this->cart->lines()->create([
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 5000,
        'line_discount_amount' => 0,
        'line_total_amount' => 5000,
    ]);
});

function checkoutApi(string $path = ''): string
{
    return storefrontUrl('shop.test', '/api/storefront/v1/checkouts'.$path);
}

/**
 * @return array<string, mixed>
 */
function germanShippingPayload(): array
{
    return [
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country' => 'DE',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
        'use_shipping_as_billing' => true,
    ];
}

/**
 * Create a checkout via the API and return its id.
 */
function createApiCheckout(int $cartId): int
{
    $response = test()->postJson(checkoutApi(), [
        'cart_id' => $cartId,
        'email' => 'jane@example.com',
    ])->assertCreated();

    return $response->json('id');
}

it('creates a checkout from a cart', function () {
    $this->postJson(checkoutApi(), [
        'cart_id' => $this->cart->id,
        'email' => 'jane@example.com',
    ])
        ->assertCreated()
        ->assertJsonPath('status', 'started');
});

it('sets checkout address', function () {
    $id = createApiCheckout($this->cart->id);

    $this->putJson(checkoutApi("/{$id}/address"), germanShippingPayload())
        ->assertSuccessful()
        ->assertJsonPath('status', 'addressed');
});

it('selects a shipping method', function () {
    $id = createApiCheckout($this->cart->id);
    $this->putJson(checkoutApi("/{$id}/address"), germanShippingPayload())->assertSuccessful();

    $this->putJson(checkoutApi("/{$id}/shipping-method"), ['shipping_method_id' => $this->rate->id])
        ->assertSuccessful()
        ->assertJsonPath('status', 'shipping_selected')
        ->assertJsonCount(1, 'available_shipping_methods');
});

it('retrieves checkout with totals', function () {
    $id = createApiCheckout($this->cart->id);

    $this->getJson(checkoutApi("/{$id}"))
        ->assertSuccessful()
        ->assertJsonStructure(['totals_json', 'totals' => ['subtotal', 'total']]);
});

it('selects a payment method', function () {
    $id = createApiCheckout($this->cart->id);
    $this->putJson(checkoutApi("/{$id}/address"), germanShippingPayload())->assertSuccessful();
    $this->putJson(checkoutApi("/{$id}/shipping-method"), ['shipping_method_id' => $this->rate->id])->assertSuccessful();

    $this->putJson(checkoutApi("/{$id}/payment-method"), ['payment_method' => 'credit_card'])
        ->assertSuccessful()
        ->assertJsonPath('status', 'payment_selected')
        ->assertJsonPath('payment_method', 'credit_card');
});

it('completes checkout with credit card payment', function () {
    $id = createApiCheckout($this->cart->id);
    $this->putJson(checkoutApi("/{$id}/address"), germanShippingPayload())->assertSuccessful();
    $this->putJson(checkoutApi("/{$id}/shipping-method"), ['shipping_method_id' => $this->rate->id])->assertSuccessful();
    $this->putJson(checkoutApi("/{$id}/payment-method"), ['payment_method' => 'credit_card'])->assertSuccessful();

    $this->postJson(checkoutApi("/{$id}/pay"), [
        'payment_method' => 'credit_card',
        'card_number' => '4242424242424242',
        'card_expiry' => '12/28',
        'card_cvc' => '123',
        'card_holder' => 'Jane Doe',
    ])
        ->assertSuccessful()
        ->assertJsonPath('status', 'completed')
        ->assertJsonPath('order.status', 'paid');

    $this->assertDatabaseHas('orders', ['checkout_id' => $id]);
});

it('rejects payment with declined card', function () {
    $id = createApiCheckout($this->cart->id);
    $this->putJson(checkoutApi("/{$id}/address"), germanShippingPayload())->assertSuccessful();
    $this->putJson(checkoutApi("/{$id}/shipping-method"), ['shipping_method_id' => $this->rate->id])->assertSuccessful();
    $this->putJson(checkoutApi("/{$id}/payment-method"), ['payment_method' => 'credit_card'])->assertSuccessful();

    $this->postJson(checkoutApi("/{$id}/pay"), [
        'payment_method' => 'credit_card',
        'card_number' => '4000000000000002',
        'card_expiry' => '12/28',
        'card_cvc' => '123',
        'card_holder' => 'Jane Doe',
    ])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'card_declined');
});

it('validates required address fields', function () {
    $id = createApiCheckout($this->cart->id);

    $payload = germanShippingPayload();
    unset($payload['shipping_address']['city']);

    $this->putJson(checkoutApi("/{$id}/address"), $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors('shipping_address.city');
});

it('applies a discount code', function () {
    $discount = App\Models\Discount::factory()->for($this->store)->create([
        'type' => 'code',
        'code' => 'SAVE10',
        'value_type' => 'percent',
        'value_amount' => 10,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
    ]);

    $id = createApiCheckout($this->cart->id);

    $this->postJson(checkoutApi("/{$id}/apply-discount"), ['code' => 'SAVE10'])
        ->assertSuccessful()
        ->assertJsonPath('discount_code', 'SAVE10');
});
