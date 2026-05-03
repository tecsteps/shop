<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingRateType;
use App\Events\FulfillmentDelivered;
use App\Events\FulfillmentShipped;
use App\Exceptions\FulfillmentGuardException;
use App\Exceptions\PaymentFailedException;
use App\Jobs\CancelUnpaidBankTransferOrders;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use App\Services\RefundService;
use Illuminate\Support\Facades\Event;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function phaseFiveCheckoutFixture(PaymentMethod $method = PaymentMethod::CreditCard, int $quantity = 2): array
{
    $store = Store::factory()->create(['default_currency' => 'EUR']);
    app()->instance('current_store', $store);

    $product = Product::factory()->for($store)->create();
    $variant = ProductVariant::factory()->for($product)->default()->create([
        'price_amount' => 1000,
        'currency' => 'EUR',
        'weight_g' => 250,
    ]);
    InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $variant->id)
        ->update(['quantity_on_hand' => 10, 'quantity_reserved' => 0, 'policy' => 'deny']);

    $zone = ShippingZone::factory()->for($store)->create(['countries_json' => ['DE']]);
    $rate = $zone->rates()->create([
        'name' => 'Standard',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 500],
        'is_active' => true,
    ]);

    TaxSettings::factory()->for($store)->create([
        'config_json' => ['default_rate_basis_points' => 1900, 'shipping_taxable' => true],
    ]);

    Discount::factory()->for($store)->create([
        'code' => 'WELCOME10',
        'type' => DiscountType::Code,
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'rules_json' => ['min_purchase_amount' => null],
    ]);

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, $quantity);

    $checkout = app(CheckoutService::class)->createFromCart($cart->refresh(), 'buyer@example.com');
    $checkout = app(CheckoutService::class)->setAddress($checkout, [
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => 'Street 1',
            'city' => 'Berlin',
            'country' => 'Germany',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
        'use_shipping_as_billing' => true,
    ]);
    $checkout = app(CheckoutService::class)->setShippingMethod($checkout, $rate->id);
    $checkout = app(CheckoutService::class)->selectPaymentMethod($checkout, $method);

    return [$store, $checkout->refresh(), $variant->refresh()];
}

test('order service creates a paid order from checkout idempotently and commits inventory', function () {
    [, $checkout, $variant] = phaseFiveCheckoutFixture();

    $order = app(OrderService::class)->createFromCheckout($checkout, [
        'card_number' => '4242424242424242',
    ]);
    $again = app(OrderService::class)->createFromCheckout($checkout->refresh(), [
        'card_number' => '4242424242424242',
    ]);

    expect($again->id)->toBe($order->id)
        ->and($order->order_number)->toBe('#1001')
        ->and($order->status)->toBe(OrderStatus::Paid)
        ->and($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->payments()->first()->status)->toBe(PaymentStatus::Captured)
        ->and($order->lines)->toHaveCount(1)
        ->and($checkout->refresh()->status)->toBe(CheckoutStatus::Completed)
        ->and($checkout->cart->refresh()->status)->toBe(CartStatus::Converted)
        ->and($variant->inventoryItem->refresh()->quantity_on_hand)->toBe(8)
        ->and($variant->inventoryItem->refresh()->quantity_reserved)->toBe(0);
});

test('declined card releases reserved inventory and leaves checkout retryable', function () {
    [, $checkout, $variant] = phaseFiveCheckoutFixture();

    expect(fn () => app(OrderService::class)->createFromCheckout($checkout, [
        'card_number' => '4000000000000002',
    ]))->toThrow(PaymentFailedException::class, 'Your card was declined.');

    expect($checkout->refresh()->status)->toBe(CheckoutStatus::ShippingSelected)
        ->and($checkout->payment_method)->toBeNull()
        ->and($checkout->order)->toBeNull()
        ->and($variant->inventoryItem->refresh()->quantity_on_hand)->toBe(10)
        ->and($variant->inventoryItem->refresh()->quantity_reserved)->toBe(0);
});

test('bank transfer orders stay pending until confirmation commits inventory', function () {
    [, $checkout, $variant] = phaseFiveCheckoutFixture(PaymentMethod::BankTransfer);

    $order = app(OrderService::class)->createFromCheckout($checkout);

    expect($order->payment_method)->toBe(PaymentMethod::BankTransfer)
        ->and($order->financial_status)->toBe(FinancialStatus::Pending)
        ->and($order->payments()->first()->status)->toBe(PaymentStatus::Pending)
        ->and($variant->inventoryItem->refresh()->quantity_on_hand)->toBe(10)
        ->and($variant->inventoryItem->refresh()->quantity_reserved)->toBe(2);

    $confirmed = app(OrderService::class)->confirmBankTransfer($order);

    expect($confirmed->financial_status)->toBe(FinancialStatus::Paid)
        ->and($confirmed->payments()->first()->status)->toBe(PaymentStatus::Captured)
        ->and($variant->inventoryItem->refresh()->quantity_on_hand)->toBe(8)
        ->and($variant->inventoryItem->refresh()->quantity_reserved)->toBe(0);
});

