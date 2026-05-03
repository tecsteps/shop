<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Services\ApiTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    $this->seed();
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $this->otherStore = Store::query()->where('handle', 'acme-electronics')->firstOrFail();
    $this->user = User::query()->where('email', 'admin@example.com')->firstOrFail();
});

function adminOrderTokenFor($test, array $abilities): string
{
    return app(ApiTokenService::class)->create($test->store, $test->user, 'Order API test', $abilities)['plain_text_token'];
}

test('admin order api lists and filters orders with read token ability', function (): void {
    $url = route('api.admin.orders.index', $this->store);

    $this->getJson($url)->assertUnauthorized();

    $this->withToken(adminOrderTokenFor($this, ['write-orders']))
        ->getJson($url)
        ->assertForbidden();

    $this->withToken(adminOrderTokenFor($this, ['read-orders']))
        ->getJson($url.'?status=paid&query=1001&per_page=5')
        ->assertOk()
        ->assertJsonPath('data.0.order_number', '#1001')
        ->assertJsonPath('data.0.status', 'paid')
        ->assertJsonPath('data.0.store_id', $this->store->id)
        ->assertJsonPath('meta.per_page', 5)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'order_number',
                    'status',
                    'financial_status',
                    'fulfillment_status',
                    'customer',
                    'line_count',
                    'total_amount',
                ],
            ],
            'meta' => ['current_page', 'per_page', 'total', 'last_page'],
        ]);
});

test('admin order api retrieves full order details', function (): void {
    $order = Order::query()
        ->where('store_id', $this->store->id)
        ->where('order_number', '#1001')
        ->firstOrFail();

    $this->withToken(adminOrderTokenFor($this, ['read-orders']))
        ->getJson(route('api.admin.orders.show', [$this->store, $order]))
        ->assertOk()
        ->assertJsonPath('data.order_number', '#1001')
        ->assertJsonPath('data.lines.0.title_snapshot', 'Linen Shirt')
        ->assertJsonPath('data.payments.0.status', 'captured')
        ->assertJsonStructure([
            'data' => [
                'billing_address_json',
                'shipping_address_json',
                'lines',
                'payments',
                'fulfillments',
                'refunds',
            ],
        ]);
});

test('admin order api creates a shipped fulfillment', function (): void {
    $order = Order::query()
        ->where('store_id', $this->store->id)
        ->where('order_number', '#1001')
        ->with('lines')
        ->firstOrFail();
    $line = $order->lines->first();

    $this->withToken(adminOrderTokenFor($this, ['read-orders']))
        ->postJson(route('api.admin.orders.fulfillments.store', [$this->store, $order]), [
            'line_items' => [
                ['order_line_id' => $line->id, 'quantity' => $line->quantity],
            ],
        ])
        ->assertForbidden();

    $this->withToken(adminOrderTokenFor($this, ['write-orders']))
        ->postJson(route('api.admin.orders.fulfillments.store', [$this->store, $order]), [
            'tracking_company' => 'DHL',
            'tracking_number' => 'API123456789',
            'tracking_url' => 'https://tracking.test/API123456789',
            'line_items' => [
                ['order_line_id' => $line->id, 'quantity' => $line->quantity],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', FulfillmentShipmentStatus::Shipped->value)
        ->assertJsonPath('data.tracking_company', 'DHL')
        ->assertJsonPath('data.line_items.0.order_line_id', $line->id);

    expect($order->refresh()->status)->toBe(OrderStatus::Fulfilled)
        ->and($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($order->fulfillments()->firstOrFail()->status)->toBe(FulfillmentShipmentStatus::Shipped);
});

test('admin order api creates a refund against captured payments', function (): void {
    $order = Order::query()
        ->where('store_id', $this->store->id)
        ->where('order_number', '#1004')
        ->firstOrFail();

    $this->withToken(adminOrderTokenFor($this, ['write-orders']))
        ->postJson(route('api.admin.orders.refunds.store', [$this->store, $order]), [
            'amount' => 1000,
            'reason' => 'API test refund',
        ])
        ->assertCreated()
        ->assertJsonPath('data.amount', 1000)
        ->assertJsonPath('data.reason', 'API test refund')
        ->assertJsonPath('data.status', RefundStatus::Processed->value);

    expect($order->refresh()->financial_status)->toBe(FinancialStatus::PartiallyRefunded)
        ->and($order->refunds()->firstOrFail()->amount)->toBe(1000);
});

test('admin order api enforces store scoped token access and order lookup', function (): void {
    $otherStoreOrder = Order::query()
        ->where('store_id', $this->otherStore->id)
        ->firstOrFail();

    $this->withToken(adminOrderTokenFor($this, ['read-orders']))
        ->getJson(route('api.admin.orders.index', $this->otherStore))
        ->assertForbidden();

    $this->withToken(adminOrderTokenFor($this, ['read-orders']))
        ->getJson(route('api.admin.orders.show', [$this->store, $otherStoreOrder]))
        ->assertNotFound();
});
