<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentOrderStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\CheckoutCompleted;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\Event;

/**
 * Store with variant (2500), DE zone with flat rate 499.
 *
 * @return array{0: Store, 1: ProductVariant, 2: ShippingRate}
 */
function orderSetup(bool $digital = false): array
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

function orderAddress(): array
{
    return [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'address1' => '123 Main St',
        'city' => 'Berlin',
        'country' => 'DE',
        'country_code' => 'DE',
        'postal_code' => '10115',
    ];
}

/**
 * Drive a checkout to payment_selected via the full state machine.
 */
function orderCheckout(Store $store, ProductVariant $variant, ?ShippingRate $rate, string $method = 'credit_card', int $quantity = 2, ?Customer $customer = null, string $email = 'customer@example.com'): Checkout
{
    $service = app(CheckoutService::class);

    $cart = app(CartService::class)->create($store, $customer);
    app(CartService::class)->addLine($cart, $variant->id, $quantity);

    $checkout = $service->createFromCart($cart->refresh(), $email, $customer);
    $checkout = $service->setAddress($checkout, ['shipping_address' => orderAddress()]);
    $checkout = $service->setShippingMethod($checkout, $rate?->id);

    return $service->selectPaymentMethod($checkout, $method);
}

test('creates an order from a completed checkout', function () {
    [$store, $variant, $rate] = orderSetup();
    $checkout = orderCheckout($store, $variant, $rate);

    $order = app(CheckoutService::class)->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->fulfillment_status)->toBe(FulfillmentOrderStatus::Unfulfilled)
        ->and($order->payment_method)->toBe(PaymentMethod::CreditCard)
        ->and($order->subtotal_amount)->toBe(5000)
        ->and($order->shipping_amount)->toBe(499)
        ->and($order->tax_amount)->toBe(0)
        ->and($order->total_amount)->toBe(5499)
        ->and($order->order_number)->toBe('#1001')
        ->and($order->placed_at)->not->toBeNull()
        ->and($order->shipping_address_json['city'])->toBe('Berlin')
        ->and($order->isPaid())->toBeTrue();

    $payment = $order->payments->first();
    expect($payment->provider)->toBe('mock')
        ->and($payment->status)->toBe(PaymentStatus::Captured)
        ->and($payment->amount)->toBe(5499);
});

test('generates sequential order numbers per store', function () {
    [$store, $variant, $rate] = orderSetup();
    $service = app(CheckoutService::class);

    $numbers = [];
    foreach (range(1, 3) as $i) {
        $checkout = orderCheckout($store, $variant, $rate, 'credit_card', 1);
        $numbers[] = $service->completeCheckout($checkout, ['card_number' => '4242424242424242'])->order_number;
    }

    expect($numbers)->toBe(['#1001', '#1002', '#1003']);

    // A second store gets its own sequence.
    $otherStore = test()->createStore();
    test()->bindStore($otherStore);

    $otherProduct = Product::factory()->active()->create(['store_id' => $otherStore->id]);
    $otherVariant = ProductVariant::factory()->withInventory(10)->create([
        'product_id' => $otherProduct->id,
        'price_amount' => 1000,
    ]);
    $otherZone = ShippingZone::factory()->create(['store_id' => $otherStore->id, 'countries_json' => ['DE']]);
    $otherRate = ShippingRate::factory()->flat(100)->create(['zone_id' => $otherZone->id]);

    expect(app(App\Services\OrderService::class)->generateOrderNumber($otherStore))->toBe('#1001');

    $otherCheckout = orderCheckout($otherStore, $otherVariant, $otherRate, 'credit_card', 1);
    $otherOrder = $service->completeCheckout($otherCheckout, ['card_number' => '4242424242424242']);

    expect($otherOrder->order_number)->toBe('#1001');
});

