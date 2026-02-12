<?php

use App\Enums\CartStatus;
use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Store;

it('completes cart to checkout happy path with credit card', function () {
    $store = Store::factory()->create();
    $variant = createSellableVariant(
        $store,
        variantAttributes: [
            'requires_shipping' => false,
            'price_amount' => 2500,
        ],
        inventoryAttributes: ['quantity_on_hand' => 10],
    );

    $this->post('/cart/lines', [
        'variant_id' => $variant->id,
        'quantity' => 2,
    ])->assertRedirect(route('storefront.cart.show'));

    $cart = Cart::query()->where('store_id', $store->id)->firstOrFail();

    $this->post('/checkout')->assertRedirect();

    $checkout = Checkout::query()->where('cart_id', $cart->id)->latest('id')->firstOrFail();

    $this->post("/checkout/{$checkout->id}/submit", [
        'email' => 'buyer@gmail.com',
        'payment_method' => PaymentMethod::CreditCard->value,
        'billing_same_as_shipping' => true,
        'card' => [
            'number' => '4242424242424242',
            'name' => 'Jane Buyer',
            'expiry' => now()->addYear()->format('m/y'),
            'cvc' => '123',
        ],
    ])->assertRedirect(route('storefront.checkout.confirmation', ['checkoutId' => $checkout->id]));

    $this->get(route('storefront.checkout.confirmation', ['checkoutId' => $checkout->id]))
        ->assertOk();

    $order = Order::query()->latest('id')->first();
    $payment = Payment::query()->where('order_id', $order?->id)->first();

    expect($order)->not->toBeNull()
        ->and($order?->status)->toBe(OrderStatus::Paid)
        ->and($order?->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order?->payment_method)->toBe(PaymentMethod::CreditCard)
        ->and($payment?->status)->toBe(PaymentStatus::Captured)
        ->and($cart->fresh()->status)->toBe(CartStatus::Converted);
});

it('shows checkout decline errors and keeps cart active when card is declined', function () {
    $store = Store::factory()->create();
    $variant = createSellableVariant(
        $store,
        variantAttributes: [
            'requires_shipping' => false,
            'price_amount' => 2500,
        ],
        inventoryAttributes: ['quantity_on_hand' => 10],
    );

    $this->post('/cart/lines', [
        'variant_id' => $variant->id,
        'quantity' => 1,
    ])->assertRedirect(route('storefront.cart.show'));

    $cart = Cart::query()->where('store_id', $store->id)->firstOrFail();

    $this->post('/checkout')->assertRedirect();

    $checkout = Checkout::query()->where('cart_id', $cart->id)->latest('id')->firstOrFail();

    $this->from(route('storefront.checkout.show', ['checkoutId' => $checkout->id]))
        ->post("/checkout/{$checkout->id}/submit", [
            'email' => 'buyer@gmail.com',
            'payment_method' => PaymentMethod::CreditCard->value,
            'billing_same_as_shipping' => true,
            'card' => [
                'number' => '4000000000000002',
                'name' => 'Jane Buyer',
                'expiry' => now()->addYear()->format('m/y'),
                'cvc' => '123',
            ],
        ])
        ->assertRedirect(route('storefront.checkout.show', ['checkoutId' => $checkout->id]))
        ->assertSessionHasErrors('payment');

    expect(Order::query()->count())->toBe(0)
        ->and($cart->fresh()->status)->toBe(CartStatus::Active);
});
