<?php

it('shows the order list with seeded orders', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Orders")')
        ->assertSeeIn('h1[data-flux-heading]', 'Orders')
        ->assertSee('#1001')
        ->assertNoJavascriptErrors();
});

it('can filter orders by status', function (): void {
    $page = browserLoginAsAdmin();

    $page->click('aside a:has-text("Orders")')
        ->click('@order-status-tab-paid')
        ->assertSee('#1001')
        ->assertNoJavascriptErrors()
        ->click('@order-status-tab-fulfilled')
        ->assertSee('#1002')
        ->assertDontSee('#1001')
        ->assertNoJavascriptErrors()
        ->click('@order-status-tab-all')
        ->assertSee('#1001')
        ->assertNoJavascriptErrors();
});

it('shows order detail with line items and totals', function (): void {
    $page = browserOpenAdminOrder('#1001');

    $page->assertSee('#1001')
        ->assertSee('Paid')
        ->assertSee('Unfulfilled')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('Subtotal')
        ->assertSee('Shipping')
        ->assertSee('Tax')
        ->assertSee('Total')
        ->assertNoJavascriptErrors();
});

it('shows order timeline events', function (): void {
    $page = browserOpenAdminOrder('#1001');

    $page->assertSee('Timeline')
        ->assertSee('Order placed')
        ->assertNoJavascriptErrors();
});

it('can create a fulfillment', function (): void {
    $page = browserOpenAdminOrder('#1001');

    browserCreateFulfillment($page, '#1001');

    $page->assertSee('DHL')
        ->assertSee('DHL123456789')
        ->assertNoJavascriptErrors();
});

it('can process a refund', function (): void {
    $page = browserOpenAdminOrder('#1001');

    $page->click('@refund-button')
        ->assertSee('Refund order')
        ->fill('@refund-amount-input', '10.00')
        ->fill('@refund-reason-input', 'Customer requested partial refund')
        ->click('@submit-refund-button')
        ->assertSee('Refund processed')
        ->assertSee('Partially Refunded')
        ->assertNoJavascriptErrors();
});

it('shows customer information in order detail', function (): void {
    $page = browserOpenAdminOrder('#1001');

    $page->assertSee('customer@acme.test')
        ->assertNoJavascriptErrors();
});

it('can confirm bank transfer payment', function (): void {
    $page = browserOpenAdminOrder('#1005');

    $page->assertSee('Pending')
        ->assertVisible('[data-test="confirm-payment-button"]')
        ->click('@confirm-payment-button')
        ->assertSee('Payment confirmed')
        ->assertSee('Paid')
        ->assertNotPresent('[data-test="confirm-payment-button"]')
        ->assertNoJavascriptErrors();
});

it('shows fulfillment guard for unpaid order', function (): void {
    $page = browserOpenAdminOrder('#1005');

    $page->assertVisible('[data-test="fulfillment-guard-callout"]')
        ->assertSee('Payment must be confirmed before items can be fulfilled')
        ->assertNotPresent('[data-test="create-fulfillment-button"]')
        ->assertNoJavascriptErrors();
});

it('can mark fulfillment as shipped', function (): void {
    $page = browserOpenAdminOrder('#1001');

    browserCreateFulfillment($page, '#1001');

    $page->click('Mark as shipped')
        ->assertSee('Shipped')
        ->assertNoJavascriptErrors();
});

it('can mark fulfillment as delivered', function (): void {
    $page = browserOpenAdminOrder('#1001');

    browserCreateFulfillment($page, '#1001');

    $page->click('Mark as shipped')
        ->assertSee('Shipped')
        ->click('Mark as delivered')
        ->assertSee('Delivered')
        ->assertScript("document.querySelector('[data-test=\"fulfillment-status-badge\"]').textContent.trim()", 'Fulfilled')
        ->assertNoJavascriptErrors();
});
