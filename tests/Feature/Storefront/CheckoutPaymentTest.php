<?php

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Livewire\Storefront\Account\Addresses\Index as AddressesIndex;
use App\Livewire\Storefront\Checkout\Show as CheckoutShow;
use App\Livewire\Storefront\Products\Show as ProductShow;
use App\Models\Customer;
use App\Services\CartService;
use App\Services\ProductService;
use Livewire\Livewire;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->productService = app(ProductService::class);
});

it('defaults checkout country to DE', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'Test Product',
        'price_amount' => 1000,
    ]);
    $product->update(['status' => ProductStatus::Active]);

    $variant = $product->variants->first();
    $variant->inventoryItem->update([
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    $cartService = app(CartService::class);
    $cart = $cartService->getOrCreateForSession($this->ctx['store']);
    $cartService->addLine($cart, $variant->id, 1);

    Livewire::withHeaders(['Host' => 'shop.test'])
        ->test(CheckoutShow::class)
        ->assertSet('country', 'DE');
});

it('passes card number to payment provider and shows declined error', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'Test Product',
        'price_amount' => 1000,
    ]);
    $product->update(['status' => ProductStatus::Active]);

    $variant = $product->variants->first();
    $variant->inventoryItem->update([
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    $cartService = app(CartService::class);
    $cart = $cartService->getOrCreateForSession($this->ctx['store']);
    $cartService->addLine($cart, $variant->id, 1);

    // Create a shipping rate
    $zone = \App\Models\ShippingZone::factory()->for($this->ctx['store'])->create();
    $rate = \App\Models\ShippingRate::factory()->for($zone, 'zone')->create(['amount' => 500]);

    Livewire::withHeaders(['Host' => 'shop.test'])
        ->test(CheckoutShow::class)
        ->set('email', 'test@example.com')
        ->set('firstName', 'John')
        ->set('lastName', 'Doe')
        ->set('address1', '123 Main St')
        ->set('city', 'Berlin')
        ->set('country', 'DE')
        ->set('zip', '10115')
        ->call('submitAddress')
        ->set('selectedShippingRateId', $rate->id)
        ->call('submitShipping')
        ->set('paymentMethod', 'credit_card')
        ->set('cardNumber', '4000000000000002')
        ->call('submitPayment')
        ->assertSet('errorMessage', 'Your card was declined.');
});

it('shows friendly error when adding more items than available stock', function () {
    $product = $this->productService->create($this->ctx['store'], [
        'title' => 'Limited Product',
        'price_amount' => 1000,
    ]);
    $product->update(['status' => ProductStatus::Active]);

    $variant = $product->variants->first();
    $variant->inventoryItem->update([
        'quantity_on_hand' => 2,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    Livewire::withHeaders(['Host' => 'shop.test'])
        ->test(ProductShow::class, ['handle' => $product->handle])
        ->set('quantity', 5)
        ->call('addToCart')
        ->assertNotDispatched('cart-updated');
});

it('defaults address form country to DE', function () {
    $customer = Customer::factory()->for($this->ctx['store'])->create();
    actingAsCustomer($customer);

    Livewire::withHeaders(['Host' => 'shop.test'])
        ->test(AddressesIndex::class)
        ->assertSet('country', 'DE');
});
