<?php

use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\CartDrawer;
use App\Livewire\Storefront\Checkout\Confirmation;
use App\Livewire\Storefront\Checkout\Show as CheckoutShow;
use App\Models\Order;
use App\Models\OrderLine;
use App\Services\CartService;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];

    // The storefront cart/checkout routes the components redirect to
    // (storefront.cart / storefront.checkout / storefront.checkout.confirmation)
    // are defined for real in routes/storefront.php, so no runtime stubs are
    // needed here.
});

it('cart drawer adds a variant and emits cart-updated', function () {
    $variant = makeSellableVariant(['price' => 2500]);

    Livewire::test(CartDrawer::class)
        ->call('addToCart', $variant->id, 2)
        ->assertSet('open', true)
        ->assertDispatched('cart-updated', itemCount: 2)
        ->assertSee('25.00 USD'); // 2500 cents per unit

    $cart = app(CartService::class)->getOrCreateForSession($this->store);
    expect($cart->lines()->sum('quantity'))->toBe(2);
});

it('cart drawer opens on the open-cart-drawer event', function () {
    Livewire::test(CartDrawer::class)
        ->call('openDrawer')
        ->assertSet('open', true);
});

it('cart drawer surfaces an inventory error', function () {
    $variant = makeSellableVariant(['on_hand' => 1, 'policy' => 'deny']);

    Livewire::test(CartDrawer::class)
        ->call('addToCart', $variant->id, 5)
        ->assertDispatched('cart-error');
});

it('cart page updates a line quantity', function () {
    $variant = makeSellableVariant(['price' => 1000]);
    $cart = app(CartService::class)->getOrCreateForSession($this->store);
    $line = app(CartService::class)->addLine($cart, $variant->id, 1);

    Livewire::test(CartShow::class)
        ->call('updateQuantity', $line->id, 4)
        ->assertDispatched('cart-updated');

    expect($cart->lines()->first()->quantity)->toBe(4);
});

it('checkout component drives a full purchase', function () {
    $variant = makeSellableVariant(['price' => 5000, 'requires_shipping' => false]);
    $cart = app(CartService::class)->getOrCreateForSession($this->store);
    app(CartService::class)->addLine($cart, $variant->id, 1);

    Livewire::test(CheckoutShow::class)
        ->set('email', 'buyer@example.com')
        ->set('address.first_name', 'Anna')
        ->set('address.last_name', 'Schmidt')
        ->set('address.address1', 'Hauptstrasse 1')
        ->set('address.city', 'Berlin')
        ->set('address.postal_code', '10115')
        ->set('address.country', 'DE')
        ->call('saveAddress')
        ->call('selectShipping')
        ->set('paymentMethod', 'credit_card')
        ->set('cardNumber', '4242424242424242')
        ->call('pay')
        ->assertHasNoErrors();

    expect(Order::query()->where('store_id', $this->store->id)->count())->toBe(1);
});

it('checkout component surfaces a payment decline', function () {
    $variant = makeSellableVariant(['price' => 5000, 'requires_shipping' => false]);
    $cart = app(CartService::class)->getOrCreateForSession($this->store);
    app(CartService::class)->addLine($cart, $variant->id, 1);

    Livewire::test(CheckoutShow::class)
        ->set('email', 'buyer@example.com')
        ->set('address.first_name', 'Anna')
        ->set('address.last_name', 'Schmidt')
        ->set('address.address1', 'Hauptstrasse 1')
        ->set('address.city', 'Berlin')
        ->set('address.postal_code', '10115')
        ->set('address.country', 'DE')
        ->call('saveAddress')
        ->call('selectShipping')
        ->set('paymentMethod', 'credit_card')
        ->set('cardNumber', '4000000000000002')
        ->call('pay')
        ->assertHasErrors('payment');

    expect(Order::query()->where('store_id', $this->store->id)->count())->toBe(0);
});

it('confirmation page shows the order and bank transfer details', function () {
    $order = Order::factory()->for($this->store)->bankTransfer()->create(['order_number' => '#1001', 'total_amount' => 5000]);
    OrderLine::factory()->for($order)->create(['store_id' => $this->store->id, 'title_snapshot' => 'Demo Item', 'quantity' => 1]);

    Livewire::test(Confirmation::class, ['orderId' => $order->id])
        ->assertSee('#1001')
        ->assertSee('Demo Item')
        ->assertSee('Mock Bank AG');
});
