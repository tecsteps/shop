<?php

use App\Enums\CartStatus;
use App\Enums\ShippingRateType;
use App\Enums\VariantStatus;
use App\Events\OrderCreated;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CheckoutService;
use App\Services\OrderService;
use Illuminate\Support\Facades\Event;

function createOrderTestContext(array $overrides = []): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::factory()->for($store)->active()->create();
    $variant = ProductVariant::factory()->for($product)->create([
        'price_amount' => 5000,
        'sku' => 'TEST-SKU-001',
        'status' => VariantStatus::Active,
        'weight_g' => 500,
    ]);

    InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
    ]);

    $cart = Cart::factory()->for($store)->create(
        array_filter(['customer_id' => $overrides['customer_id'] ?? null])
    );
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

    return array_merge($ctx, [
        'product' => $product,
        'variant' => $variant,
        'cart' => $cart,
        'rate' => $rate,
    ]);
}

function completeOrderFromContext(array $ctx, string $paymentMethod = 'credit_card'): Order
{
    $service = app(CheckoutService::class);
    $checkout = $service->createCheckout($ctx['cart']);

    $checkout = $service->setAddress($checkout, [
        'email' => 'order-test@example.com',
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

    $checkout = $service->setShippingMethod($checkout, $ctx['rate']->id);
    $checkout = $service->selectPaymentMethod($checkout, $paymentMethod);

    return $service->completeCheckout($checkout, [
        'card_number' => '4242424242424242',
    ]);
}

it('creates an order from a completed checkout', function () {
    $ctx = createOrderTestContext();
    $order = completeOrderFromContext($ctx);

    expect($order)->toBeInstanceOf(Order::class)
        ->and($order->store_id)->toBe($ctx['store']->id)
        ->and($order->email)->toBe('order-test@example.com')
        ->and($order->order_number)->toStartWith('#');
});

it('generates sequential order numbers per store', function () {
    $ctx = createOrderTestContext();

    $orderService = app(OrderService::class);
    $store = $ctx['store'];

    $number1 = $orderService->generateOrderNumber($store);
    expect($number1)->toBe('#1001');

    // Create an order to advance the sequence
    Order::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'order_number' => '#1001',
        'payment_method' => 'credit_card',
        'status' => 'pending',
        'financial_status' => 'pending',
        'fulfillment_status' => 'unfulfilled',
        'email' => 'test@test.com',
    ]);

    $number2 = $orderService->generateOrderNumber($store);
    expect($number2)->toBe('#1002');

    Order::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'order_number' => '#1002',
        'payment_method' => 'credit_card',
        'status' => 'pending',
        'financial_status' => 'pending',
        'fulfillment_status' => 'unfulfilled',
        'email' => 'test@test.com',
    ]);

    $number3 = $orderService->generateOrderNumber($store);
    expect($number3)->toBe('#1003');
});

it('creates order lines with snapshots', function () {
    $ctx = createOrderTestContext();
    $order = completeOrderFromContext($ctx);

    $line = $order->lines()->first();

    expect($line)->not->toBeNull()
        ->and($line->title_snapshot)->toBe($ctx['product']->title)
        ->and($line->sku_snapshot)->toBe('TEST-SKU-001')
        ->and($line->quantity)->toBe(2)
        ->and($line->unit_price_amount)->toBe(5000);
});

it('commits inventory on order creation for credit card', function () {
    $ctx = createOrderTestContext();
    completeOrderFromContext($ctx, 'credit_card');

    $inventory = InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $ctx['variant']->id)
        ->first();

    // Started with 50. Reserved 2 during selectPaymentMethod, then committed 2 during order creation.
    // commit: quantity_on_hand -= 2, quantity_reserved -= 2
    expect($inventory->quantity_on_hand)->toBe(48)
        ->and($inventory->quantity_reserved)->toBe(0);
});

it('marks cart as converted', function () {
    $ctx = createOrderTestContext();
    completeOrderFromContext($ctx);

    $cart = Cart::withoutGlobalScopes()->find($ctx['cart']->id);

    expect($cart->status)->toBe(CartStatus::Converted);
});

it('dispatches OrderCreated event', function () {
    Event::fake([OrderCreated::class]);

    $ctx = createOrderTestContext();
    completeOrderFromContext($ctx);

    Event::assertDispatched(OrderCreated::class, function (OrderCreated $event) {
        return $event->order instanceof Order;
    });
});

it('preserves order data when product is deleted', function () {
    $ctx = createOrderTestContext();
    $order = completeOrderFromContext($ctx);

    $line = $order->lines()->first();
    $originalTitle = $line->title_snapshot;
    $originalSku = $line->sku_snapshot;

    // Delete the product (soft or hard)
    $ctx['product']->delete();

    // Reload the order line
    $line = $order->lines()->first();

    expect($line->title_snapshot)->toBe($originalTitle)
        ->and($line->sku_snapshot)->toBe($originalSku)
        ->and($line->product_id)->toBeNull();
});

it('links order to customer when authenticated', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $customer = Customer::factory()->create(['store_id' => $store->id]);

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

    $cart = Cart::factory()->for($store)->create([
        'customer_id' => $customer->id,
    ]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 5000,
        'line_subtotal_amount' => 5000,
        'line_total_amount' => 5000,
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
        'email' => $customer->email,
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
    $checkout = $service->selectPaymentMethod($checkout, 'credit_card');
    $order = $service->completeCheckout($checkout, [
        'card_number' => '4242424242424242',
    ]);

    expect($order->customer_id)->toBe($customer->id);
});
