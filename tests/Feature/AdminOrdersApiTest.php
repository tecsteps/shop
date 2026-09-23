<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Str;

function ordersApiToken(User $user, Store $store): string
{
    $plainTextToken = 'shop_'.Str::random(64);
    $user->tokens()->create([
        'store_id' => $store->getKey(),
        'name' => 'orders-api-test',
        'token' => hash('sha256', $plainTextToken),
        'abilities' => ['read-orders', 'write-orders'],
        'expires_at' => now()->addHour(),
    ]);

    return $plainTextToken;
}

/** @return array{order: Order, customer: Customer, line: OrderLine} */
function makeOrdersApiOrder(Store $store, string $number, string $email, string $status = 'paid'): array
{
    $customer = Customer::factory()->create(['store_id' => $store->getKey(), 'email' => $email]);
    $order = Order::query()->create([
        'store_id' => $store->getKey(),
        'customer_id' => $customer->getKey(),
        'order_number' => $number,
        'payment_method' => 'credit_card',
        'status' => $status,
        'financial_status' => $status === 'pending' ? 'pending' : 'paid',
        'fulfillment_status' => 'unfulfilled',
        'currency' => $store->default_currency,
        'subtotal_amount' => 5000,
        'total_amount' => 5000,
        'email' => $email,
        'billing_address_json' => ['first_name' => 'Jane', 'country_code' => 'DE'],
        'shipping_address_json' => ['first_name' => 'Jane', 'country_code' => 'DE'],
        'placed_at' => now()->subDay(),
    ]);
    $line = $order->lines()->create([
        'title_snapshot' => 'Cotton shirt',
        'sku_snapshot' => 'COTTON-01',
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
        'tax_lines_json' => [['rate' => 1900, 'amount' => 950, 'jurisdiction' => 'DE']],
        'discount_allocations_json' => [['code' => 'HELLO', 'amount' => 500]],
    ]);

    return compact('order', 'customer', 'line');
}

it('filters and returns documented order list and detail resources', function () {
    $store = shopStore();
    $orderData = makeOrdersApiOrder($store, '#API-1001', 'jane@example.test');
    makeOrdersApiOrder($store, '#API-1002', 'other@example.test', 'pending');
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $token = ordersApiToken($user, $store);
    $baseUrl = "/api/admin/v1/stores/{$store->id}/orders";

    $this->withToken($token)->getJson("{$baseUrl}?status=paid&financial_status=paid&fulfillment_status=unfulfilled&customer_id={$orderData['customer']->id}&query=jane@example.test&sort=total_desc&page=1&per_page=1")
        ->assertOk()
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.order_number', '#API-1001')
        ->assertJsonPath('data.0.customer.email', 'jane@example.test')
        ->assertJsonPath('data.0.line_count', 1);

    $this->withToken($token)->getJson("{$baseUrl}/{$orderData['order']->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $orderData['order']->id)
        ->assertJsonPath('data.lines.0.sku_snapshot', 'COTTON-01')
        ->assertJsonPath('data.lines.0.tax_lines_json.0.rate', 1900)
        ->assertJsonPath('data.customer.id', $orderData['customer']->id)
        ->assertJsonPath('data.payments', [])
        ->assertJsonPath('data.fulfillments', [])
        ->assertJsonPath('data.refunds', []);
});

it('creates a shipped partial fulfillment and a documented partial refund', function () {
    $store = shopStore();
    $orderData = makeOrdersApiOrder($store, '#API-FULFILL-1', 'fulfill@example.test');
    Payment::query()->create([
        'order_id' => $orderData['order']->id,
        'provider' => 'mock',
        'method' => 'credit_card',
        'provider_payment_id' => 'mock-api-payment',
        'status' => 'captured',
        'amount' => 5000,
        'currency' => 'EUR',
        'created_at' => now(),
    ]);
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $token = ordersApiToken($user, $store);
    $baseUrl = "/api/admin/v1/stores/{$store->id}/orders/{$orderData['order']->id}";

    $this->withToken($token)->postJson("{$baseUrl}/fulfillments", [
        'tracking_company' => 'DHL',
        'tracking_number' => 'TRACK-123',
        'tracking_url' => 'https://www.dhl.com/track/TRACK-123',
        'line_items' => [['order_line_id' => $orderData['line']->id, 'quantity' => 1]],
        'notify_customer' => false,
    ])
        ->assertCreated()
        ->assertJsonPath('data.order_id', $orderData['order']->id)
        ->assertJsonPath('data.status', 'shipped')
        ->assertJsonPath('data.line_items.0.order_line_id', $orderData['line']->id)
        ->assertJsonPath('data.line_items.0.quantity', 1);

    $this->withToken($token)->postJson("{$baseUrl}/refunds", [
        'amount' => 2500,
        'reason' => 'Return one item',
        'line_items' => [['order_line_id' => $orderData['line']->id, 'quantity' => 1]],
        'notify_customer' => false,
    ])
        ->assertCreated()
        ->assertJsonPath('data.order_id', $orderData['order']->id)
        ->assertJsonPath('data.amount', 2500)
        ->assertJsonPath('data.status', 'completed');

    $this->withToken($token)->getJson($baseUrl)
        ->assertOk()
        ->assertJsonPath('data.fulfillments.0.status', 'shipped')
        ->assertJsonPath('data.refunds.0.status', 'completed')
        ->assertJsonPath('data.refunds.0.line_items.0.quantity', 1)
        ->assertJsonPath('data.payments.0.status', 'partially_refunded');

    $this->withToken($token)->postJson("{$baseUrl}/refunds", [
        'amount' => 3000,
        'reason' => 'Too much',
    ])->assertUnprocessable()->assertInvalid(['amount']);
});

it('rejects foreign order lines and returns conflict for an unpayable fulfillment', function () {
    $store = shopStore();
    $pending = makeOrdersApiOrder($store, '#API-PENDING-1', 'pending@example.test', 'pending');
    $other = makeOrdersApiOrder($store, '#API-OTHER-1', 'other-line@example.test');
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $token = ordersApiToken($user, $store);
    $baseUrl = "/api/admin/v1/stores/{$store->id}/orders/{$pending['order']->id}/fulfillments";

    $this->withToken($token)->postJson($baseUrl, [
        'line_items' => [['order_line_id' => $other['line']->id, 'quantity' => 1]],
    ])->assertUnprocessable()->assertInvalid(['line_items.0.order_line_id']);

    $this->withToken($token)->postJson($baseUrl, [
        'line_items' => [['order_line_id' => $pending['line']->id, 'quantity' => 1]],
    ])->assertConflict();
});
