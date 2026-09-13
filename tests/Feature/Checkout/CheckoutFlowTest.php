<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Events\OrderCreated;
use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\Event;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function flowContext(): array
{
    ['store' => $store] = test()->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);
    $rate = CommerceTestHelper::flatShippingRate($store);

    return ['store' => $store, 'cart' => $cart, 'rate' => $rate];
}

function driveToPaymentSelected(array $ctx): App\Models\Checkout
{
    $checkouts = app(CheckoutService::class);

    $checkout = $checkouts->createFromCart($ctx['cart'], 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $ctx['rate']->id);

    return $checkouts->selectPaymentMethod($checkout, PaymentMethod::CreditCard);
}

it('creates a checkout from a cart', function () {
    $ctx = flowContext();

    $checkout = app(CheckoutService::class)->createFromCart($ctx['cart'], 'customer@example.com');

    expect($checkout->status)->toBe(CheckoutStatus::Started);
    expect($checkout->cart_id)->toBe($ctx['cart']->id);
    expect($checkout->email)->toBe('customer@example.com');
});

it('completes full checkout happy path', function () {
    Event::fake([OrderCreated::class]);

    $ctx = flowContext();
    $checkout = driveToPaymentSelected($ctx);

    $variantId = $ctx['cart']->cartLines()->first()->variant_id;
    $item = App\Models\InventoryItem::query()->where('variant_id', $variantId)->first();

    expect($item->quantity_reserved)->toBe(2);

    $order = app(CheckoutService::class)->pay($checkout, PaymentMethod::CreditCard, [
        'card_number' => '4242424242424242',
        'card_expiry' => '12/28',
        'card_cvc' => '123',
        'card_holder' => 'Jane Doe',
    ]);

    expect($order->status)->toBe(OrderStatus::Paid);
    expect($order->financial_status)->toBe(FinancialStatus::Paid);
    expect($order->orderLines()->count())->toBe(1);
    expect($ctx['cart']->refresh()->status)->toBe(CartStatus::Converted);
    expect($checkout->refresh()->status)->toBe(CheckoutStatus::Completed);

    $item->refresh();
    expect($item->quantity_on_hand)->toBe(48);
    expect($item->quantity_reserved)->toBe(0);

    Event::assertDispatched(OrderCreated::class);
});

it('rejects checkout for empty cart', function () {
    ['store' => $store] = $this->createStoreContext();
    $cart = app(App\Services\CartService::class)->create($store);

    app(CheckoutService::class)->createFromCart($cart, 'customer@example.com');
})->throws(Illuminate\Validation\ValidationException::class);

it('expires checkout after timeout', function () {
    $ctx = flowContext();
    $checkout = driveToPaymentSelected($ctx);
    $checkout->expires_at = now()->subHour();
    $checkout->save();

    app(App\Jobs\ExpireAbandonedCheckouts::class)->handle();

    expect($checkout->refresh()->status)->toBe(CheckoutStatus::Expired);

    $variantId = $ctx['cart']->cartLines()->first()->variant_id;
    $item = App\Models\InventoryItem::query()->where('variant_id', $variantId)->first();
    expect($item->quantity_reserved)->toBe(0);
});

it('prevents duplicate orders from same checkout', function () {
    $ctx = flowContext();
    $checkout = driveToPaymentSelected($ctx);

    $checkouts = app(CheckoutService::class);
    $details = ['card_number' => '4242424242424242'];

    $first = $checkouts->pay($checkout, PaymentMethod::CreditCard, $details);
    $second = $checkouts->pay($checkout->refresh(), PaymentMethod::CreditCard, $details);

    expect($second->id)->toBe($first->id);
    expect(Order::query()->count())->toBe(1);
});