test('creates order lines with snapshot data', function () {
    [$store, $variant, $rate] = orderSetup();

    $option = ProductOption::factory()->create(['product_id' => $variant->product_id, 'name' => 'Color']);
    $value = ProductOptionValue::factory()->create(['product_option_id' => $option->id, 'value' => 'Blue']);
    $variant->optionValues()->attach($value->id);

    $checkout = orderCheckout($store, $variant, $rate);
    $order = app(CheckoutService::class)->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    $line = $order->lines->first();

    expect($line->title_snapshot)->toBe($variant->product->title.' - Blue')
        ->and($line->sku_snapshot)->toBe($variant->sku)
        ->and($line->product_id)->toBe($variant->product_id)
        ->and($line->variant_id)->toBe($variant->id)
        ->and($line->quantity)->toBe(2)
        ->and($line->unit_price_amount)->toBe(2500)
        ->and($line->total_amount)->toBe(5000)
        ->and($line->tax_lines_json)->toBe([])
        ->and($line->discount_allocations_json)->toBe([]);
});

test('commits inventory on order creation for instant capture methods', function () {
    [$store, $variant, $rate] = orderSetup();
    $checkout = orderCheckout($store, $variant, $rate, 'credit_card', 3);

    expect($variant->inventoryItem->refresh()->quantity_reserved)->toBe(3);

    app(CheckoutService::class)->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    $item = $variant->inventoryItem->refresh();
    expect($item->quantity_on_hand)->toBe(7)
        ->and($item->quantity_reserved)->toBe(0);
});

test('keeps inventory reserved for bank transfer orders', function () {
    [$store, $variant, $rate] = orderSetup();
    $checkout = orderCheckout($store, $variant, $rate, 'bank_transfer', 3);

    $order = app(CheckoutService::class)->completeCheckout($checkout, ['payment_method' => 'bank_transfer']);

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->financial_status)->toBe(FinancialStatus::Pending);

    $item = $variant->inventoryItem->refresh();
    expect($item->quantity_on_hand)->toBe(10)
        ->and($item->quantity_reserved)->toBe(3);
});

test('marks cart as converted and checkout as completed', function () {
    [$store, $variant, $rate] = orderSetup();
    $checkout = orderCheckout($store, $variant, $rate);

    app(CheckoutService::class)->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($checkout->refresh()->status)->toBe(CheckoutStatus::Completed)
        ->and($checkout->cart->refresh()->status)->toBe(CartStatus::Converted);
});

test('dispatches order lifecycle events', function () {
    [$store, $variant, $rate] = orderSetup();
    $checkout = orderCheckout($store, $variant, $rate);

    Event::fake([OrderCreated::class, CheckoutCompleted::class, OrderPaid::class]);

    $order = app(CheckoutService::class)->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    Event::assertDispatched(OrderCreated::class, fn (OrderCreated $event): bool => $event->order->id === $order->id);
    Event::assertDispatched(CheckoutCompleted::class, fn (CheckoutCompleted $event): bool => $event->checkout->id === $checkout->id);
    Event::assertDispatched(OrderPaid::class, fn (OrderPaid $event): bool => $event->order->id === $order->id);
});

test('preserves order data when the variant is deleted', function () {
    [$store, $variant, $rate] = orderSetup();
    $checkout = orderCheckout($store, $variant, $rate);

    $order = app(CheckoutService::class)->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    $variantSku = $variant->sku;
    $variant->delete();

    $line = $order->lines()->first();
    expect($line->variant_id)->toBeNull()
        ->and($line->sku_snapshot)->toBe($variantSku)
        ->and($line->title_snapshot)->not->toBeEmpty()
        ->and($order->refresh()->isDigital())->toBeFalse();
});

