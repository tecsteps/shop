<?php

use App\Livewire\Storefront\Checkout\Show as CheckoutShow;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create(['default_currency' => 'EUR']);
    app()->instance('current_store', $this->store);
    $product = Product::factory()->for($this->store)->create();
    $variant = ProductVariant::factory()->digital()->for($product)->create(['price_amount' => 2500]);
    $variant->inventoryItem->update(['quantity_on_hand' => 10]);
    $cart = Cart::factory()->for($this->store)->create();
    $cart->lines()->create(['variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 2500, 'line_subtotal_amount' => 2500, 'line_total_amount' => 2500]);
    $this->checkout = Checkout::factory()->for($this->store)->for($cart)->create();
});

it('renders checkout and validates required address fields', function () {
    Livewire::test(CheckoutShow::class, ['checkoutId' => $this->checkout->id])
        ->assertSee('Contact and shipping address')
        ->call('saveAddress')
        ->assertHasErrors(['email', 'shipping.first_name', 'shipping.last_name', 'shipping.address1', 'shipping.city', 'shipping.postal_code']);
});

it('progresses a digital checkout through address and shipping', function () {
    Livewire::test(CheckoutShow::class, ['checkoutId' => $this->checkout->id])
        ->set('email', 'buyer@example.com')->set('shipping.first_name', 'Buyer')->set('shipping.last_name', 'Person')
        ->set('shipping.address1', 'Main Street 1')->set('shipping.city', 'Berlin')->set('shipping.country', 'DE')->set('shipping.postal_code', '10115')
        ->call('saveAddress')->assertSee('Shipping method')
        ->call('selectShipping')->assertSee('Payment');
});
