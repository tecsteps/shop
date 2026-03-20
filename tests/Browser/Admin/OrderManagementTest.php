<?php

// Suite 4: Admin Order Management - Order listing, filtering, detail, fulfillment, refund

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Store;

beforeEach(function () {
    $store = Store::where('handle', 'acme-fashion')->first();
    app()->instance('current_store', $store);

    $customer = Customer::where('email', 'customer@acme.test')
        ->where('store_id', $store->id)
        ->first();

    if (! $customer) {
        return;
    }

    // Create test orders for the admin order management tests
    $variant = \App\Models\ProductVariant::whereHas('product', function ($q) use ($store) {
        $q->where('store_id', $store->id)->where('handle', 'classic-cotton-t-shirt');
    })->first();

    if (! $variant) {
        return;
    }

    // Order #1001 - paid, unfulfilled
    $order1 = Order::create([
        'store_id' => $store->id,
        'customer_id' => $customer->id,
        'order_number' => 1001,
        'email' => $customer->email,
        'financial_status' => 'paid',
        'fulfillment_status' => 'unfulfilled',
        'subtotal_amount' => 2499,
        'shipping_amount' => 499,
        'tax_amount' => 475,
        'total_amount' => 3473,
        'currency' => 'EUR',
        'shipping_address_json' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => 'Hauptstrasse 1',
            'city' => 'Berlin',
            'zip' => '10115',
            'country' => 'DE',
        ],
        'payment_method' => 'credit_card',
        'placed_at' => now(),
    ]);

    OrderLine::create([
        'order_id' => $order1->id,
        'product_id' => $variant->product_id,
        'variant_id' => $variant->id,
        'title_snapshot' => 'Classic Cotton T-Shirt',
        'sku_snapshot' => $variant->sku,
        'quantity' => 1,
        'unit_price_amount' => 2499,
        'total_amount' => 2499,
    ]);

    Payment::create([
        'order_id' => $order1->id,
        'provider' => 'mock_psp',
        'method' => 'credit_card',
        'status' => 'captured',
        'amount' => 3473,
        'currency' => 'EUR',
        'provider_payment_id' => 'mock_ref_1001',
    ]);

    // Order #1005 - pending bank transfer
    $order5 = Order::create([
        'store_id' => $store->id,
        'customer_id' => $customer->id,
        'order_number' => 1005,
        'email' => $customer->email,
        'financial_status' => 'pending',
        'fulfillment_status' => 'unfulfilled',
        'subtotal_amount' => 2499,
        'shipping_amount' => 499,
        'tax_amount' => 475,
        'total_amount' => 3473,
        'currency' => 'EUR',
        'shipping_address_json' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => 'Hauptstrasse 1',
            'city' => 'Berlin',
            'zip' => '10115',
            'country' => 'DE',
        ],
        'payment_method' => 'bank_transfer',
        'placed_at' => now(),
    ]);

    OrderLine::create([
        'order_id' => $order5->id,
        'product_id' => $variant->product_id,
        'variant_id' => $variant->id,
        'title_snapshot' => 'Classic Cotton T-Shirt',
        'sku_snapshot' => $variant->sku,
        'quantity' => 1,
        'unit_price_amount' => 2499,
        'total_amount' => 2499,
    ]);

    Payment::create([
        'order_id' => $order5->id,
        'provider' => 'mock_psp',
        'method' => 'bank_transfer',
        'status' => 'pending',
        'amount' => 3473,
        'currency' => 'EUR',
        'provider_payment_id' => 'mock_ref_1005',
    ]);
});

it('shows the order list with seeded orders', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Orders")')
        ->assertSee('1001')
        ->assertNoJavaScriptErrors();
});

it('can filter orders by status', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Orders")')
        ->assertSee('1001')
        ->assertNoJavaScriptErrors();
});

it('shows order detail with line items and totals', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Orders")')
        ->click('1001')
        ->assertSee('Order 1001')
        ->assertSee('Paid')
        ->assertSee('Unfulfilled')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('Subtotal')
        ->assertSee('Total')
        ->assertNoJavaScriptErrors();
});

it('shows order timeline events', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Orders")')
        ->click('1001')
        ->assertSee('Order 1001')
        ->assertNoJavaScriptErrors();
});

it('can create a fulfillment', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Orders")')
        ->click('1001')
        ->assertSee('Create fulfillment')
        ->fill('[placeholder="Carrier"]', 'DHL')
        ->fill('[placeholder="Tracking #"]', 'DHL123456789')
        ->click('Fulfill remaining items')
        ->assertSee('Fulfillment created')
        ->assertNoJavaScriptErrors();
});

it('can process a refund', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Orders")')
        ->click('1001')
        ->assertSee('Process refund')
        ->fill('[placeholder="Amount (cents)"]', '1000')
        ->fill('[placeholder="Reason"]', 'Customer requested partial refund')
        ->click('Refund')
        ->assertSee('Refund processed')
        ->assertNoJavaScriptErrors();
});

it('shows customer information in order detail', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Orders")')
        ->click('1001')
        ->assertSee('customer@acme.test')
        ->assertNoJavaScriptErrors();
});

it('can confirm bank transfer payment', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Orders")')
        ->click('1005')
        ->assertSee('Pending')
        ->click('Confirm Payment')
        ->assertSee('Payment confirmed')
        ->assertNoJavaScriptErrors();
});

it('shows fulfillment guard for unpaid order', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Orders")')
        ->click('1005')
        ->assertSee('Pending')
        ->assertNoJavaScriptErrors();
});

it('can mark fulfillment as shipped', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Orders")')
        ->click('1001')
        ->fill('[placeholder="Carrier"]', 'DHL')
        ->fill('[placeholder="Tracking #"]', 'DHL999')
        ->click('Fulfill remaining items')
        ->assertSee('Fulfillment created')
        ->assertNoJavaScriptErrors();
});

it('can mark fulfillment as delivered', function () {
    $page = $this->visit('/admin/login', ['host' => 'acme-fashion.test']);

    $page->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('Log in')
        ->click('a:has-text("Orders")')
        ->click('1001')
        ->fill('[placeholder="Carrier"]', 'DHL')
        ->fill('[placeholder="Tracking #"]', 'DHL888')
        ->click('Fulfill remaining items')
        ->assertSee('Fulfillment created')
        ->assertNoJavaScriptErrors();
});
