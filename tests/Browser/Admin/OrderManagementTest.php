<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Fulfillment;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
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
    $customer = Customer::query()->where('email', 'customer@acme.test')->firstOrFail();
    $product = Product::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('handle', 'classic-cotton-t-shirt')
        ->firstOrFail();
    $variant = ProductVariant::withoutGlobalScopes()
        ->where('product_id', $product->getKey())
        ->firstOrFail();

    $paid = adminOrderCreateOrder(
        store: $store,
        customer: $customer,
        product: $product,
        variant: $variant,
        orderNumber: '#1001',
        paymentMethod: PaymentMethod::CreditCard,
        status: OrderStatus::Paid,
        financialStatus: FinancialStatus::Paid,
        fulfillmentStatus: FulfillmentStatus::Unfulfilled,
        placedAt: now()->subDays(3),
    );

    $fulfilled = adminOrderCreateOrder(
        store: $store,
        customer: $customer,
        product: $product,
        variant: $variant,
        orderNumber: '#1002',
        paymentMethod: PaymentMethod::CreditCard,
        status: OrderStatus::Fulfilled,
        financialStatus: FinancialStatus::Paid,
        fulfillmentStatus: FulfillmentStatus::Fulfilled,
        placedAt: now()->subDays(2),
    );

    adminOrderCreateDeliveredFulfillment($fulfilled);

    $bank = adminOrderCreateOrder(
        store: $store,
        customer: $customer,
        product: $product,
        variant: $variant,
        orderNumber: '#1005',
        paymentMethod: PaymentMethod::BankTransfer,
        status: OrderStatus::Pending,
        financialStatus: FinancialStatus::Pending,
        fulfillmentStatus: FulfillmentStatus::Unfulfilled,
        placedAt: now()->subDay(),
    );

    InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $variant->getKey())
        ->update([
            'quantity_on_hand' => 20,
            'quantity_reserved' => 1,
        ]);

    return [
        'store' => $store,
        'paid' => $paid,
        'fulfilled' => $fulfilled,
        'bank' => $bank,
    ];
}

function adminOrderCreateOrder(
    Store $store,
    Customer $customer,
    Product $product,
    ProductVariant $variant,
    string $orderNumber,
    PaymentMethod $paymentMethod,
    OrderStatus $status,
    FinancialStatus $financialStatus,
    FulfillmentStatus $fulfillmentStatus,
    mixed $placedAt,
): Order {
    $subtotal = $variant->price_amount;
    $shipping = 499;
    $tax = 0;
    $total = $subtotal + $shipping + $tax;

    $order = Order::factory()->create([
        'store_id' => $store->getKey(),
        'customer_id' => $customer->getKey(),
        'order_number' => $orderNumber,
        'payment_method' => $paymentMethod,
        'status' => $status,
        'financial_status' => $financialStatus,
        'fulfillment_status' => $fulfillmentStatus,
        'currency' => $store->default_currency,
        'subtotal_amount' => $subtotal,
        'discount_amount' => 0,
        'shipping_amount' => $shipping,
        'tax_amount' => $tax,
        'total_amount' => $total,
        'email' => $customer->email,
        'placed_at' => $placedAt,
    ]);

    OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'product_id' => $product->getKey(),
        'variant_id' => $variant->getKey(),
        'title_snapshot' => 'Classic Cotton T-Shirt',
        'sku_snapshot' => $variant->sku,
        'quantity' => 1,
        'unit_price_amount' => $variant->price_amount,
        'total_amount' => $variant->price_amount,
    ]);

    Payment::factory()->create([
        'order_id' => $order->getKey(),
        'method' => $paymentMethod,
        'status' => $financialStatus === FinancialStatus::Pending ? PaymentStatus::Pending : PaymentStatus::Captured,
        'amount' => $total,
        'currency' => $store->default_currency,
    ]);

    return $order->refresh();
}

function adminOrderCreateDeliveredFulfillment(Order $order): void
{
    $line = $order->lines()->firstOrFail();
    $fulfillment = Fulfillment::query()->create([
        'order_id' => $order->getKey(),
        'status' => FulfillmentShipmentStatus::Delivered,
        'tracking_company' => 'DHL',
        'tracking_number' => 'DHL1234567890',
        'shipped_at' => now()->subDay(),
        'delivered_at' => now(),
    ]);

    $fulfillment->lines()->create([
        'order_line_id' => $line->getKey(),
        'quantity' => $line->quantity,
    ]);
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
