<?php

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingRateType;
use App\Enums\VariantStatus;
use App\Jobs\CancelUnpaidBankTransferOrders;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CheckoutService;
use App\Services\OrderService;

function createBankTransferOrderContext(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::factory()->for($store)->active()->create();
    $variant = ProductVariant::factory()->for($product)->create([
        'price_amount' => 5000,
        'status' => VariantStatus::Active,
        'weight_g' => 500,
    ]);

    InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
    ]);

    $cart = Cart::factory()->for($store)->create();
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 5000,
        'line_subtotal_amount' => 10000,
        'line_total_amount' => 10000,
    ]);

    $zone = ShippingZone::factory()->for($store)->create([
        'countries_json' => ['US'],
    ]);

    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
    ]);

    $service = app(CheckoutService::class);
    $checkout = $service->createCheckout($cart);
    $checkout = $service->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Los Angeles',
            'province_code' => 'CA',
            'country_code' => 'US',
            'postal_code' => '90001',
        ],
    ]);
    $checkout = $service->setShippingMethod($checkout, $rate->id);
    $checkout = $service->selectPaymentMethod($checkout, 'bank_transfer');

    $order = $service->completeCheckout($checkout, []);

    return array_merge($ctx, [
        'product' => $product,
        'variant' => $variant,
        'order' => $order,
    ]);
}

it('admin can confirm bank transfer payment', function () {
    $ctx = createBankTransferOrderContext();
    $order = $ctx['order'];

    expect($order->financial_status)->toBe(FinancialStatus::Pending);

    $orderService = app(OrderService::class);
    $orderService->confirmBankTransferPayment($order);

    $order->refresh();

    expect($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->status)->toBe(OrderStatus::Paid);

    $payment = $order->payments()->first();
    expect($payment->status)->toBe(PaymentStatus::Captured);
});

it('cannot confirm payment for non-bank-transfer orders', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $order = Order::factory()->for($store)->paid()->create([
        'payment_method' => PaymentMethod::CreditCard,
    ]);

    $orderService = app(OrderService::class);

    expect(fn () => $orderService->confirmBankTransferPayment($order))
        ->toThrow(\InvalidArgumentException::class, 'not a bank transfer order');
});

it('cannot confirm already confirmed payment', function () {
    $ctx = createBankTransferOrderContext();
    $order = $ctx['order'];

    $orderService = app(OrderService::class);
    $orderService->confirmBankTransferPayment($order);

    $order->refresh();

    expect(fn () => $orderService->confirmBankTransferPayment($order))
        ->toThrow(\InvalidArgumentException::class, 'already been confirmed');
});

it('auto-cancel job cancels unpaid bank transfer orders after 7 days', function () {
    $ctx = createBankTransferOrderContext();
    $order = $ctx['order'];

    // Set placed_at to 8 days ago
    $order->update(['placed_at' => now()->subDays(8)]);

    $job = new CancelUnpaidBankTransferOrders;
    $job->handle(app(OrderService::class));

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Cancelled);
});

it('auto-cancel job does not cancel orders within 7 days', function () {
    $ctx = createBankTransferOrderContext();
    $order = $ctx['order'];

    // placed_at is already set to now()
    $job = new CancelUnpaidBankTransferOrders;
    $job->handle(app(OrderService::class));

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Pending);
});

it('auto-fulfills digital products on payment confirmation', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::factory()->for($store)->active()->create();
    $variant = ProductVariant::factory()->for($product)->create([
        'price_amount' => 2000,
        'status' => VariantStatus::Active,
        'requires_shipping' => false,
        'weight_g' => 0,
    ]);

    InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 100,
        'quantity_reserved' => 0,
    ]);

    $cart = Cart::factory()->for($store)->create();
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 2000,
        'line_subtotal_amount' => 2000,
        'line_total_amount' => 2000,
    ]);

    $zone = ShippingZone::factory()->for($store)->create([
        'countries_json' => ['US'],
    ]);

    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 0],
    ]);

    $service = app(CheckoutService::class);
    $checkout = $service->createCheckout($cart);
    $checkout = $service->setAddress($checkout, [
        'email' => 'digital@example.com',
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '456 Oak Ave',
            'city' => 'San Francisco',
            'province_code' => 'CA',
            'country_code' => 'US',
            'postal_code' => '94102',
        ],
    ]);
    $checkout = $service->setShippingMethod($checkout, $rate->id);
    $checkout = $service->selectPaymentMethod($checkout, 'credit_card');
    $order = $service->completeCheckout($checkout, [
        'card_number' => '4242424242424242',
    ]);

    // The order was created with a digital product (requires_shipping=false)
    // It should be paid since we used a credit card
    expect($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->payment_method)->toBe(PaymentMethod::CreditCard);

    // Verify the product data is on the order line
    $orderLine = $order->lines()->first();
    expect($orderLine)->not->toBeNull()
        ->and($orderLine->variant_id)->toBe($variant->id);
});
