<?php

use App\Enums\ShippingRateType;
use App\Enums\VariantStatus;
use App\Livewire\Storefront\Checkout\Index;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Livewire\Livewire;

function createCheckoutLivewireContext(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::factory()->for($store)->active()->create(['title' => 'Test Product']);
    $variant = ProductVariant::factory()->for($product)->create([
        'price_amount' => 5000,
        'status' => VariantStatus::Active,
        'weight_g' => 500,
    ]);

    InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
    ]);

    $cart = Cart::factory()->for($store)->create();
    $line = CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 5000,
        'line_subtotal_amount' => 10000,
        'line_total_amount' => 10000,
    ]);

    $zone = ShippingZone::factory()->for($store)->create([
        'countries_json' => ['DE'],
    ]);

    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
    ]);

    session(['cart_id' => $cart->id]);

    return array_merge($ctx, [
        'product' => $product,
        'variant' => $variant,
        'cart' => $cart,
        'line' => $line,
        'zone' => $zone,
        'rate' => $rate,
    ]);
}

it('redirects to cart when cart is empty', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $cart = Cart::factory()->for($store)->create();
    session(['cart_id' => $cart->id]);

    Livewire::test(Index::class)
        ->assertRedirect(route('storefront.cart'));
});

it('mounts with step 1 when cart has items', function () {
    createCheckoutLivewireContext();

    Livewire::test(Index::class)
        ->assertSet('step', 1)
        ->assertSee('Contact Information');
});

it('submits contact email and advances to step 2', function () {
    createCheckoutLivewireContext();

    Livewire::test(Index::class)
        ->set('email', 'customer@example.com')
        ->call('submitContact')
        ->assertSet('step', 2)
        ->assertSee('Shipping Address');
});

it('validates email on contact step', function () {
    createCheckoutLivewireContext();

    Livewire::test(Index::class)
        ->set('email', 'not-an-email')
        ->call('submitContact')
        ->assertHasErrors(['email'])
        ->assertSet('step', 1);
});

it('submits address and advances to step 3', function () {
    $ctx = createCheckoutLivewireContext();

    Livewire::test(Index::class)
        ->set('email', 'customer@example.com')
        ->call('submitContact')
        ->set('firstName', 'Max')
        ->set('lastName', 'Mustermann')
        ->set('address1', 'Musterstrasse 1')
        ->set('city', 'Berlin')
        ->set('postalCode', '10115')
        ->set('countryCode', 'DE')
        ->call('submitAddress')
        ->assertSet('step', 3)
        ->assertSee('Delivery Method');
});

it('validates required address fields', function () {
    createCheckoutLivewireContext();

    Livewire::test(Index::class)
        ->set('email', 'customer@example.com')
        ->call('submitContact')
        ->call('submitAddress')
        ->assertHasErrors(['firstName', 'lastName', 'address1', 'city', 'postalCode'])
        ->assertSet('step', 2);
});

it('submits shipping and advances to step 4', function () {
    $ctx = createCheckoutLivewireContext();
    $rate = $ctx['rate'];

    Livewire::test(Index::class)
        ->set('email', 'customer@example.com')
        ->call('submitContact')
        ->set('firstName', 'Max')
        ->set('lastName', 'Mustermann')
        ->set('address1', 'Musterstrasse 1')
        ->set('city', 'Berlin')
        ->set('postalCode', '10115')
        ->set('countryCode', 'DE')
        ->call('submitAddress')
        ->set('selectedShippingRateId', $rate->id)
        ->call('submitShipping')
        ->assertSet('step', 4)
        ->assertSee('Payment');
});

it('validates shipping rate selection', function () {
    createCheckoutLivewireContext();

    Livewire::test(Index::class)
        ->set('email', 'customer@example.com')
        ->call('submitContact')
        ->set('firstName', 'Max')
        ->set('lastName', 'Mustermann')
        ->set('address1', 'Musterstrasse 1')
        ->set('city', 'Berlin')
        ->set('postalCode', '10115')
        ->set('countryCode', 'DE')
        ->call('submitAddress')
        ->call('submitShipping')
        ->assertHasErrors(['selectedShippingRateId'])
        ->assertSet('step', 3);
});

it('allows navigating back to previous steps', function () {
    $ctx = createCheckoutLivewireContext();

    Livewire::test(Index::class)
        ->set('email', 'customer@example.com')
        ->call('submitContact')
        ->assertSet('step', 2)
        ->call('goToStep', 1)
        ->assertSet('step', 1);
});

it('prevents navigating forward via goToStep', function () {
    createCheckoutLivewireContext();

    Livewire::test(Index::class)
        ->assertSet('step', 1)
        ->call('goToStep', 3)
        ->assertSet('step', 1);
});

it('completes checkout with credit card payment', function () {
    $ctx = createCheckoutLivewireContext();
    $rate = $ctx['rate'];

    Livewire::test(Index::class)
        ->set('email', 'customer@example.com')
        ->call('submitContact')
        ->set('firstName', 'Max')
        ->set('lastName', 'Mustermann')
        ->set('address1', 'Musterstrasse 1')
        ->set('city', 'Berlin')
        ->set('postalCode', '10115')
        ->set('countryCode', 'DE')
        ->call('submitAddress')
        ->set('selectedShippingRateId', $rate->id)
        ->call('submitShipping')
        ->set('paymentMethod', 'credit_card')
        ->set('cardNumber', '4242424242424242')
        ->set('cardholderName', 'Max Mustermann')
        ->set('cardExpiry', '12/28')
        ->set('cardCvc', '123')
        ->call('submitPayment')
        ->assertRedirect();
});

it('validates credit card fields when payment method is credit card', function () {
    $ctx = createCheckoutLivewireContext();
    $rate = $ctx['rate'];

    Livewire::test(Index::class)
        ->set('email', 'customer@example.com')
        ->call('submitContact')
        ->set('firstName', 'Max')
        ->set('lastName', 'Mustermann')
        ->set('address1', 'Musterstrasse 1')
        ->set('city', 'Berlin')
        ->set('postalCode', '10115')
        ->set('countryCode', 'DE')
        ->call('submitAddress')
        ->set('selectedShippingRateId', $rate->id)
        ->call('submitShipping')
        ->set('paymentMethod', 'credit_card')
        ->call('submitPayment')
        ->assertHasErrors(['cardNumber', 'cardholderName', 'cardExpiry', 'cardCvc'])
        ->assertSet('step', 4);
});

it('stores discount code from session on checkout creation', function () {
    $ctx = createCheckoutLivewireContext();

    session(['discount_code' => 'SAVE10']);

    $component = Livewire::test(Index::class);
    $checkoutId = $component->get('checkoutId');

    $checkout = \App\Models\Checkout::withoutGlobalScopes()->find($checkoutId);
    expect($checkout->discount_code)->toBe('SAVE10');
});
