<?php

use App\Enums\CartStatus;
use App\Livewire\Storefront\Checkout\Show as CheckoutShow;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\Store;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    $this->seed();
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    app()->instance('current_store', $this->store);
});

test('checkout advances through address shipping and payment steps', function (): void {
    $product = Product::query()->where('handle', 'linen-shirt')->firstOrFail();
    $variant = $product->variants()->firstOrFail();
    $cart = app(CartService::class)->create($this->store);
    app(CartService::class)->addLine($cart, $variant->id, 1);
    $checkout = app(CheckoutService::class)->createFromCart($cart->refresh(), 'buyer@example.com');
    session([CartService::SESSION_KEY => $cart->id]);
    $rate = ShippingRate::query()
        ->whereHas('zone', fn ($query) => $query->where('store_id', $this->store->id))
        ->firstOrFail();

    Livewire::test(CheckoutShow::class, ['checkoutId' => $checkout->id])
        ->assertSet('activeStep', 'address')
        ->assertSee('1. Contact and address')
        ->assertSee('Complete address first')
        ->assertDontSee('Card number')
        ->set('shippingAddress.first_name', 'Jane')
        ->set('shippingAddress.last_name', 'Doe')
        ->set('shippingAddress.address1', 'Musterstrasse 1')
        ->set('shippingAddress.city', 'Berlin')
        ->set('shippingAddress.postal_code', '10115')
        ->set('shippingAddress.country_code', 'DE')
        ->call('saveAddress')
        ->assertHasNoErrors()
        ->assertSet('activeStep', 'shipping')
        ->assertSee('Continue to payment')
        ->set('selectedShippingRateId', $rate->id)
        ->call('selectShipping')
        ->assertHasNoErrors()
        ->assertSet('activeStep', 'payment')
        ->assertSee('Card number')
        ->call('showStep', 'address')
        ->assertSet('activeStep', 'address')
        ->call('showStep', 'payment')
        ->call('pay')
        ->assertHasNoErrors()
        ->assertRedirect(route('storefront.checkout.confirmation', $checkout));

    expect($cart->refresh()->status)->toBe(CartStatus::Converted)
        ->and(session(CartService::SESSION_KEY))->toBeNull();
});
