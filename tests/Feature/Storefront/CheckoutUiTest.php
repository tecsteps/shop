<?php

use App\Livewire\Storefront\Checkout\Confirmation;
use App\Livewire\Storefront\Checkout\Show as CheckoutPage;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
});

/**
 * Drive the session cart's checkout to payment_selected so the checkout
 * page mounts on the pay step.
 */
function paymentStepCheckout($test, string $paymentMethod = 'credit_card'): Checkout
{
    $variant = createPurchasableVariant($test->store, 2500);

    $cartService = app(CartService::class);
    $cart = $cartService->getOrCreateForSession($test->store);
    $cartService->addLine($cart, $variant->getKey(), 2);

    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->createFromCart($cart);
    $checkout = $checkoutService->setAddress($checkout, [
        'email' => 'shopper@example.test',
        'shipping_address' => validShippingAddress(),
    ]);

    $zone = ShippingZone::factory()->for($test->store)->create(['countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->for($zone, 'zone')->flatAmount(499)->create();
    $checkout = $checkoutService->setShippingMethod($checkout, $rate->getKey());
    $checkout = $checkoutService->selectPaymentMethod($checkout, $paymentMethod);

    Session::put('checkout_id', $checkout->getKey());

    return $checkout;
}

it('pays with credit card and redirects to the confirmation page', function () {
    $checkout = paymentStepCheckout($this);

    Livewire::test(CheckoutPage::class)
        ->assertSet('step', 5)
        ->set('cardNumber', '4242424242424242')
        ->set('cardName', 'Erika Mustermann')
        ->set('cardExpiry', '12/28')
        ->set('cardCvc', '123')
        ->call('payNow')
        ->assertRedirect(route('storefront.checkout.confirmation', ['checkoutId' => $checkout->getKey()]));

    $this->assertDatabaseHas('orders', [
        'checkout_id' => $checkout->getKey(),
        'status' => 'paid',
    ]);
});

it('shows an error and stays on the payment step when the card is declined', function () {
    paymentStepCheckout($this);

    Livewire::test(CheckoutPage::class)
        ->set('cardNumber', '4000000000000002')
        ->set('cardName', 'Erika Mustermann')
        ->set('cardExpiry', '12/28')
        ->set('cardCvc', '123')
        ->call('payNow')
        ->assertSet('step', 5)
        ->assertSee('declined');

    expect(Order::query()->count())->toBe(0);
});

it('renders the order confirmation page', function () {
    $checkout = paymentStepCheckout($this);
    $order = app(CheckoutService::class)->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    Livewire::test(Confirmation::class, ['checkoutId' => $checkout->getKey()])
        ->assertSee('Thank you for your order!')
        ->assertSee($order->order_number)
        ->assertSee('54.99 EUR');
});

it('shows bank transfer instructions on the confirmation page for pending orders', function () {
    $checkout = paymentStepCheckout($this, 'bank_transfer');
    $order = app(CheckoutService::class)->completeCheckout($checkout);

    Livewire::test(Confirmation::class, ['checkoutId' => $checkout->getKey()])
        ->assertSee('Bank Transfer Instructions')
        ->assertSee('DE89 3704 0044 0532 0130 00')
        ->assertSee($order->order_number);
});

it('prefills the checkout address step from the customer default address', function () {
    $customer = Customer::factory()->for($this->store)->create();

    CustomerAddress::factory()->for($customer)->create([
        'address_json' => [
            'first_name' => 'Jane',
            'last_name' => 'Shopper',
            'company' => '',
            'address1' => 'Musterstrasse 1',
            'address2' => '',
            'city' => 'Berlin',
            'province' => '',
            'province_code' => '',
            'country' => 'Germany',
            'country_code' => 'DE',
            'zip' => '10115',
            'phone' => '',
        ],
        'is_default' => true,
    ]);

    $variant = createPurchasableVariant($this->store);
    $cartService = app(CartService::class);
    $cart = $cartService->create($this->store, $customer);
    $cartService->addLine($cart, $variant->getKey(), 1);

    actingAsCustomer($customer);

    Livewire::test(CheckoutPage::class)
        ->assertSet('email', $customer->email)
        ->assertSet('shipping.first_name', 'Jane')
        ->assertSet('shipping.address1', 'Musterstrasse 1')
        ->assertSet('shipping.postal_code', '10115')
        ->assertSet('shipping.country_code', 'DE');
});