test('fulfillment guard blocks unpaid orders and marks paid orders fulfilled', function () {
    [, $checkout] = phaseFiveCheckoutFixture(PaymentMethod::BankTransfer);
    $order = app(OrderService::class)->createFromCheckout($checkout);

    expect(fn () => app(FulfillmentService::class)->create($order, [
        $order->lines()->first()->id => 2,
    ]))->toThrow(FulfillmentGuardException::class);

    $order = app(OrderService::class)->confirmBankTransfer($order);
    app(FulfillmentService::class)->create($order, [
        $order->lines()->first()->id => 2,
    ]);

    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($order->status)->toBe(OrderStatus::Fulfilled);
});

test('fulfillment service marks shipments shipped and delivered', function () {
    [, $checkout] = phaseFiveCheckoutFixture();
    $order = app(OrderService::class)->createFromCheckout($checkout, [
        'card_number' => '4242424242424242',
    ]);

    $fulfillment = app(FulfillmentService::class)->create($order, [
        $order->lines()->first()->id => 2,
    ], [
        'tracking_company' => 'DHL',
        'tracking_number' => 'DHL123456789',
        'tracking_url' => 'https://tracking.test/DHL123456789',
    ]);

    Event::fake([FulfillmentShipped::class, FulfillmentDelivered::class]);

    app(FulfillmentService::class)->markAsShipped($fulfillment);

    expect($fulfillment->refresh()->status)->toBe(FulfillmentShipmentStatus::Shipped)
        ->and($fulfillment->shipped_at)->not->toBeNull()
        ->and($fulfillment->tracking_company)->toBe('DHL')
        ->and($fulfillment->tracking_number)->toBe('DHL123456789');

    Event::assertDispatched(FulfillmentShipped::class, fn (FulfillmentShipped $event): bool => $event->fulfillment->is($fulfillment));

    app(FulfillmentService::class)->markAsDelivered($fulfillment);

    expect($fulfillment->refresh()->status)->toBe(FulfillmentShipmentStatus::Delivered)
        ->and($fulfillment->delivered_at)->not->toBeNull();

    Event::assertDispatched(FulfillmentDelivered::class, fn (FulfillmentDelivered $event): bool => $event->fulfillment->is($fulfillment));
});

test('refund service updates financial status and can restock inventory', function () {
    [, $checkout, $variant] = phaseFiveCheckoutFixture();
    $order = app(OrderService::class)->createFromCheckout($checkout, [
        'card_number' => '4242424242424242',
    ]);

    app(RefundService::class)->create($order, $order->payments()->first(), $order->total_amount, 'Customer request', true);

    expect($order->refresh()->financial_status)->toBe(FinancialStatus::Refunded)
        ->and($order->status)->toBe(OrderStatus::Refunded)
        ->and($order->payments()->first()->status)->toBe(PaymentStatus::Refunded)
        ->and($variant->inventoryItem->refresh()->quantity_on_hand)->toBe(10);
});

test('refund service refunds selected line quantities and restocks selected quantity', function () {
    [, $checkout, $variant] = phaseFiveCheckoutFixture();
    $order = app(OrderService::class)->createFromCheckout($checkout, [
        'card_number' => '4242424242424242',
    ]);
    $line = $order->lines()->firstOrFail();

    $refund = app(RefundService::class)->createForLines($order, $order->payments()->first(), [
        $line->id => 1,
    ], 'Line item return', true);

    expect($refund->amount)->toBe(1000)
        ->and($refund->reason)->toBe('Line item return')
        ->and($order->refresh()->financial_status)->toBe(FinancialStatus::PartiallyRefunded)
        ->and($variant->inventoryItem->refresh()->quantity_on_hand)->toBe(9);
});

test('bank transfer cancellation job voids stale pending orders and releases reservations', function () {
    [, $checkout, $variant] = phaseFiveCheckoutFixture(PaymentMethod::BankTransfer);
    $order = app(OrderService::class)->createFromCheckout($checkout);
    $order->forceFill(['placed_at' => now()->subDays(8)])->save();

    app(CancelUnpaidBankTransferOrders::class)->handle(app(OrderService::class));

    expect($order->refresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($order->financial_status)->toBe(FinancialStatus::Voided)
        ->and($order->payments()->first()->status)->toBe(PaymentStatus::Failed)
        ->and($variant->inventoryItem->refresh()->quantity_reserved)->toBe(0);
});
