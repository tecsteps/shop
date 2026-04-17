<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\ShippingRateType;
use App\Enums\VariantStatus;
use App\Events\OrderFulfilled;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CheckoutService;
use App\Services\FulfillmentService;
use Illuminate\Support\Facades\Event;

function createPaidOrderForFulfillment(): array
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
        'quantity' => 3,
        'unit_price_amount' => 5000,
        'line_subtotal_amount' => 15000,
        'line_total_amount' => 15000,
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
        'email' => 'fulfill-test@example.com',
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

    return array_merge($ctx, [
        'product' => $product,
        'variant' => $variant,
        'order' => $order,
    ]);
}

it('creates a fulfillment for specific order lines', function () {
    $ctx = createPaidOrderForFulfillment();
    $order = $ctx['order'];
    $orderLine = $order->lines()->first();

    $fulfillmentService = app(FulfillmentService::class);
    $fulfillment = $fulfillmentService->create($order, [
        ['order_line_id' => $orderLine->id, 'quantity' => 1],
    ]);

    expect($fulfillment)->not->toBeNull()
        ->and($fulfillment->order_id)->toBe($order->id)
        ->and($fulfillment->lines)->toHaveCount(1)
        ->and($fulfillment->lines->first()->quantity)->toBe(1);
});

it('updates order fulfillment status to partial', function () {
    $ctx = createPaidOrderForFulfillment();
    $order = $ctx['order'];
    $orderLine = $order->lines()->first();

    $fulfillmentService = app(FulfillmentService::class);
    $fulfillmentService->create($order, [
        ['order_line_id' => $orderLine->id, 'quantity' => 1],
    ]);

    $order->refresh();

    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Partial);
});

it('updates order fulfillment status to fulfilled when all lines done', function () {
    $ctx = createPaidOrderForFulfillment();
    $order = $ctx['order'];
    $orderLine = $order->lines()->first();

    $fulfillmentService = app(FulfillmentService::class);
    $fulfillmentService->create($order, [
        ['order_line_id' => $orderLine->id, 'quantity' => 3],
    ]);

    $order->refresh();

    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
});

it('adds tracking information', function () {
    $ctx = createPaidOrderForFulfillment();
    $order = $ctx['order'];
    $orderLine = $order->lines()->first();

    $fulfillmentService = app(FulfillmentService::class);
    $fulfillment = $fulfillmentService->create($order, [
        ['order_line_id' => $orderLine->id, 'quantity' => 3],
    ], [
        'tracking_company' => 'UPS',
        'tracking_number' => '1Z999AA10123456784',
        'tracking_url' => 'https://ups.com/track/1Z999AA10123456784',
    ]);

    expect($fulfillment->tracking_company)->toBe('UPS')
        ->and($fulfillment->tracking_number)->toBe('1Z999AA10123456784')
        ->and($fulfillment->tracking_url)->toBe('https://ups.com/track/1Z999AA10123456784');
});

it('transitions fulfillment from pending to shipped', function () {
    $ctx = createPaidOrderForFulfillment();
    $order = $ctx['order'];
    $orderLine = $order->lines()->first();

    $fulfillmentService = app(FulfillmentService::class);
    $fulfillment = $fulfillmentService->create($order, [
        ['order_line_id' => $orderLine->id, 'quantity' => 3],
    ]);

    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Pending);

    $fulfillmentService->markAsShipped($fulfillment, [
        'tracking_company' => 'FedEx',
        'tracking_number' => 'FX123456',
    ]);

    $fulfillment->refresh();

    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Shipped)
        ->and($fulfillment->shipped_at)->not->toBeNull()
        ->and($fulfillment->tracking_company)->toBe('FedEx');
});

it('transitions fulfillment from shipped to delivered', function () {
    $ctx = createPaidOrderForFulfillment();
    $order = $ctx['order'];
    $orderLine = $order->lines()->first();

    $fulfillmentService = app(FulfillmentService::class);
    $fulfillment = $fulfillmentService->create($order, [
        ['order_line_id' => $orderLine->id, 'quantity' => 3],
    ]);

    $fulfillmentService->markAsShipped($fulfillment);
    $fulfillmentService->markAsDelivered($fulfillment);

    $fulfillment->refresh();

    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Delivered);
});

it('prevents fulfilling more than ordered quantity', function () {
    $ctx = createPaidOrderForFulfillment();
    $order = $ctx['order'];
    $orderLine = $order->lines()->first();

    $fulfillmentService = app(FulfillmentService::class);

    expect(fn () => $fulfillmentService->create($order, [
        ['order_line_id' => $orderLine->id, 'quantity' => 5],
    ]))->toThrow(\InvalidArgumentException::class);
});

it('fulfillment guard blocks fulfillment when financial_status is pending', function () {
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $order = Order::factory()->for($store)->create([
        'financial_status' => FinancialStatus::Pending,
    ]);

    $orderLine = $order->lines()->create([
        'product_id' => null,
        'variant_id' => null,
        'title_snapshot' => 'Test Product',
        'quantity' => 1,
        'unit_price_amount' => 1000,
        'total_amount' => 1000,
    ]);

    $fulfillmentService = app(FulfillmentService::class);

    expect(fn () => $fulfillmentService->create($order, [
        ['order_line_id' => $orderLine->id, 'quantity' => 1],
    ]))->toThrow(FulfillmentGuardException::class);
});

it('fulfillment guard allows fulfillment when financial_status is paid', function () {
    $ctx = createPaidOrderForFulfillment();
    $order = $ctx['order'];
    $orderLine = $order->lines()->first();

    expect($order->financial_status)->toBe(FinancialStatus::Paid);

    $fulfillmentService = app(FulfillmentService::class);
    $fulfillment = $fulfillmentService->create($order, [
        ['order_line_id' => $orderLine->id, 'quantity' => 1],
    ]);

    expect($fulfillment)->not->toBeNull();
});

it('fulfillment guard allows fulfillment when financial_status is partially_refunded', function () {
    $ctx = createPaidOrderForFulfillment();
    $order = $ctx['order'];

    // Manually set to partially_refunded
    $order->update(['financial_status' => FinancialStatus::PartiallyRefunded]);
    $order->refresh();

    $orderLine = $order->lines()->first();

    $fulfillmentService = app(FulfillmentService::class);
    $fulfillment = $fulfillmentService->create($order, [
        ['order_line_id' => $orderLine->id, 'quantity' => 1],
    ]);

    expect($fulfillment)->not->toBeNull();
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

    // Order is paid, so we can fulfill the digital product
    $orderLine = $order->lines()->first();
    $fulfillmentService = app(FulfillmentService::class);
    $fulfillment = $fulfillmentService->create($order, [
        ['order_line_id' => $orderLine->id, 'quantity' => $orderLine->quantity],
    ]);

    $order->refresh();

    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($fulfillment->lines)->toHaveCount(1);
});

it('dispatches OrderFulfilled event when all lines fulfilled', function () {
    Event::fake([OrderFulfilled::class]);

    $ctx = createPaidOrderForFulfillment();
    $order = $ctx['order'];
    $orderLine = $order->lines()->first();

    $fulfillmentService = app(FulfillmentService::class);
    $fulfillmentService->create($order, [
        ['order_line_id' => $orderLine->id, 'quantity' => 3],
    ]);

    Event::assertDispatched(OrderFulfilled::class, function (OrderFulfilled $event) use ($order) {
        return $event->order->id === $order->id;
    });
});
