<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentOrderStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Events\OrderPaid;
use App\Exceptions\InvalidOrderTransitionException;
use App\Jobs\CancelUnpaidBankTransferOrders;
use App\Models\Checkout;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\StoreSettings;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\OrderService;
use Illuminate\Support\Facades\Event;

/**
 * Store with variant (2500), DE zone with flat rate 499.
 *
 * @return array{0: Store, 1: ProductVariant, 2: ShippingRate}
 */
function bankTransferSetup(bool $digital = false): array
{
    $store = test()->createStore();
    test()->bindStore($store);

    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->withInventory(10)->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
        'requires_shipping' => ! $digital,
    ]);

    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->flat(499)->create(['zone_id' => $zone->id]);

    return [$store, $variant, $rate];
}

/**
 * Create a bank transfer order via the full checkout flow.
 *
 * @return array{0: Store, 1: ProductVariant, 2: Order}
 */
function bankTransferOrder(bool $digital = false, int $quantity = 2): array
{
    [$store, $variant, $rate] = bankTransferSetup($digital);

    $service = app(CheckoutService::class);
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, $quantity);

    $checkout = $service->createFromCart($cart->refresh(), 'customer@example.com');
    $checkout = $service->setAddress($checkout, [
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country' => 'DE',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
    ]);
    $checkout = $service->setShippingMethod($checkout, $digital ? null : $rate->id);
    $checkout = $service->selectPaymentMethod($checkout, 'bank_transfer');

    $order = $service->completeCheckout($checkout, ['payment_method' => 'bank_transfer']);

    return [$store, $variant, $order];
}

test('admin can confirm bank transfer payment', function () {
    [$store, $variant, $order] = bankTransferOrder();

    expect($variant->inventoryItem->refresh()->quantity_reserved)->toBe(2);

    Event::fake([OrderPaid::class]);

    app(OrderService::class)->confirmBankTransferPayment($order->refresh());

    $order->refresh();
    expect($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->status)->toBe(OrderStatus::Paid)
        ->and($order->payments->first()->status)->toBe(PaymentStatus::Captured);

    $item = $variant->inventoryItem->refresh();
    expect($item->quantity_on_hand)->toBe(8)
        ->and($item->quantity_reserved)->toBe(0);

    Event::assertDispatched(OrderPaid::class, fn (OrderPaid $event): bool => $event->order->id === $order->id);
});

test('cannot confirm payment for non-bank-transfer orders', function () {
    [$store, $variant, $rate] = bankTransferSetup();

    $service = app(CheckoutService::class);
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, 1);

    $checkout = $service->createFromCart($cart->refresh(), 'customer@example.com');
    $checkout = $service->setAddress($checkout, [
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country' => 'DE',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
    ]);
    $checkout = $service->setShippingMethod($checkout, $rate->id);
    $checkout = $service->selectPaymentMethod($checkout, 'credit_card');

    $order = $service->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    app(OrderService::class)->confirmBankTransferPayment($order);
})->throws(InvalidOrderTransitionException::class);

test('cannot confirm already confirmed payment', function () {
    [$store, $variant, $order] = bankTransferOrder();

    $service = app(OrderService::class);
    $service->confirmBankTransferPayment($order->refresh());
    $service->confirmBankTransferPayment($order->refresh());
})->throws(InvalidOrderTransitionException::class);

test('auto-cancel job cancels unpaid bank transfer orders after config days', function () {
    [$store, $variant, $order] = bankTransferOrder();

    $order->update(['placed_at' => now()->subDays(8)]);

    Event::fake([OrderCancelled::class]);

    (new CancelUnpaidBankTransferOrders)->handle(app(OrderService::class));

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Cancelled)
        ->and($order->financial_status)->toBe(FinancialStatus::Voided)
        ->and($order->payments->first()->status)->toBe(PaymentStatus::Failed)
        ->and($variant->inventoryItem->refresh()->quantity_reserved)->toBe(0);

    Event::assertDispatched(OrderCancelled::class, fn (OrderCancelled $event): bool => $event->order->id === $order->id);
});

test('auto-cancel job does not cancel orders within config days', function () {
    [$store, $variant, $order] = bankTransferOrder();

    $order->update(['placed_at' => now()->subDays(2)]);

    (new CancelUnpaidBankTransferOrders)->handle(app(OrderService::class));

    expect($order->refresh()->status)->toBe(OrderStatus::Pending)
        ->and($order->financial_status)->toBe(FinancialStatus::Pending)
        ->and($variant->inventoryItem->refresh()->quantity_reserved)->toBe(2);
});

test('auto-cancel job respects the per-store bank_transfer_cancel_days setting', function () {
    [$store, $variant, $order] = bankTransferOrder();

    StoreSettings::create([
        'store_id' => $store->id,
        'settings_json' => ['bank_transfer_cancel_days' => 3],
    ]);

    $order->update(['placed_at' => now()->subDays(5)]);

    (new CancelUnpaidBankTransferOrders)->handle(app(OrderService::class));

    expect($order->refresh()->status)->toBe(OrderStatus::Cancelled);
});

test('auto-fulfills digital products on payment confirmation', function () {
    [$store, $variant, $order] = bankTransferOrder(digital: true);

    app(OrderService::class)->confirmBankTransferPayment($order->refresh());

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentOrderStatus::Fulfilled)
        ->and($order->status)->toBe(OrderStatus::Fulfilled);

    $fulfillment = $order->fulfillments->first();
    expect($fulfillment)->not->toBeNull()
        ->and($fulfillment->status)->toBe(FulfillmentShipmentStatus::Delivered)
        ->and($fulfillment->shipped_at)->not->toBeNull();
});
