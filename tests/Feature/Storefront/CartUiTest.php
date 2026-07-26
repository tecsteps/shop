<?php

use App\Enums\CheckoutStatus;
use App\Livewire\Storefront\Cart\Show as CartPage;
use App\Livewire\Storefront\CartDrawer;
use App\Livewire\Storefront\Checkout\Show as CheckoutPage;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\CartService;
use Livewire\Livewire;

/**
 * Store with a purchasable variant, DE zone + flat rate 499.
 *
 * @return array{0: Store, 1: ProductVariant, 2: ShippingRate}
 */
function cartUiSetup(): array
{
    $store = test()->createStore();
    test()->bindStore($store);

    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->withInventory(50)->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
    ]);

    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->flat(499)->create(['zone_id' => $zone->id]);

    return [$store, $variant, $rate];
}

/**
 * A session-bound cart with one line of the variant.
 *
 * @return array{0: Cart, 1: App\Models\CartLine}
 */
function sessionCartWithLine(Store $store, ProductVariant $variant): array
{
    $service = app(CartService::class);
    $cart = $service->create($store);
    $line = $service->addLine($cart, $variant->id, 1);

    session(['cart_id' => $cart->id]);

    return [$cart->refresh(), $line];
}

test('cart drawer adds a line from the add-to-cart event', function () {
    [$store, $variant] = cartUiSetup();

    Livewire::test(CartDrawer::class)
        ->dispatch('add-to-cart', variantId: $variant->id, quantity: 2)
        ->assertDispatched('cart-updated', count: 2)
        ->assertDispatched('cart-drawer-open');

    $cart = Cart::query()->where('store_id', $store->id)->sole();

    expect($cart->lines)->toHaveCount(1)
        ->and($cart->lines->first()->quantity)->toBe(2);
});

test('cart page updates line quantity', function () {
    [$store, $variant] = cartUiSetup();
    [, $line] = sessionCartWithLine($store, $variant);

    Livewire::test(CartPage::class)
        ->call('incrementLine', $line->id)
        ->assertDispatched('cart-updated', count: 2);

    expect($line->refresh()->quantity)->toBe(2);

    Livewire::test(CartPage::class)
        ->call('decrementLine', $line->id)
        ->call('decrementLine', $line->id);

    expect(Cart::find(session('cart_id'))->lines)->toHaveCount(0);
});

test('cart page shows a discount error for an unknown code', function () {
    [$store, $variant] = cartUiSetup();
    sessionCartWithLine($store, $variant);

    Livewire::test(CartPage::class)
        ->set('discountCode', 'NOPE')
        ->call('applyDiscount')
        ->assertSet('discountError', 'This discount code is invalid.');
});

test('checkout new redirects to the cart when it is empty', function () {
    cartUiSetup();

    Livewire::test(CheckoutPage::class, ['checkoutId' => 'new'])
        ->assertRedirect(route('storefront.cart.show'));
});

test('checkout walks from address step to shipping and payment', function () {
    [$store, $variant, $rate] = cartUiSetup();
    sessionCartWithLine($store, $variant);

    Livewire::test(CheckoutPage::class, ['checkoutId' => 'new'])
        ->set('email', 'jane@example.com')
        ->set('address.first_name', 'Jane')
        ->set('address.last_name', 'Doe')
        ->set('address.address1', '123 Main St')
        ->set('address.city', 'Berlin')
        ->set('address.country_code', 'DE')
        ->set('address.postal_code', '10115')
        ->call('submitAddress');

    $checkout = Checkout::query()->where('store_id', $store->id)->sole();

    expect($checkout->status)->toBe(CheckoutStatus::Addressed)
        ->and($checkout->email)->toBe('jane@example.com')
        ->and($checkout->shipping_address_json['city'])->toBe('Berlin');

    Livewire::test(CheckoutPage::class, ['checkoutId' => $checkout->id])
        ->assertSet('step', 2)
        ->call('selectShipping', $rate->id)
        ->assertSet('step', 3);

    expect($checkout->refresh()->status)->toBe(CheckoutStatus::ShippingSelected)
        ->and($checkout->totals_json['shipping'])->toBe(499);

    Livewire::test(CheckoutPage::class, ['checkoutId' => $checkout->id])
        ->assertSet('step', 3)
        ->set('paymentMethod', 'paypal')
        ->call('selectPayment')
        ->assertSet('paymentSelected', true);

    expect($checkout->refresh()->status)->toBe(CheckoutStatus::PaymentSelected)
        ->and($variant->inventoryItem->refresh()->quantity_reserved)->toBe(1);
});

