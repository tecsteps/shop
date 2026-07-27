<?php

use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    bindBrowserStorefrontDomain();

    actingAsAdmin(User::query()->where('email', 'admin@acme.test')->sole());
});

/**
 * Resolve a seeded order by its number (orders are store-scoped via the
 * HTTP context only, so the plain query is unscoped in tests).
 */
function orderByNumber(string $number): Order
{
    return Order::query()->where('order_number', $number)->sole();
}

test('shows the order list with seeded orders', function () {
    visit('/admin/orders')
        ->assertSee('#1001')
        ->assertNoJavascriptErrors();
});

test('can filter orders by status', function () {
    $page = visit('/admin/orders');

    $page->press('button[role="tab"]:has-text("Paid")')
        ->wait(1)
        ->assertSee('#1001')
        ->assertNoJavascriptErrors();

    $page->press('button[role="tab"]:has-text("Fulfilled")')
        ->wait(1)
        ->assertSee('#1002')
        ->assertDontSee('#1001')
        ->assertNoJavascriptErrors();

    $page->press('button[role="tab"]:has-text("All")')
        ->wait(1)
        ->assertSee('#1001')
        ->assertNoJavascriptErrors();
});

test('shows order detail with line items and totals', function () {
    visit('/admin/orders')
        ->click('a:has-text("#1001")')
        ->wait(1)
        ->assertSee('#1001')
        ->assertSee('Paid')
        ->assertSee('Unfulfilled')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('Subtotal')
        ->assertSee('Shipping')
        ->assertSee('Tax')
        ->assertSee('Total')
        ->assertNoJavascriptErrors();
});

test('shows order timeline events', function () {
    $order = orderByNumber('#1001');

    visit("/admin/orders/{$order->id}")
        ->assertSee('Timeline')
        ->assertSee('Order placed')
        ->assertNoJavascriptErrors();
});

test('can create a fulfillment', function () {
    $order = orderByNumber('#1001');

    visit("/admin/orders/{$order->id}")
        ->press('Create fulfillment')
        ->wait(1)
        ->fill('trackingCompany', 'DHL')
        ->fill('trackingNumber', 'DHL123456789')
        ->press('button[wire\:click="createFulfillment"]')
        ->wait(1)
        ->assertSee('Fulfillment created')
        ->assertSee('DHL')
        ->assertSee('DHL123456789')
        ->assertNoJavascriptErrors();

    expect(
        Fulfillment::query()
            ->where('order_id', $order->id)
            ->where('tracking_company', 'DHL')
            ->where('tracking_number', 'DHL123456789')
            ->exists()
    )->toBeTrue();
});

test('can process a refund', function () {
    $order = orderByNumber('#1001');

    // The refund amount field is in cents (minor units): 1000 = 10.00 EUR.
    visit("/admin/orders/{$order->id}")
        ->press('Refund')
        ->wait(1)
        ->fill('refundAmount', '1000')
        ->fill('refundReason', 'Customer requested partial refund')
        ->press('Create refund')
        ->wait(1)
        ->assertSee('Refund issued')
        ->assertSee('Partially refunded')
        ->assertNoJavascriptErrors();

    expect(
        $order->refunds()
            ->where('amount', 1000)
            ->where('reason', 'Customer requested partial refund')
            ->exists()
    )->toBeTrue();
});

test('shows customer information in order detail', function () {
    $order = orderByNumber('#1001');

    visit("/admin/orders/{$order->id}")
        ->assertSee('customer@acme.test')
        ->assertNoJavascriptErrors();
});

test('can confirm bank transfer payment', function () {
    $order = orderByNumber('#1005');

    visit("/admin/orders/{$order->id}")
        ->assertSee('Pending')
        ->assertSee('Confirm payment')
        ->press('Confirm payment')
        ->wait(1)
        ->assertSee('Payment confirmed')
        ->assertSee('Paid')
        ->assertDontSee('Confirm payment')
        ->assertNoJavascriptErrors();

    expect($order->refresh()->financial_status)->toBe(App\Enums\FinancialStatus::Paid);
});

test('shows fulfillment guard for unpaid order', function () {
    $order = orderByNumber('#1005');

    visit("/admin/orders/{$order->id}")
        ->assertSee('Fulfillment cannot be created until payment is confirmed')
        ->assertButtonDisabled('Create fulfillment')
        ->assertNoJavascriptErrors();
});

test('can mark fulfillment as shipped', function () {
    $order = orderByNumber('#1001');

    $page = visit("/admin/orders/{$order->id}");

    // Create the fulfillment first so there is something to ship.
    $page->press('Create fulfillment')
        ->wait(1)
        ->press('button[wire\:click="createFulfillment"]')
        ->wait(1)
        ->assertSee('Fulfillment created');

    // The card button opens the tracking modal; submit it to ship.
    $page->press('Mark as shipped')
        ->wait(1)
        ->press('button[wire\:click="markAsShipped"]')
        ->wait(1)
        ->assertSee('Fulfillment marked as shipped')
        ->assertSee('Shipped')
        ->assertNoJavascriptErrors();
});

test('can mark fulfillment as delivered', function () {
    $order = orderByNumber('#1001');

    $page = visit("/admin/orders/{$order->id}");

    // Create and ship a fulfillment so it can be delivered.
    $page->press('Create fulfillment')
        ->wait(1)
        ->press('button[wire\:click="createFulfillment"]')
        ->wait(1)
        ->assertSee('Fulfillment created');

    $page->press('Mark as shipped')
        ->wait(1)
        ->press('button[wire\:click="markAsShipped"]')
        ->wait(1)
        ->assertSee('Fulfillment marked as shipped');

    $page->press('Mark as delivered')
        ->wait(1)
        ->assertSee('Fulfillment marked as delivered')
        ->assertSee('Delivered')
        ->assertSee('Fulfilled')
        ->assertNoJavascriptErrors();
});
