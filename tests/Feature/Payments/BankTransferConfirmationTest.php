<?php

use App\Jobs\CancelUnpaidBankTransferOrders;
use App\Models\Order;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Services\ProductService;

function makeBankTransferOrder(bool $digital = false): Order
{
    $ctx = createStoreContext();
    $store = $ctx['store'];
    $product = app(ProductService::class)->create($store, [
        'title' => $digital ? 'Ebook' : 'Widget',
        'price_amount' => 2500,
        'quantity_on_hand' => 10,
        'requires_shipping' => ! $digital,
    ]);
    app(ProductService::class)->transitionStatus($product, \App\Enums\ProductStatus::Active);
    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'type' => 'flat', 'config_json' => ['amount' => 499]]);
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $product->variants()->first()->id, 1);
    $checkout = app(CheckoutService::class)->create($cart, 'x@example.com');
    app(CheckoutService::class)->setAddress($checkout, [
        'email' => 'x@example.com',
        'shipping_address' => ['first_name' => 'Jane', 'last_name' => 'Doe', 'address1' => 'Main 1', 'city' => 'Berlin', 'country' => 'Germany', 'country_code' => 'DE', 'postal_code' => '10115'],
    ]);
    app(CheckoutService::class)->setShippingMethod($checkout, $rate->id);
    app(CheckoutService::class)->selectPaymentMethod($checkout, 'bank_transfer');

    return app(CheckoutService::class)->completeCheckout($checkout, ['payment_method' => 'bank_transfer']);
}

it('admin can confirm bank transfer payment', function () {
    $order = makeBankTransferOrder();
    $variant = $order->lines()->first()->variant;
    $before = $variant->inventoryItem->fresh()->quantity_on_hand;

    app(OrderService::class)->confirmPayment($order);

    expect($order->fresh()->financial_status)->toBe('paid');
    expect($order->fresh()->status)->toBe('paid');
    expect($order->payments()->first()->status)->toBe('captured');
    expect($variant->inventoryItem->fresh()->quantity_on_hand)->toBe($before - $order->lines()->first()->quantity);
});

it('cannot confirm payment for non-bank-transfer orders', function () {
    $order = makeCompletedOrder();

    expect(fn () => app(OrderService::class)->confirmPayment($order))
        ->toThrow(InvalidArgumentException::class);
});

it('cannot confirm already confirmed payment', function () {
    $order = makeBankTransferOrder();
    app(OrderService::class)->confirmPayment($order);

    expect(fn () => app(OrderService::class)->confirmPayment($order->fresh()))
        ->toThrow(InvalidArgumentException::class);
});

it('auto-cancel job cancels unpaid bank transfer orders after config days', function () {
    $order = makeBankTransferOrder();
    $order->update(['placed_at' => now()->subDays(8)]);

    (new CancelUnpaidBankTransferOrders)->handle(app(\App\Services\InventoryService::class));

    expect($order->fresh()->status)->toBe('cancelled');
    expect($order->fresh()->financial_status)->toBe('voided');
});

it('auto-fulfills digital products on payment confirmation', function () {
    $order = makeBankTransferOrder(true);

    app(OrderService::class)->confirmPayment($order);

    expect($order->fresh()->fulfillment_status)->toBe('fulfilled');
    expect($order->fulfillments()->first()->status)->toBe('delivered');
});
