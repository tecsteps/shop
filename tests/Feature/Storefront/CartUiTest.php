<?php

use App\Livewire\Storefront\Cart\Show as CartPage;
use App\Livewire\Storefront\CartDrawer;
use App\Livewire\Storefront\Products\Show as ProductPage;
use App\Models\Discount;
use App\Services\CartService;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
});

it('adds a product to the cart from the product page', function () {
    $variant = createPurchasableVariant($this->store, 2500);

    Livewire::test(ProductPage::class, ['handle' => $variant->product->handle])
        ->set('quantity', 2)
        ->call('addToCart')
        ->assertDispatched('cart-updated')
        ->assertSet('addedToCart', true);

    $this->assertDatabaseHas('cart_lines', [
        'variant_id' => $variant->getKey(),
        'quantity' => 2,
        'unit_price_amount' => 2500,
    ]);
});

it('shows an inventory error when adding more than available stock', function () {
    $variant = createPurchasableVariant($this->store, 2500, 1);

    Livewire::test(ProductPage::class, ['handle' => $variant->product->handle])
        ->set('quantity', 5)
        ->call('addToCart')
        ->assertHasErrors('quantity')
        ->assertNotDispatched('cart-updated');
});

it('renders cart lines in the drawer and opens on cart-updated', function () {
    $variant = createPurchasableVariant($this->store, 2500);
    $cart = app(CartService::class)->getOrCreateForSession($this->store);
    app(CartService::class)->addLine($cart, $variant->getKey(), 2);

    Livewire::test(CartDrawer::class)
        ->assertSet('open', false)
        ->dispatch('cart-updated')
        ->assertSet('open', true)
        ->assertSee($variant->product->title)
        ->assertSee('50.00 EUR');
});

it('updates a line quantity from the drawer', function () {
    $variant = createPurchasableVariant($this->store, 2500);
    $cart = app(CartService::class)->getOrCreateForSession($this->store);
    $line = app(CartService::class)->addLine($cart, $variant->getKey(), 1);

    Livewire::test(CartDrawer::class)
        ->call('updateQuantity', $line->getKey(), 3)
        ->assertDispatched('cart-updated');

    expect($line->refresh()->quantity)->toBe(3);
    expect($line->line_subtotal_amount)->toBe(7500);
});

it('removes a line from the drawer', function () {
    $variant = createPurchasableVariant($this->store, 2500);
    $cart = app(CartService::class)->getOrCreateForSession($this->store);
    $line = app(CartService::class)->addLine($cart, $variant->getKey(), 1);

    Livewire::test(CartDrawer::class)
        ->call('removeLine', $line->getKey())
        ->assertDispatched('cart-updated');

    $this->assertDatabaseMissing('cart_lines', ['id' => $line->getKey()]);
});

it('renders the full cart page with line items and totals', function () {
    $variant = createPurchasableVariant($this->store, 2500);
    $cart = app(CartService::class)->getOrCreateForSession($this->store);
    app(CartService::class)->addLine($cart, $variant->getKey(), 2);

    Livewire::test(CartPage::class)
        ->assertSee($variant->product->title)
        ->assertSee('50.00 EUR');
});

it('shows the empty state on the cart page without a cart', function () {
    Livewire::test(CartPage::class)
        ->assertSee('Your cart is empty');
});

it('applies a valid discount code on the cart page', function () {
    Discount::factory()->for($this->store)->create(['code' => 'SAVE10', 'value_amount' => 10]);

    $variant = createPurchasableVariant($this->store, 10000);
    $cart = app(CartService::class)->getOrCreateForSession($this->store);
    app(CartService::class)->addLine($cart, $variant->getKey(), 1);

    Livewire::test(CartPage::class)
        ->set('discountCode', 'SAVE10')
        ->call('applyDiscount')
        ->assertSet('discountError', null)
        ->assertSee('SAVE10')
        ->assertSee('-10.00 EUR');

    expect(session('cart_discount_code'))->toBe('SAVE10');
});

it('shows an error for an invalid discount code', function () {
    $variant = createPurchasableVariant($this->store, 10000);
    $cart = app(CartService::class)->getOrCreateForSession($this->store);
    app(CartService::class)->addLine($cart, $variant->getKey(), 1);

    Livewire::test(CartPage::class)
        ->set('discountCode', 'NOPE')
        ->call('applyDiscount')
        ->assertSet('discountError', 'Invalid discount code.');
});

it('serves the cart page over http with the storefront layout', function () {
    $variant = createPurchasableVariant($this->store, 2500);
    $cart = app(CartService::class)->getOrCreateForSession($this->store);
    app(CartService::class)->addLine($cart, $variant->getKey(), 1);

    $this->withSession([CartService::SESSION_KEY => $cart->getKey()])
        ->get('http://'.$this->context['domain']->hostname.'/cart')
        ->assertOk()
        ->assertSee('Your Cart')
        ->assertSee($variant->product->title);
});

it('redirects to the cart page when checking out with an empty cart', function () {
    $this->get('http://'.$this->context['domain']->hostname.'/checkout')
        ->assertRedirect();
});

it('serves the checkout page over http for a filled cart', function () {
    $variant = createPurchasableVariant($this->store, 2500);
    $cart = app(CartService::class)->getOrCreateForSession($this->store);
    app(CartService::class)->addLine($cart, $variant->getKey(), 2);

    $this->withSession([CartService::SESSION_KEY => $cart->getKey()])
        ->get('http://'.$this->context['domain']->hostname.'/checkout')
        ->assertOk()
        ->assertSee('Checkout')
        ->assertSee('Contact information')
        ->assertSee('Order Summary');
});

it('removes a line from the cart page when quantity is set to zero', function () {
    $variant = createPurchasableVariant($this->store, 2500);
    $cart = app(CartService::class)->getOrCreateForSession($this->store);
    $line = app(CartService::class)->addLine($cart, $variant->getKey(), 1);

    Livewire::test(CartPage::class)
        ->call('updateQuantity', $line->getKey(), 0);

    $this->assertDatabaseMissing('cart_lines', ['id' => $line->getKey()]);
});
