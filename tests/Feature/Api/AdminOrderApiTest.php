<?php

use App\Enums\PaymentMethod;
use App\Services\CheckoutService;
use Tests\Support\CatalogTestHelper;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(Illuminate\Routing\Middleware\ThrottleRequests::class);
});

function adminOrderContext(string $method = 'credit_card'): array
{
    ['store' => $store, 'user' => $user] = test()->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);
    $rate = CommerceTestHelper::flatShippingRate($store, ['DE'], 0);

    $paymentMethod = App\Enums\PaymentMethod::from($method);

    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->createFromCart($cart, 'customer@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $rate->id);
    $checkout = $checkouts->selectPaymentMethod($checkout, $paymentMethod);
    $order = $checkouts->pay($checkout, $paymentMethod, ['card_number' => '4242424242424242']);

    return ['store' => $store, 'user' => $user, 'order' => $order];
}

it('lists orders with authentication', function () {
    $ctx = adminOrderContext();

    $response = $this->getJson(
        "/api/admin/v1/stores/{$ctx['store']->id}/orders",
        CatalogTestHelper::adminHeaders($ctx['user'], ['read-orders'])
    );

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonPath('data.0.order_number', '#1001');
});

it('retrieves a single order', function () {
    $ctx = adminOrderContext();

    $response = $this->getJson(
        "/api/admin/v1/stores/{$ctx['store']->id}/orders/{$ctx['order']->id}",
        CatalogTestHelper::adminHeaders($ctx['user'], ['read-orders'])
    );

    $response->assertOk();
    $response->assertJsonPath('data.order_number', '#1001');
    expect($response->json('data.lines'))->toHaveCount(1);
    expect($response->json('data.payments'))->toHaveCount(1);
    expect($response->json('data.fulfillments'))->toBeArray();
});

it('filters orders by status', function () {
    $ctx = adminOrderContext();

    $cart = CommerceTestHelper::cartWithLines($ctx['store']);
    $rate = App\Models\ShippingRate::query()->first();

    $checkouts = app(CheckoutService::class);
    $checkout = $checkouts->createFromCart($cart, 'other@example.com');
    $checkout = $checkouts->setAddress($checkout, CommerceTestHelper::address());
    $checkout = $checkouts->setShippingMethod($checkout, $rate->id);
    $checkout = $checkouts->selectPaymentMethod($checkout, PaymentMethod::BankTransfer);
    $checkouts->pay($checkout, PaymentMethod::BankTransfer, []);

    $response = $this->getJson(
        "/api/admin/v1/stores/{$ctx['store']->id}/orders?status=paid",
        CatalogTestHelper::adminHeaders($ctx['user'], ['read-orders'])
    );

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonPath('data.0.status', 'paid');
});

it('creates a fulfillment via API', function () {
    $ctx = adminOrderContext();
    $line = $ctx['order']->orderLines()->first();

    $response = $this->postJson(
        "/api/admin/v1/stores/{$ctx['store']->id}/orders/{$ctx['order']->id}/fulfillments",
        [
            'tracking_company' => 'DHL',
            'tracking_number' => '1234567890',
            'line_items' => [['order_line_id' => $line->id, 'quantity' => 1]],
        ],
        CatalogTestHelper::adminHeaders($ctx['user'], ['write-orders'])
    );

    $response->assertCreated();
    $response->assertJsonPath('data.status', 'pending');
    $response->assertJsonPath('data.tracking_company', 'DHL');
});

it('creates a refund via API', function () {
    $ctx = adminOrderContext();

    $response = $this->postJson(
        "/api/admin/v1/stores/{$ctx['store']->id}/orders/{$ctx['order']->id}/refunds",
        ['amount' => 1000, 'reason' => 'Customer request'],
        CatalogTestHelper::adminHeaders($ctx['user'], ['write-orders'])
    );

    $response->assertCreated();
    $response->assertJsonPath('data.amount', 1000);
    $response->assertJsonPath('data.status', 'processed');
});

it('requires write-orders ability for mutations', function () {
    $ctx = adminOrderContext();
    $line = $ctx['order']->orderLines()->first();

    $response = $this->postJson(
        "/api/admin/v1/stores/{$ctx['store']->id}/orders/{$ctx['order']->id}/fulfillments",
        ['line_items' => [['order_line_id' => $line->id, 'quantity' => 1]]],
        CatalogTestHelper::adminHeaders($ctx['user'], ['read-orders'])
    );

    $response->assertForbidden();
});

it('returns 401 without token', function () {
    $ctx = adminOrderContext();

    $this->getJson("/api/admin/v1/stores/{$ctx['store']->id}/orders")->assertUnauthorized();
});

it('confirms bank transfer payment via API', function () {
    $ctx = adminOrderContext('bank_transfer');

    $response = $this->postJson(
        "/api/admin/v1/stores/{$ctx['store']->id}/orders/{$ctx['order']->id}/confirm-payment",
        [],
        CatalogTestHelper::adminHeaders($ctx['user'], ['write-orders'])
    );

    $response->assertOk();
    $response->assertJsonPath('data.financial_status', 'paid');
    $response->assertJsonPath('data.status', 'paid');
});

it('ships and delivers a fulfillment via API', function () {
    $ctx = adminOrderContext();
    $line = $ctx['order']->orderLines()->first();
    $headers = CatalogTestHelper::adminHeaders($ctx['user'], ['write-orders']);

    $created = $this->postJson(
        "/api/admin/v1/stores/{$ctx['store']->id}/orders/{$ctx['order']->id}/fulfillments",
        ['line_items' => [['order_line_id' => $line->id, 'quantity' => $line->quantity]]],
        $headers
    );
    $created->assertCreated();
    $fulfillmentId = $created->json('data.id');

    $shipped = $this->postJson(
        "/api/admin/v1/stores/{$ctx['store']->id}/orders/{$ctx['order']->id}/fulfillments/{$fulfillmentId}/ship",
        [],
        $headers
    );
    $shipped->assertOk();
    $shipped->assertJsonPath('data.status', 'shipped');

    $delivered = $this->postJson(
        "/api/admin/v1/stores/{$ctx['store']->id}/orders/{$ctx['order']->id}/fulfillments/{$fulfillmentId}/deliver",
        [],
        $headers
    );
    $delivered->assertOk();
    $delivered->assertJsonPath('data.status', 'delivered');
});

it('lists refunds for an order', function () {
    $ctx = adminOrderContext();
    app(App\Services\RefundService::class)->create($ctx['order'], 1000);

    $response = $this->getJson(
        "/api/admin/v1/stores/{$ctx['store']->id}/orders/{$ctx['order']->id}/refunds",
        CatalogTestHelper::adminHeaders($ctx['user'], ['read-orders'])
    );

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

it('isolates orders across stores', function () {
    $ctxA = adminOrderContext();
    $ctxB = adminOrderContext();

    $response = $this->getJson(
        "/api/admin/v1/stores/{$ctxB['store']->id}/orders/{$ctxA['order']->id}",
        CatalogTestHelper::adminHeaders($ctxB['user'], ['read-orders'])
    );

    $response->assertNotFound();
});
