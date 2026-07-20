<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Customer;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\ApiTokenService;

beforeEach(function () {
    $this->store = $this->createStore();
    $this->user = $this->createUserWithRole($this->store, 'owner');
    $this->tokens = app(ApiTokenService::class);
    $this->token = $this->tokens->create($this->user, 'API', ['read-orders', 'write-orders']);
});

function paidOrderWithLine(Tests\TestCase $test, int $quantity = 2, int $unitPrice = 2500): Order
{
    $order = Order::factory()->paid()->create([
        'store_id' => $test->store->id,
        'subtotal_amount' => $quantity * $unitPrice,
        'total_amount' => $quantity * $unitPrice,
    ]);

    OrderLine::factory()->create([
        'order_id' => $order->id,
        'quantity' => $quantity,
        'unit_price_amount' => $unitPrice,
        'total_amount' => $quantity * $unitPrice,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::Captured,
        'amount' => $order->total_amount,
    ]);

    return $order->refresh();
}

test('lists orders with authentication', function () {
    paidOrderWithLine($this);
    Order::factory()->create(['store_id' => $this->store->id]);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->getJson("/api/admin/v1/stores/{$this->store->id}/orders")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [['id', 'order_number', 'status', 'financial_status', 'fulfillment_status', 'customer', 'currency', 'total_amount', 'line_count', 'placed_at']],
            'meta' => ['current_page', 'per_page', 'total', 'last_page'],
        ])
        ->assertJsonPath('meta.total', 2);
});

test('filters orders by status', function () {
    paidOrderWithLine($this);
    Order::factory()->create(['store_id' => $this->store->id]); // pending

    $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->getJson("/api/admin/v1/stores/{$this->store->id}/orders?status=paid");

    $response->assertOk()->assertJsonCount(1, 'data');
    expect($response->json('data.0.status'))->toBe('paid');
});

test('filters orders by search query on customer email', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id, 'email' => 'jane@example.com']);
    Order::factory()->paid()->create(['store_id' => $this->store->id, 'customer_id' => $customer->id]);
    Order::factory()->paid()->create(['store_id' => $this->store->id, 'email' => 'guest@example.com']);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->getJson("/api/admin/v1/stores/{$this->store->id}/orders?query=jane@")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.customer.email', 'jane@example.com');
});

test('retrieves a single order with lines, payments and fulfillments', function () {
    $order = paidOrderWithLine($this);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->getJson("/api/admin/v1/stores/{$this->store->id}/orders/{$order->id}")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id', 'store_id', 'order_number', 'status', 'financial_status', 'fulfillment_status',
                'lines' => [['id', 'title_snapshot', 'quantity', 'unit_price_amount', 'total_amount']],
                'payments' => [['id', 'provider', 'method', 'status', 'amount', 'currency']],
                'fulfillments', 'refunds', 'placed_at', 'created_at', 'updated_at',
            ],
        ])
        ->assertJsonPath('data.id', $order->id)
        ->assertJsonPath('data.payments.0.status', 'captured');
});

test('creates a fulfillment via API', function () {
    $order = paidOrderWithLine($this, quantity: 2);
    $line = $order->lines->sole();

    $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson("/api/admin/v1/stores/{$this->store->id}/orders/{$order->id}/fulfillments", [
            'tracking_company' => 'DHL',
            'tracking_number' => '1234567890',
            'tracking_url' => 'https://www.dhl.com/track/1234567890',
            'line_items' => [['order_line_id' => $line->id, 'quantity' => 2]],
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.order_id', $order->id)
        ->assertJsonPath('data.status', 'shipped')
        ->assertJsonPath('data.tracking_number', '1234567890')
        ->assertJsonPath('data.line_items.0.order_line_id', $line->id)
        ->assertJsonPath('data.line_items.0.quantity', 2);

    expect(Fulfillment::query()->where('order_id', $order->id)->count())->toBe(1)
        ->and($order->fresh()->fulfillment_status)->toBe(FulfillmentOrderStatus::Fulfilled);
});

test('rejects fulfillment of a cancelled order with 409', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'status' => OrderStatus::Cancelled,
        'financial_status' => FinancialStatus::Paid,
    ]);
    $line = OrderLine::factory()->create(['order_id' => $order->id]);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson("/api/admin/v1/stores/{$this->store->id}/orders/{$order->id}/fulfillments", [
            'line_items' => [['order_line_id' => $line->id, 'quantity' => 1]],
        ])
        ->assertConflict();
});

