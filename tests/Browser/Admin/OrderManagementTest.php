<?php

use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Playwright\Playwright;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Playwright::setHost('shop.test');

    $this->seed(DatabaseSeeder::class);
});

afterEach(function (): void {
    Playwright::setHost(null);
});

function adminOrderHost(): array
{
    return ['host' => 'shop.test'];
}

function adminOrderAuthenticate(mixed $testCase): void
{
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $user = User::query()->where('email', 'admin@acme.test')->firstOrFail();

    $testCase->actingAs($user);
    $testCase->withSession(['current_store_id' => $store->getKey()]);
}

/**
 * @return array{store: Store, paid: Order, fulfilled: Order, bank: Order}
 */
function adminOrderFixtures(): array
{
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $orders = Order::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->whereIn('order_number', ['#1001', '#1002', '#1005'])
        ->get()
        ->keyBy('order_number');

    $bank = $orders->get('#1005');

    if (! $bank instanceof Order) {
        abort(500, 'Seeded bank transfer order is missing.');
    }

    $bankLine = $bank->lines()
        ->whereNotNull('variant_id')
        ->firstOrFail();

    InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $bankLine->variant_id)
        ->update([
            'quantity_on_hand' => 20,
            'quantity_reserved' => $bankLine->quantity,
        ]);

    return [
        'store' => $store,
        'paid' => $orders->get('#1001'),
        'fulfilled' => $orders->get('#1002'),
        'bank' => $bank,
    ];
}

function adminOrderOpenOrders(mixed $testCase): mixed
{
    adminOrderFixtures();
    adminOrderAuthenticate($testCase);

    return visit('/admin/orders', adminOrderHost())
        ->wait(1)
        ->assertPathIs('/admin/orders')
        ->assertSee('Orders')
        ->assertNoJavaScriptErrors();
}

function adminOrderOpenOrder(mixed $testCase, string $orderNumber): mixed
{
    return adminOrderOpenOrders($testCase)
        ->assertSee($orderNumber)
        ->click("a:has-text(\"{$orderNumber}\")")
        ->wait(1)
        ->assertSee($orderNumber)
        ->assertNoJavaScriptErrors();
}

function adminOrderCreateFulfillmentInBrowser(mixed $page): mixed
{
    return $page
        ->click('button[data-test="fulfillment-modal-button"]')
        ->wait(1)
        ->fill('input[wire\\:model="trackingCompany"]', 'DHL')
        ->fill('input[wire\\:model="trackingNumber"]', 'DHL123456789')
        ->click('button[data-test="fulfillment-submit-button"]')
        ->wait(1)
        ->assertSee('Fulfillment created')
        ->assertSee('DHL')
        ->assertSee('DHL123456789')
        ->assertNoJavaScriptErrors();
}

test('shows the order list with seeded orders', function (): void {
    adminOrderOpenOrders($this)
        ->assertSee('#1001')
        ->assertNoJavaScriptErrors();
});

test('can filter orders by status', function (): void {
    adminOrderOpenOrders($this)
        ->select('select[wire\\:model\\.live="financialStatusFilter"]', 'paid')
        ->wait(1)
        ->assertSee('#1001')
        ->assertDontSee('#1005')
        ->assertNoJavaScriptErrors()
        ->select('select[wire\\:model\\.live="fulfillmentStatusFilter"]', 'fulfilled')
        ->wait(1)
        ->assertSee('#1002')
        ->assertNoJavaScriptErrors()
        ->select('select[wire\\:model\\.live="financialStatusFilter"]', 'all')
        ->select('select[wire\\:model\\.live="fulfillmentStatusFilter"]', 'all')
        ->wait(1)
        ->assertSee('#1001')
        ->assertSee('#1005')
        ->assertNoJavaScriptErrors();
});

test('shows order detail with line items and totals', function (): void {
    adminOrderOpenOrder($this, '#1001')
        ->assertSee('Paid')
        ->assertSee('Unfulfilled')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('Subtotal')
        ->assertSee('Shipping')
        ->assertSee('Tax')
        ->assertSee('Total')
        ->assertNoJavaScriptErrors();
});

test('shows order timeline events', function (): void {
    adminOrderOpenOrder($this, '#1001')
        ->assertSee('Timeline')
        ->assertSee('Order placed')
        ->assertNoJavaScriptErrors();
});

test('can create a fulfillment', function (): void {
    adminOrderCreateFulfillmentInBrowser(adminOrderOpenOrder($this, '#1001'));
});

test('can process a refund', function (): void {
    adminOrderOpenOrder($this, '#1001')
        ->click('button[data-test="refund-modal-button"]')
        ->wait(1)
        ->fill('input[wire\\:model="refundAmount"]', '10.00')
        ->fill('textarea[wire\\:model="refundReason"]', 'Customer requested partial refund')
        ->click('button[data-test="refund-submit-button"]')
        ->wait(1)
        ->assertSee('Refund processed')
        ->assertSee('Partially Refunded')
        ->assertNoJavaScriptErrors();
});

test('shows customer information in order detail', function (): void {
    adminOrderOpenOrder($this, '#1001')
        ->assertSee('customer@acme.test')
        ->assertNoJavaScriptErrors();
});

test('can confirm bank transfer payment', function (): void {
    adminOrderOpenOrder($this, '#1005')
        ->assertSee('Pending')
        ->assertPresent('button[data-test="confirm-payment-button"]')
        ->click('button[data-test="confirm-payment-button"]')
        ->wait(1)
        ->assertSee('Payment confirmed')
        ->assertSee('Paid')
        ->assertDontSee('Confirm payment')
        ->assertNoJavaScriptErrors();
});

test('shows fulfillment guard for unpaid order', function (): void {
    adminOrderOpenOrder($this, '#1005')
        ->assertSee('Cannot create fulfillment')
        ->assertSee('Payment must be confirmed before items can be fulfilled')
        ->assertScript('document.querySelector("button[data-test=\"fulfillment-modal-button\"]") === null')
        ->assertNoJavaScriptErrors();
});

test('can mark fulfillment as shipped', function (): void {
    $page = adminOrderCreateFulfillmentInBrowser(adminOrderOpenOrder($this, '#1001'));

    $page->click('button[data-test="mark-fulfillment-shipped-button"]')
        ->wait(1)
        ->assertSee('Shipped')
        ->assertNoJavaScriptErrors();
});

test('can mark fulfillment as delivered', function (): void {
    $page = adminOrderCreateFulfillmentInBrowser(adminOrderOpenOrder($this, '#1001'));

    $page->click('button[data-test="mark-fulfillment-shipped-button"]')
        ->wait(1)
        ->click('button[data-test="mark-fulfillment-delivered-button"]')
        ->wait(1)
        ->assertSee('Delivered')
        ->assertSee('Fulfilled')
        ->assertNoJavaScriptErrors();
});