test('checkout validates the address form', function () {
    [$store, $variant] = cartUiSetup();
    sessionCartWithLine($store, $variant);

    Livewire::test(CheckoutPage::class, ['checkoutId' => 'new'])
        ->set('email', 'not-an-email')
        ->call('submitAddress')
        ->assertHasErrors(['email', 'address.first_name', 'address.address1', 'address.city', 'address.country_code', 'address.postal_code']);

    expect(Checkout::query()->count())->toBe(0);
});

test('expired checkout renders the expired state', function () {
    [$store, $variant] = cartUiSetup();
    [$cart] = sessionCartWithLine($store, $variant);

    $checkout = app(\App\Services\CheckoutService::class)->createFromCart($cart, 'jane@example.com');
    $checkout->update(['expires_at' => now()->subHour()]);

    Livewire::test(CheckoutPage::class, ['checkoutId' => $checkout->id])
        ->assertSet('expired', true)
        ->assertSee('This checkout has expired');
});

test('cart page renders over http with the drawer in the layout', function () {
    [$store, $variant] = cartUiSetup();
    [$cart] = sessionCartWithLine($store, $variant);

    $this->withSession(['cart_id' => $cart->id])
        ->get('http://'.$store->handle.'.test/cart')
        ->assertOk()
        ->assertSee('Your Cart')
        ->assertSee('25.00 USD');
});

test('checkout new page renders the address step over http', function () {
    [$store, $variant] = cartUiSetup();
    [$cart] = sessionCartWithLine($store, $variant);

    $this->withSession(['cart_id' => $cart->id])
        ->get('http://'.$store->handle.'.test/checkout/new')
        ->assertOk()
        ->assertSee('Contact & shipping address');
});

test('bank transfer pay through the checkout page creates a pending order', function () {
    [$store, $variant, $rate] = cartUiSetup();
    [$cart] = sessionCartWithLine($store, $variant);

    $checkoutService = app(\App\Services\CheckoutService::class);
    $checkout = $checkoutService->createFromCart($cart, 'jane@example.com');
    $checkoutService->setAddress($checkout, [
        'email' => 'jane@example.com',
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
    ]);
    $checkoutService->setShippingMethod($checkout, $rate->id);
    $checkoutService->selectPaymentMethod($checkout, 'bank_transfer');

    Livewire::test(CheckoutPage::class, ['checkoutId' => $checkout->id])
        ->call('pay')
        ->assertRedirect(route('storefront.checkout.confirmation', ['checkoutId' => $checkout->id]));

    $order = \App\Models\Order::query()->where('store_id', $store->id)->sole();

    expect($order->financial_status->value)->toBe('pending')
        ->and($order->payment_method->value)->toBe('bank_transfer')
        ->and($checkout->refresh()->status)->toBe(CheckoutStatus::Completed);
});

test('paypal pay through the checkout page creates a paid order', function () {
    [$store, $variant, $rate] = cartUiSetup();
    [$cart] = sessionCartWithLine($store, $variant);

    $checkoutService = app(\App\Services\CheckoutService::class);
    $checkout = $checkoutService->createFromCart($cart, 'jane@example.com');
    $checkoutService->setAddress($checkout, [
        'email' => 'jane@example.com',
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
    ]);
    $checkoutService->setShippingMethod($checkout, $rate->id);
    $checkoutService->selectPaymentMethod($checkout, 'paypal');

    Livewire::test(CheckoutPage::class, ['checkoutId' => $checkout->id])
        ->call('pay')
        ->assertRedirect(route('storefront.checkout.confirmation', ['checkoutId' => $checkout->id]));

    $order = \App\Models\Order::query()->where('store_id', $store->id)->sole();

    expect($order->financial_status->value)->toBe('paid')
        ->and($order->status->value)->toBe('paid');
});
