<?php

use App\Enums\CheckoutStatus;
use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Livewire\Storefront\Checkout\Confirmation;
use App\Livewire\Storefront\Checkout\Show;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Services\CheckoutService;
use Livewire\Livewire;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function checkoutAddress(): array
{
    return CommerceTestHelper::address();
}

function checkoutCardDetails(): array
{
    return [
        'cardNumber' => '4242424242424242',
        'cardExpiry' => '12/28',
        'cardCvc' => '123',
        'cardHolder' => 'Jane Doe',
    ];
}

it('completes a full guest checkout journey to confirmation', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store, [['price' => 2500, 'qty' => 2]]);
    $rate = CommerceTestHelper::flatShippingRate($store);
    $checkout = app(CheckoutService::class)->createFromCart($cart, 'guest@example.com');

    $page = $this->get(storefrontUrl($domain->hostname, '/checkout/'.$checkout->id));
    $page->assertOk();
    $page->assertSee('Checkout');

    $test = Livewire::test(Show::class, ['checkoutId' => $checkout->id])
        ->set('email', 'guest@example.com')
        ->call('saveContact')
        ->set('shipping', checkoutAddress())
        ->call('saveAddress');

    expect($checkout->refresh()->status)->toBe(CheckoutStatus::Addressed);

    $test->set('shippingRateId', $rate->id)->call('saveShipping');

    expect($checkout->refresh()->status)->toBe(CheckoutStatus::ShippingSelected);

    $test->set(checkoutCardDetails())->call('pay')->assertRedirect();

    $order = Order::query()->where('checkout_id', $checkout->id)->first();

    expect($order)->not->toBeNull();
    expect($order->status->value)->toBe('paid');
    expect($order->total_amount)->toBeGreaterThan(0);

    $confirmation = $this->get(storefrontUrl($domain->hostname, '/checkout/'.$checkout->id.'/confirmation'));
    $confirmation->assertOk();
    $confirmation->assertSee('Thank you for your order!');
    $confirmation->assertSee($order->order_number);
});

it('applies a discount code during checkout', function () {
    ['store' => $store] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store, [['price' => 5000, 'qty' => 1]]);
    $rate = CommerceTestHelper::flatShippingRate($store);
    Discount::factory()->create([
        'store_id' => $store->id,
        'type' => DiscountType::Code,
        'code' => 'WELCOME10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'status' => DiscountStatus::Active,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'usage_limit' => null,
    ]);
    $checkout = app(CheckoutService::class)->createFromCart($cart, 'guest@example.com');

    Livewire::test(Show::class, ['checkoutId' => $checkout->id])
        ->set('email', 'guest@example.com')
        ->set('shipping', checkoutAddress())
        ->call('saveAddress')
        ->set('shippingRateId', $rate->id)
        ->call('saveShipping')
        ->set('discountCodeInput', 'WELCOME10')
        ->call('applyDiscount')
        ->assertHasNoErrors();

    expect($checkout->refresh()->discount_code)->toBe('WELCOME10');
    expect((int) ($checkout->refresh()->totals_json['discount'] ?? 0))->toBe(500);
});

it('rejects invalid discount codes at checkout', function () {
    ['store' => $store] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);
    $checkout = app(CheckoutService::class)->createFromCart($cart, 'guest@example.com');

    Livewire::test(Show::class, ['checkoutId' => $checkout->id])
        ->set('discountCodeInput', 'NOPE')
        ->call('applyDiscount')
        ->assertHasErrors('discountCodeInput');
});

it('shows a payment decline error and lets the customer retry', function () {
    ['store' => $store] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);
    $rate = CommerceTestHelper::flatShippingRate($store);
    $checkout = app(CheckoutService::class)->createFromCart($cart, 'guest@example.com');

    $test = Livewire::test(Show::class, ['checkoutId' => $checkout->id])
        ->set('email', 'guest@example.com')
        ->set('shipping', checkoutAddress())
        ->call('saveAddress')
        ->set('shippingRateId', $rate->id)
        ->call('saveShipping')
        ->set('cardNumber', '4000000000000002')
        ->set('cardExpiry', '12/28')
        ->set('cardCvc', '123')
        ->set('cardHolder', 'Jane Doe')
        ->call('pay');

    $test->assertSet('payError', 'Payment declined: Your card was declined.');

    expect(Order::query()->where('checkout_id', $checkout->id)->exists())->toBeFalse();

    $test->set('cardNumber', '4242424242424242')->call('pay')->assertRedirect();

    expect(Order::query()->where('checkout_id', $checkout->id)->exists())->toBeTrue();
});