test('rejects fulfillment exceeding the unfulfilled quantity with 422', function () {
    $order = paidOrderWithLine($this, quantity: 1);
    $line = $order->lines->sole();

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson("/api/admin/v1/stores/{$this->store->id}/orders/{$order->id}/fulfillments", [
            'line_items' => [['order_line_id' => $line->id, 'quantity' => 5]],
        ])
        ->assertUnprocessable();
});

test('creates a refund via API', function () {
    $order = paidOrderWithLine($this, quantity: 2, unitPrice: 2500);

    $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson("/api/admin/v1/stores/{$this->store->id}/orders/{$order->id}/refunds", [
            'amount' => 2500,
            'reason' => 'Customer requested return for 1 item',
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.order_id', $order->id)
        ->assertJsonPath('data.amount', 2500)
        ->assertJsonPath('data.reason', 'Customer requested return for 1 item')
        ->assertJsonPath('data.status', 'processed');

    $refund = Refund::query()->sole();

    expect($refund->status)->toBe(RefundStatus::Processed)
        ->and($refund->provider_refund_id)->not->toBeNull()
        ->and($order->fresh()->financial_status)->toBe(FinancialStatus::PartiallyRefunded);
});

test('rejects refund of an unpaid order with 409', function () {
    $order = Order::factory()->create(['store_id' => $this->store->id, 'total_amount' => 5000]);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson("/api/admin/v1/stores/{$this->store->id}/orders/{$order->id}/refunds", ['amount' => 100])
        ->assertConflict();
});

test('rejects refund exceeding the refundable amount with 422', function () {
    $order = paidOrderWithLine($this, quantity: 1, unitPrice: 1000);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->postJson("/api/admin/v1/stores/{$this->store->id}/orders/{$order->id}/refunds", ['amount' => 999999])
        ->assertUnprocessable();
});

test('requires write-orders ability for mutations', function () {
    $readOnly = $this->tokens->create($this->user, 'Read only', ['read-orders']);
    $order = paidOrderWithLine($this);
    $line = $order->lines->sole();

    $this->withHeader('Authorization', 'Bearer '.$readOnly)
        ->postJson("/api/admin/v1/stores/{$this->store->id}/orders/{$order->id}/fulfillments", [
            'line_items' => [['order_line_id' => $line->id, 'quantity' => 1]],
        ])
        ->assertForbidden();

    $this->withHeader('Authorization', 'Bearer '.$readOnly)
        ->postJson("/api/admin/v1/stores/{$this->store->id}/orders/{$order->id}/refunds", ['amount' => 100])
        ->assertForbidden();
});

test('scopes orders to the requested store', function () {
    $otherStore = $this->createStore();
    $otherOrder = Order::factory()->paid()->create(['store_id' => $otherStore->id]);

    $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->getJson("/api/admin/v1/stores/{$this->store->id}/orders/{$otherOrder->id}")
        ->assertNotFound();
});

test('exports orders as CSV', function () {
    $order = paidOrderWithLine($this, quantity: 2, unitPrice: 2500);
    Order::factory()->create(['store_id' => $this->store->id]);

    $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->get("/api/admin/v1/stores/{$this->store->id}/orders/export");

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toStartWith('text/csv')
        ->and($response->headers->get('Content-Disposition'))->toContain('attachment')
        ->toContain('orders-'.now()->format('Y-m-d').'.csv');

    $lines = array_values(array_filter(explode("\n", trim($response->getContent()))));

    expect($lines[0])->toBe('order_number,created_at,status,financial_status,fulfillment_status,customer_email,customer_name,subtotal_amount,discount_amount,shipping_amount,tax_amount,total_amount,currency,shipping_method,tracking_number')
        ->and($lines)->toHaveCount(3);

    $rows = array_map(fn (string $line): array => str_getcsv($line), array_slice($lines, 1));
    $paidRow = collect($rows)->firstWhere('0', $order->order_number);

    expect($paidRow)->not->toBeNull()
        ->and($paidRow[2])->toBe('paid')
        ->and((int) $paidRow[11])->toBe(5000)
        ->and($paidRow[12])->toBe('USD');
});

test('csv export respects the status filter', function () {
    paidOrderWithLine($this);
    Order::factory()->create(['store_id' => $this->store->id]); // pending

    $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
        ->get("/api/admin/v1/stores/{$this->store->id}/orders/export?status=paid");

    $lines = array_values(array_filter(explode("\n", trim($response->getContent()))));

    expect($lines)->toHaveCount(2); // header + 1 paid order
});

test('requires read-orders ability for export', function () {
    $noOrders = $this->tokens->create($this->user, 'Products only', ['read-products']);

    $this->withHeader('Authorization', 'Bearer '.$noOrders)
        ->getJson("/api/admin/v1/stores/{$this->store->id}/orders/export")
        ->assertForbidden();
});
