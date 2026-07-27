<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Support\OrderToken;

/**
 * Absolute storefront API URL for the store's primary domain.
 */
function orderApiUrl(Store $store, string $path): string
{
    return 'http://'.$store->handle.'.test/api/storefront/v1'.$path;
}

/**
 * Create a paid order via the full checkout flow.
 *
 * @return array{0: Store, 1: Order}
 */
function placedOrder(?Store $store = null): array
{
    $store ??= test()->createStore();
    test()->bindStore($store);

    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->withInventory(10)->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
    ]);

    $zone = ShippingZone::query()->first()
        ?? ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::query()->where('zone_id', $zone->id)->first()
        ?? ShippingRate::factory()->flat(499)->create(['zone_id' => $zone->id]);

    $service = app(CheckoutService::class);
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, 2);

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

    return [$store, $order];
}

test('returns the order for a valid token', function () {
    [$store, $order] = placedOrder();

    $url = orderApiUrl($store, '/orders/'.urlencode($order->order_number).'?token='.OrderToken::for($order));

    $this->getJson($url)
        ->assertOk()
        ->assertJsonPath('order_number', $order->order_number)
        ->assertJsonPath('status', 'paid')
        ->assertJsonPath('financial_status', 'paid')
        ->assertJsonPath('fulfillment_status', 'unfulfilled')
        ->assertJsonPath('email', 'customer@example.com')
        ->assertJsonPath('totals.total_amount', 5499)
        ->assertJsonPath('totals.subtotal_amount', 5000)
        ->assertJsonPath('lines.0.quantity', 2)
        ->assertJsonPath('lines.0.unit_price_amount', 2500)
        ->assertJsonPath('shipping_address.city', 'Berlin')
        ->assertJsonStructure(['placed_at', 'lines', 'totals', 'shipping_address', 'fulfillments']);
});

test('rejects a missing token', function () {
    [$store, $order] = placedOrder();

    $this->getJson(orderApiUrl($store, '/orders/'.urlencode($order->order_number)))
        ->assertUnauthorized();
});

test('rejects an invalid token', function () {
    [$store, $order] = placedOrder();

    $this->getJson(orderApiUrl($store, '/orders/'.urlencode($order->order_number).'?token=forged-token'))
        ->assertUnauthorized();
});

test('returns 404 for an unknown order number', function () {
    [$store, $order] = placedOrder();

    $this->getJson(orderApiUrl($store, '/orders/'.urlencode('#9999').'?token=whatever'))
        ->assertNotFound();
});

test('does not leak orders across stores', function () {
    [$storeA] = placedOrder();
    [$storeB] = placedOrder();
    [, $orderB] = placedOrder($storeB); // second order in store B gets #1002

    // Store B's #1002 does not exist in store A (only #1001) — the store
    // scope must hide it even with a valid token for that order.
    $url = orderApiUrl($storeA, '/orders/'.urlencode($orderB->order_number).'?token='.OrderToken::for($orderB));

    $this->getJson($url)->assertNotFound();
});