it('places bank transfer orders with instructions on the confirmation page', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);
    $rate = CommerceTestHelper::flatShippingRate($store);
    $checkout = app(CheckoutService::class)->createFromCart($cart, 'guest@example.com');

    Livewire::test(Show::class, ['checkoutId' => $checkout->id])
        ->set('email', 'guest@example.com')
        ->set('shipping', checkoutAddress())
        ->call('saveAddress')
        ->set('shippingRateId', $rate->id)
        ->call('saveShipping')
        ->set('paymentMethod', 'bank_transfer')
        ->call('pay')
        ->assertRedirect();

    $order = Order::query()->where('checkout_id', $checkout->id)->first();

    expect($order->financial_status->value)->toBe('pending');

    $confirmation = $this->get(storefrontUrl($domain->hostname, '/checkout/'.$checkout->id.'/confirmation'));
    $confirmation->assertOk();
    $confirmation->assertSee('Bank Transfer Instructions');
    $confirmation->assertSee('DE89 3704 0044 0532 0130 00');
});

it('completes checkout for a logged in customer', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $store->id]);
    $cart = CommerceTestHelper::cartWithLines($store);
    $rate = CommerceTestHelper::flatShippingRate($store);
    $this->actingAs($customer, 'customer');
    $checkout = app(CheckoutService::class)->createFromCart($cart, $customer->email, $customer);

    Livewire::test(Show::class, ['checkoutId' => $checkout->id])
        ->assertSet('email', $customer->email)
        ->set('shipping', checkoutAddress())
        ->call('saveAddress')
        ->set('shippingRateId', $rate->id)
        ->call('saveShipping')
        ->set(checkoutCardDetails())
        ->call('pay')
        ->assertRedirect();

    $order = Order::query()->where('checkout_id', $checkout->id)->first();

    expect($order->customer_id)->toBe($customer->id);
});

it('validates checkout addresses', function () {
    ['store' => $store] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);
    $checkout = app(CheckoutService::class)->createFromCart($cart, 'guest@example.com');

    Livewire::test(Show::class, ['checkoutId' => $checkout->id])
        ->set('email', 'not-an-email')
        ->set('shipping', ['first_name' => '', 'country_code' => 'XXL'])
        ->call('saveAddress')
        ->assertHasErrors(['email', 'shipping.first_name', 'shipping.last_name', 'shipping.address1', 'shipping.city', 'shipping.country_code', 'shipping.postal_code']);
});

it('isolates checkouts by store and handles expired checkouts', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    ['store' => $otherStore] = $this->createStoreContext();
    app(App\Tenancy\CurrentStore::class)->set($otherStore);
    $otherCart = CommerceTestHelper::cartWithLines($otherStore);
    $foreign = app(CheckoutService::class)->createFromCart($otherCart, 'guest@example.com');
    app(App\Tenancy\CurrentStore::class)->set($store);
    $cart = CommerceTestHelper::cartWithLines($store);
    $checkout = app(CheckoutService::class)->createFromCart($cart, 'guest@example.com');
    $expired = app(CheckoutService::class)->createFromCart($cart, 'guest@example.com');
    app(CheckoutService::class)->expireCheckout($expired);

    $this->get(storefrontUrl($domain->hostname, '/checkout/'.$foreign->id))->assertNotFound();
    $this->get(storefrontUrl($domain->hostname, '/checkout/'.$expired->id))->assertStatus(410);
    $this->get(storefrontUrl($domain->hostname, '/checkout/'.$foreign->id.'/confirmation'))->assertNotFound();
});

it('redirects incomplete checkouts away from the confirmation page', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);
    $checkout = app(CheckoutService::class)->createFromCart($cart, 'guest@example.com');

    $this->get(storefrontUrl($domain->hostname, '/checkout/'.$checkout->id.'/confirmation'))
        ->assertRedirect(storefrontUrl($domain->hostname, '/checkout/'.$checkout->id));
});

it('renders the confirmation component for completed checkouts', function () {
    ['store' => $store] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);
    $rate = CommerceTestHelper::flatShippingRate($store);
    $checkout = app(CheckoutService::class)->createFromCart($cart, 'guest@example.com');

    Livewire::test(Show::class, ['checkoutId' => $checkout->id])
        ->set('email', 'guest@example.com')
        ->set('shipping', checkoutAddress())
        ->call('saveAddress')
        ->set('shippingRateId', $rate->id)
        ->call('saveShipping')
        ->set(checkoutCardDetails())
        ->call('pay');

    $order = Order::query()->where('checkout_id', $checkout->id)->first();

    Livewire::test(Confirmation::class, ['checkoutId' => $checkout->id])
        ->assertSee('Thank you for your order!')
        ->assertSee($order->order_number);
});
