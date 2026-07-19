<?php

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\RefundService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Create a paid credit card order for a variant with 10 on hand.
 *
 * @return array{0: Store, 1: ProductVariant, 2: Order, 3: Payment}
 */
function refundOrder(int $quantity = 2): array
{
    $store = test()->createStore();
    test()->bindStore($store);

    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->withInventory(10)->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
    ]);

    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->flat(499)->create(['zone_id' => $zone->id]);

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
    $checkout = $service->setShippingMethod($checkout, $rate->id);
    $checkout = $service->selectPaymentMethod($checkout, 'credit_card');

    $order = $service->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    return [$store, $variant, $order, $order->payments->first()];
}

test('creates a full refund', function () {
    [$store, $variant, $order, $payment] = refundOrder();

    Event::fake([OrderRefunded::class]);

    $refund = app(RefundService::class)->create($order, $payment, $order->total_amount, 'Customer changed mind');

    $order->refresh();
    expect($refund->status)->toBe(RefundStatus::Processed)
        ->and($refund->provider_refund_id)->toStartWith('mock_refund_')
        ->and($refund->amount)->toBe(5499)
        ->and($order->financial_status)->toBe(FinancialStatus::Refunded)
        ->and($order->status)->toBe(OrderStatus::Refunded)
        ->and($order->payments->first()->status)->toBe(PaymentStatus::Refunded)
        ->and($order->refundableAmount())->toBe(0);

    Event::assertDispatched(OrderRefunded::class, fn (OrderRefunded $event): bool => $event->order->id === $order->id && $event->refund->id === $refund->id);
});

test('creates a partial refund', function () {
    [$store, $variant, $order, $payment] = refundOrder();

    app(RefundService::class)->create($order, $payment, 2000);

    $order->refresh();
    expect($order->financial_status)->toBe(FinancialStatus::PartiallyRefunded)
        ->and($order->status)->toBe(OrderStatus::Paid)
        ->and($order->refundableAmount())->toBe(3499);
});

test('rejects refund exceeding payment amount', function () {
    [$store, $variant, $order, $payment] = refundOrder();

    app(RefundService::class)->create($order, $payment, $order->total_amount + 1);
})->throws(ValidationException::class);

test('rejects a second refund exceeding the remaining refundable amount', function () {
    [$store, $variant, $order, $payment] = refundOrder();
    $service = app(RefundService::class);

    $service->create($order, $payment, 5000);

    expect($order->refresh()->refundableAmount())->toBe(499);

    $service->create($order->refresh(), $payment, 500);
})->throws(ValidationException::class);

test('restocks inventory when restock flag is true', function () {
    [$store, $variant, $order, $payment] = refundOrder(2);

    expect($variant->inventoryItem->refresh()->quantity_on_hand)->toBe(8);

    app(RefundService::class)->create($order, $payment, 2000, null, true);

    expect($variant->inventoryItem->refresh()->quantity_on_hand)->toBe(10);
});

test('does not restock when restock flag is false', function () {
    [$store, $variant, $order, $payment] = refundOrder(2);

    app(RefundService::class)->create($order, $payment, 2000, null, false);

    expect($variant->inventoryItem->refresh()->quantity_on_hand)->toBe(8);
});

test('records refund reason', function () {
    [$store, $variant, $order, $payment] = refundOrder();

    $refund = app(RefundService::class)->create($order, $payment, 1000, 'Customer requested');

    expect($refund->reason)->toBe('Customer requested');
});

test('only allows admin or owner to process refunds', function () {
    [$store, $variant, $order, $payment] = refundOrder();

    $staff = test()->createUserWithRole($store, 'staff');
    $owner = test()->createUserWithRole($store, 'owner');

    expect(Gate::forUser($staff)->inspect('createRefund', $order)->denied())->toBeTrue()
        ->and(Gate::forUser($owner)->inspect('createRefund', $order)->allowed())->toBeTrue();
});
