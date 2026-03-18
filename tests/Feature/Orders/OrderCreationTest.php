<?php

use App\Enums\CartStatus;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\ShippingRateType;
use App\Enums\TaxMode;
use App\Enums\VariantStatus;
use App\Events\OrderCreated;
use App\Exceptions\PaymentFailedException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\Event;

function createOrderTestContext(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'title' => 'Order Test Product',
        'handle' => 'order-test-'.rand(1000, 9999),
        'status' => ProductStatus::Active,
        'published_at' => now(),
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'OTP-001',
        'price_amount' => 2500,
        'currency' => 'EUR',
        'is_default' => true,
        'position' => 0,
        'status' => VariantStatus::Active,
        'requires_shipping' => true,
        'weight_g' => 500,
    ]);

    InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    $cart = Cart::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => CartStatus::Active,
    ]);

    CartLine::create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 5000,
        'line_discount_amount' => 0,
        'line_total_amount' => 5000,
    ]);

    $zone = ShippingZone::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'name' => 'DE',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);

    $rate = ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Standard',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);

    TaxSettings::create([
        'store_id' => $store->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => ['tax_rate_basis_points' => 1900],
    ]);

    return array_merge($ctx, compact('product', 'variant', 'cart', 'zone', 'rate'));
}

function completeCheckoutForOrder(array $ctx, string $paymentMethod = 'credit_card'): \App\Models\Order
{
    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->createFromCart($ctx['cart']);

    $checkoutService->setAddress($checkout, [
        'email' => 'order@example.com',
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '456 Oak Ave',
            'city' => 'Berlin',
            'country' => 'DE',
            'postal_code' => '10115',
        ],
    ]);

    $checkoutService->setShippingMethod($checkout->fresh(), $ctx['rate']->id);
    $checkoutService->selectPaymentMethod($checkout->fresh(), $paymentMethod);

    $paymentData = $paymentMethod === 'credit_card'
        ? ['card_number' => '4242424242424242']
        : [];

    return $checkoutService->completeCheckout($checkout->fresh(), $paymentData);
}

it('creates order from checkout with credit card', function () {
    $ctx = createOrderTestContext();
    Event::fake([OrderCreated::class]);

    $order = completeCheckoutForOrder($ctx, 'credit_card');

    expect($order)->toBeInstanceOf(Order::class)
        ->and($order->status)->toBe(OrderStatus::Paid)
        ->and($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled)
        ->and($order->payment_method)->toBe(PaymentMethod::CreditCard)
        ->and($order->email)->toBe('order@example.com')
        ->and($order->order_number)->toBe('#1001');

    Event::assertDispatched(OrderCreated::class);
});

it('creates order from checkout with bank transfer', function () {
    $ctx = createOrderTestContext();

    $order = completeCheckoutForOrder($ctx, 'bank_transfer');

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->financial_status)->toBe(FinancialStatus::Pending)
        ->and($order->payment_method)->toBe(PaymentMethod::BankTransfer);

    $payment = $order->payments()->first();
    expect($payment->status)->toBe(PaymentStatus::Pending);
});

it('generates sequential order numbers', function () {
    $ctx = createOrderTestContext();
    $order1 = completeCheckoutForOrder($ctx, 'paypal');

    // Create a second order
    $cart2 = Cart::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => CartStatus::Active,
    ]);

    $variant2 = ProductVariant::create([
        'product_id' => $ctx['product']->id,
        'sku' => 'OTP-002',
        'price_amount' => 1000,
        'currency' => 'EUR',
        'is_default' => false,
        'position' => 1,
        'status' => VariantStatus::Active,
        'requires_shipping' => true,
    ]);

    InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'variant_id' => $variant2->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    CartLine::create([
        'cart_id' => $cart2->id,
        'variant_id' => $variant2->id,
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'line_subtotal_amount' => 1000,
        'line_discount_amount' => 0,
        'line_total_amount' => 1000,
    ]);

    $ctx2 = array_merge($ctx, ['cart' => $cart2]);
    $order2 = completeCheckoutForOrder($ctx2, 'paypal');

    expect($order1->order_number)->toBe('#1001')
        ->and($order2->order_number)->toBe('#1002');
});

it('creates order lines with snapshot data', function () {
    $ctx = createOrderTestContext();
    $order = completeCheckoutForOrder($ctx, 'credit_card');

    $lines = $order->lines;
    expect($lines)->toHaveCount(1);

    $line = $lines->first();
    expect($line->title_snapshot)->toBe('Order Test Product')
        ->and($line->sku_snapshot)->toBe('OTP-001')
        ->and($line->price_amount)->toBe(2500)
        ->and($line->quantity)->toBe(2)
        ->and($line->requires_shipping)->toBeTrue();
});

it('commits inventory on credit card payment', function () {
    $ctx = createOrderTestContext();
    completeCheckoutForOrder($ctx, 'credit_card');

    $item = $ctx['variant']->inventoryItem->fresh();
    expect($item->quantity_on_hand)->toBe(48)
        ->and($item->quantity_reserved)->toBe(0);
});

it('keeps inventory reserved for bank transfer', function () {
    $ctx = createOrderTestContext();
    completeCheckoutForOrder($ctx, 'bank_transfer');

    $item = $ctx['variant']->inventoryItem->fresh();
    expect($item->quantity_on_hand)->toBe(50)
        ->and($item->quantity_reserved)->toBe(2);
});

it('marks cart as converted after order creation', function () {
    $ctx = createOrderTestContext();
    completeCheckoutForOrder($ctx, 'credit_card');

    expect($ctx['cart']->fresh()->status)->toBe(CartStatus::Converted);
});

it('creates payment record with correct data', function () {
    $ctx = createOrderTestContext();
    $order = completeCheckoutForOrder($ctx, 'credit_card');

    $payment = $order->payments()->first();
    expect($payment->provider)->toBe('mock')
        ->and($payment->method)->toBe(PaymentMethod::CreditCard)
        ->and($payment->status)->toBe(PaymentStatus::Captured)
        ->and($payment->provider_payment_id)->toStartWith('mock_');
});

it('throws PaymentFailedException on decline', function () {
    $ctx = createOrderTestContext();
    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->createFromCart($ctx['cart']);

    $checkoutService->setAddress($checkout, [
        'email' => 'decline@example.com',
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '456 Oak Ave',
            'city' => 'Berlin',
            'country' => 'DE',
            'postal_code' => '10115',
        ],
    ]);

    $checkoutService->setShippingMethod($checkout->fresh(), $ctx['rate']->id);
    $checkoutService->selectPaymentMethod($checkout->fresh(), 'credit_card');

    expect(fn () => $checkoutService->completeCheckout($checkout->fresh(), [
        'card_number' => '4000000000000002',
    ]))->toThrow(PaymentFailedException::class);
});

it('releases inventory on payment decline', function () {
    $ctx = createOrderTestContext();
    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->createFromCart($ctx['cart']);

    $checkoutService->setAddress($checkout, [
        'email' => 'decline@example.com',
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '456 Oak Ave',
            'city' => 'Berlin',
            'country' => 'DE',
            'postal_code' => '10115',
        ],
    ]);

    $checkoutService->setShippingMethod($checkout->fresh(), $ctx['rate']->id);
    $checkoutService->selectPaymentMethod($checkout->fresh(), 'credit_card');

    try {
        $checkoutService->completeCheckout($checkout->fresh(), [
            'card_number' => '4000000000000002',
        ]);
    } catch (PaymentFailedException) {
        // expected
    }

    $item = $ctx['variant']->inventoryItem->fresh();
    expect($item->quantity_reserved)->toBe(0);
});
