<?php

use App\Enums\PaymentMethod;
use App\Events\OrderCreated;
use App\Models\Customer;
use App\Services\CheckoutService;
use App\Services\OrderService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->checkouts = app(CheckoutService::class);
    $this->orders = app(OrderService::class);
});

/**
 * Complete a physical-goods checkout and return the resulting order.
 *
 * @param  list<array{0: int, 1: int}>  $lines
 */
function placeOrder(CheckoutService $checkouts, array $lines = [[5000, 1]], ?Customer $customer = null, string $email = 'buyer@example.com'): App\Models\Order
{
    $store = app('current_store');
    $cart = cartWithLines($lines);
    if ($customer !== null) {
        $cart->update(['customer_id' => $customer->id]);
    }

    $checkout = $checkouts->startFromCart($cart->fresh('lines.variant.product'));
    $checkouts->setAddress($checkout, germanAddressData($email));

    $zone = App\Models\ShippingZone::factory()->for($store)->countries(['DE'])->create();
    $rate = App\Models\ShippingRate::factory()->for($zone, 'zone')->flat(499)->create();
    $checkouts->setShippingMethod($checkout->fresh(), $rate->id);
    $checkouts->selectPaymentMethod($checkout->fresh(), PaymentMethod::CreditCard);

    return $checkouts->completeCheckout($checkout->fresh(), ['card_number' => '4242424242424242']);
}

it('creates an order from a completed checkout', function () {
    $order = placeOrder($this->checkouts);

    expect($order->exists)->toBeTrue()
        ->and($order->total_amount)->toBeGreaterThan(0)
        ->and($order->status->value)->toBe('paid');
});

it('generates sequential order numbers per store', function () {
    $a = placeOrder($this->checkouts);
    $b = placeOrder($this->checkouts);
    $c = placeOrder($this->checkouts);

    expect($a->order_number)->toBe('#1001')
        ->and($b->order_number)->toBe('#1002')
        ->and($c->order_number)->toBe('#1003');
});

it('creates order lines with snapshots', function () {
    $order = placeOrder($this->checkouts, [[2500, 1], [7500, 2]]);

    expect($order->lines)->toHaveCount(2);
    $order->lines->each(function ($line) {
        expect($line->title_snapshot)->not->toBeEmpty()
            ->and($line->sku_snapshot)->not->toBeNull();
    });
});

it('commits inventory on order creation', function () {
    $order = placeOrder($this->checkouts, [[5000, 3]]);
    $item = $order->lines->first()->variant->inventoryItem->fresh();

    expect($item->quantity_on_hand)->toBe(97)
        ->and($item->quantity_reserved)->toBe(0);
});

it('marks cart as converted', function () {
    $order = placeOrder($this->checkouts);

    expect($order->checkout->cart->fresh()->status->value)->toBe('converted');
});

it('dispatches OrderCreated event', function () {
    Event::fake([OrderCreated::class]);

    placeOrder($this->checkouts);

    Event::assertDispatched(OrderCreated::class);
});

it('preserves order data when product is deleted', function () {
    $order = placeOrder($this->checkouts);
    $variant = $order->lines->first()->variant;
    $variant->product->update(['status' => 'archived']);

    $line = $order->fresh()->lines->first();

    expect($line->title_snapshot)->not->toBeEmpty()
        ->and($line->sku_snapshot)->not->toBeNull();
});

it('links order to customer when authenticated', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);

    $order = placeOrder($this->checkouts, customer: $customer);

    expect($order->customer_id)->toBe($customer->id);
});

it('sets email from checkout on the order', function () {
    $order = placeOrder($this->checkouts, email: 'test@example.com');

    expect($order->email)->toBe('test@example.com');
});
