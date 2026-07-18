<?php

use App\Enums\CheckoutStatus;
use App\Enums\OrderStatus;
use App\Livewire\Storefront\Checkout\Show as CheckoutShow;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function buildCheckoutForPayment(): array
{
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    $product = Product::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->withInventory(5)->create([
        'product_id' => $product->id,
        'price_amount' => 2000,
        'is_default' => true,
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->id, 'currency' => $store->default_currency]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'unit_price_amount' => 2000,
        'quantity' => 2,
        'line_subtotal_amount' => 4000,
        'line_total_amount' => 4000,
    ]);

    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'name' => 'Standard Shipping']);

    $checkout = app(CheckoutService::class)->create($cart);

    return compact('checkout', 'rate');
}

it('completes the checkout happy path with a credit card and redirects to confirmation', function () {
    ['checkout' => $checkout, 'rate' => $rate] = buildCheckoutForPayment();

    $component = Livewire::test(CheckoutShow::class, ['checkout' => $checkout])
        ->set('email', 'buyer@example.com')
        ->set('firstName', 'Jane')
        ->set('lastName', 'Doe')
        ->set('address1', '1 Main St')
        ->set('city', 'Berlin')
        ->set('postalCode', '10115')
        ->set('country', 'DE')
        ->call('saveAddress')
        ->assertHasNoErrors();

    expect($checkout->fresh()->status)->toBe(CheckoutStatus::Addressed);

    $component
        ->call('selectShippingRate', $rate->id)
        ->assertHasNoErrors();

    expect($checkout->fresh()->status)->toBe(CheckoutStatus::ShippingSelected);

    $component
        ->set('selectedPaymentMethod', 'credit_card')
        ->set('cardNumber', '4242424242424242')
        ->set('cardholderName', 'Jane Doe')
        ->set('cardExpiry', '12/28')
        ->set('cardCvc', '123')
        ->call('pay')
        ->assertHasNoErrors();

    $checkout->refresh();
    expect($checkout->status)->toBe(CheckoutStatus::Completed);

    $order = Order::query()->where('checkout_id', $checkout->id)->firstOrFail();
    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->email)->toBe('buyer@example.com')
        ->and($order->total_amount)->toBeGreaterThan(0);

    $component->assertRedirect(route('storefront.checkout.confirmation', $checkout));
});

it('shows a decline error for the magic decline card and allows retrying payment', function () {
    ['checkout' => $checkout, 'rate' => $rate] = buildCheckoutForPayment();

    $component = Livewire::test(CheckoutShow::class, ['checkout' => $checkout])
        ->set('email', 'buyer@example.com')
        ->set('firstName', 'Jane')
        ->set('lastName', 'Doe')
        ->set('address1', '1 Main St')
        ->set('city', 'Berlin')
        ->set('postalCode', '10115')
        ->set('country', 'DE')
        ->call('saveAddress')
        ->call('selectShippingRate', $rate->id)
        ->set('selectedPaymentMethod', 'credit_card')
        ->set('cardNumber', '4000000000000002')
        ->set('cardholderName', 'Jane Doe')
        ->set('cardExpiry', '12/28')
        ->set('cardCvc', '123')
        ->call('pay');

    expect($component->get('paymentError'))->toContain('declined');
    expect($checkout->fresh()->status)->toBe(CheckoutStatus::PaymentSelected);
    expect(Order::query()->where('checkout_id', $checkout->id)->exists())->toBeFalse();

    $component
        ->set('cardNumber', '4242424242424242')
        ->call('pay')
        ->assertHasNoErrors();

    expect($checkout->fresh()->status)->toBe(CheckoutStatus::Completed);
});