test('links guest order to a customer by email', function () {
    [$store, $variant, $rate] = orderSetup();
    $checkout = orderCheckout($store, $variant, $rate);

    $order = app(CheckoutService::class)->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    $customer = Customer::withoutGlobalScopes()->where('store_id', $store->id)->where('email', 'customer@example.com')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->password_hash)->toBeNull()
        ->and($order->refresh()->customer_id)->toBe($customer->id);

    // A second order with the same email reuses the customer.
    $second = orderCheckout($store, $variant, $rate);
    $secondOrder = app(CheckoutService::class)->completeCheckout($second, ['card_number' => '4242424242424242']);

    expect($secondOrder->customer_id)->toBe($customer->id)
        ->and(Customer::withoutGlobalScopes()->where('store_id', $store->id)->count())->toBe(1);
});

test('links order to the authenticated customer', function () {
    [$store, $variant, $rate] = orderSetup();
    $customer = Customer::factory()->create(['store_id' => $store->id]);

    $checkout = orderCheckout($store, $variant, $rate, 'credit_card', 2, $customer);
    $order = app(CheckoutService::class)->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($order->customer_id)->toBe($customer->id)
        ->and(Customer::withoutGlobalScopes()->where('store_id', $store->id)->count())->toBe(1);
});

test('sets email from checkout on the order', function () {
    [$store, $variant, $rate] = orderSetup();
    $checkout = orderCheckout($store, $variant, $rate, 'credit_card', 2, null, 'guest@example.com');

    $order = app(CheckoutService::class)->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($order->email)->toBe('guest@example.com');
});

test('prevents duplicate orders from same checkout', function () {
    [$store, $variant, $rate] = orderSetup();
    $checkout = orderCheckout($store, $variant, $rate);

    $service = app(CheckoutService::class);
    $first = $service->completeCheckout($checkout, ['card_number' => '4242424242424242']);
    $second = $service->completeCheckout($checkout->refresh(), ['card_number' => '4242424242424242']);

    expect($second->id)->toBe($first->id)
        ->and(Order::withoutGlobalScopes()->count())->toBe(1);
});

test('increments usage count on order completion', function () {
    [$store, $variant, $rate] = orderSetup();
    $discount = Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'SAVE10',
        'usage_count' => 5,
    ]);

    $checkout = orderCheckout($store, $variant, $rate);

    $service = app(CheckoutService::class);
    $result = $service->applyDiscount($checkout, 'SAVE10');
    expect($result->valid)->toBeTrue();

    $order = $service->completeCheckout($checkout->refresh(), ['card_number' => '4242424242424242']);

    expect($discount->refresh()->usage_count)->toBe(6)
        ->and($order->discount_amount)->toBe(500);

    $line = $order->lines->first();
    expect($line->discount_allocations_json)->toBe([['discount_id' => $discount->id, 'amount' => 500]]);
});

test('completes full checkout happy path', function () {
    [$store, $variant, $rate] = orderSetup();
    $service = app(CheckoutService::class);

    // create cart -> add lines -> create checkout -> set address -> select shipping -> begin payment -> complete
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, 2);

    $checkout = $service->createFromCart($cart->refresh(), 'customer@example.com');
    $checkout = $service->setAddress($checkout, ['shipping_address' => orderAddress()]);
    $checkout = $service->setShippingMethod($checkout, $rate->id);
    $checkout = $service->selectPaymentMethod($checkout, 'credit_card');

    $order = $service->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($order)->toBeInstanceOf(Order::class)
        ->and($cart->refresh()->status)->toBe(CartStatus::Converted)
        ->and($variant->inventoryItem->refresh()->quantity_on_hand)->toBe(8)
        ->and($variant->inventoryItem->quantity_reserved)->toBe(0);
});

test('auto-fulfills digital products on instant capture', function () {
    [$store, $variant] = orderSetup(digital: true);
    $checkout = orderCheckout($store, $variant, null);

    $order = app(CheckoutService::class)->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentOrderStatus::Fulfilled)
        ->and($order->status)->toBe(OrderStatus::Fulfilled);

    $fulfillment = $order->fulfillments->first();
    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Delivered)
        ->and($fulfillment->shipped_at)->not->toBeNull()
        ->and($fulfillment->lines)->toHaveCount(1);
});
