<?php

use App\Enums\CartStatus;
use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Events\OrderCreated;
use App\Models\Customer;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\Event;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function orderContext(array $lines = [['price' => 2500, 'qty' => 2]]): array
{
    ['store' => $store] = test()->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store, $lines);
    $rate = CommerceTestHelper::flatShippingRate($store, ['DE'], 499);

    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->createFromCart($cart, 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $rate->id);
    $checkout = $checkouts->selectPaymentMethod($checkout, PaymentMethod::CreditCard);

    return ['store' => $store, 'cart' => $cart, 'checkout' => $checkout];
}

it('creates an order from a completed checkout', function () {
    $ctx = orderContext();

    $order = app(CheckoutService::class)->pay($ctx['checkout'], PaymentMethod::CreditCard, [
        'card_number' => '4242424242424242',
    ]);

    expect($order->status)->toBe(OrderStatus::Paid);
    expect($order->financial_status)->toBe(FinancialStatus::Paid);
    expect($order->subtotal_amount)->toBe(5000);
    expect($order->shipping_amount)->toBe(499);
    expect($order->total_amount)->toBe($order->subtotal_amount - $order->discount_amount + $order->shipping_amount + $order->tax_amount);
});

it('generates sequential order numbers per store', function () {
    ['store' => $store] = $this->createStoreContext();

    $numbers = [];

    for ($i = 0; $i < 3; $i++) {
        $cart = CommerceTestHelper::cartWithLines($store, [['price' => 1000, 'qty' => 1]]);
        $rate = $i === 0 ? CommerceTestHelper::flatShippingRate($store) : App\Models\ShippingRate::query()->first();

        $checkouts = app(CheckoutService::class);
        $checkout = $checkouts->createFromCart($cart, "customer{$i}@example.com");
        $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
        $checkout = $checkouts->setShippingMethod($checkout, $rate->id);
        $checkout = $checkouts->selectPaymentMethod($checkout, PaymentMethod::CreditCard);
        $numbers[] = $checkouts->pay($checkout, PaymentMethod::CreditCard, ['card_number' => '4242424242424242'])->order_number;
    }

    expect($numbers)->toBe(['#1001', '#1002', '#1003']);
});

it('creates order lines with snapshots', function () {
    $ctx = orderContext();

    $order = app(CheckoutService::class)->pay($ctx['checkout'], PaymentMethod::CreditCard, [
        'card_number' => '4242424242424242',
    ]);

    $line = $order->orderLines()->first();

    expect($line->title_snapshot)->not->toBe('');
    expect($line->sku_snapshot)->not->toBeNull();
    expect($line->quantity)->toBe(2);
    expect($line->unit_price_amount)->toBe(2500);
});

it('commits inventory on order creation', function () {
    $ctx = orderContext();

    app(CheckoutService::class)->pay($ctx['checkout'], PaymentMethod::CreditCard, [
        'card_number' => '4242424242424242',
    ]);

    $item = App\Models\InventoryItem::query()
        ->where('variant_id', $ctx['cart']->cartLines()->first()->variant_id)
        ->first();

    expect($item->quantity_on_hand)->toBe(48);
    expect($item->quantity_reserved)->toBe(0);
});

it('marks cart as converted', function () {
    $ctx = orderContext();

    app(CheckoutService::class)->pay($ctx['checkout'], PaymentMethod::CreditCard, [
        'card_number' => '4242424242424242',
    ]);

    expect($ctx['cart']->refresh()->status)->toBe(CartStatus::Converted);
});

it('dispatches OrderCreated event', function () {
    Event::fake([OrderCreated::class]);
    $ctx = orderContext();

    app(CheckoutService::class)->pay($ctx['checkout'], PaymentMethod::CreditCard, [
        'card_number' => '4242424242424242',
    ]);

    Event::assertDispatched(OrderCreated::class);
});

it('preserves order data when product is archived', function () {
    $ctx = orderContext();

    $order = app(CheckoutService::class)->pay($ctx['checkout'], PaymentMethod::CreditCard, [
        'card_number' => '4242424242424242',
    ]);

    $line = $order->orderLines()->first();
    $product = App\Models\ProductVariant::query()->whereKey($ctx['cart']->cartLines()->first()->variant_id)->first()->product;
    app(App\Services\ProductService::class)->archive($product->refresh());

    expect($line->refresh()->title_snapshot)->not->toBe('');
    expect($order->refresh()->orderLines()->count())->toBe(1);
});

it('links order to customer when authenticated', function () {
    ['store' => $store] = $this->createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $store->id, 'email' => 'member@example.com']);
    $cart = CommerceTestHelper::cartWithLines($store);
    $cart->customer_id = $customer->id;
    $cart->save();
    $rate = CommerceTestHelper::flatShippingRate($store);

    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->createFromCart($cart, 'member@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $rate->id);
    $checkout = $checkouts->selectPaymentMethod($checkout, PaymentMethod::CreditCard);
    $order = $checkouts->pay($checkout, PaymentMethod::CreditCard, ['card_number' => '4242424242424242']);

    expect($order->customer_id)->toBe($customer->id);
});

it('sets email from checkout on the order', function () {
    $ctx = orderContext();

    $order = app(CheckoutService::class)->pay($ctx['checkout'], PaymentMethod::CreditCard, [
        'card_number' => '4242424242424242',
    ]);

    expect($order->email)->toBe('customer@example.com');
});

it('creates a guest customer for unknown checkout email', function () {
    $ctx = orderContext();

    $order = app(CheckoutService::class)->pay($ctx['checkout'], PaymentMethod::CreditCard, [
        'card_number' => '4242424242424242',
    ]);

    expect($order->customer_id)->not->toBeNull();
    expect(Customer::query()->whereKey($order->customer_id)->first()->email)->toBe('customer@example.com');
});
