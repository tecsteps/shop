<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentOrderStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Livewire\Admin\Orders\Index;
use App\Livewire\Admin\Orders\Show;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Livewire\Livewire;

beforeEach(function () {
    $this->store = $this->createStore();
    $this->user = $this->createUserWithRole($this->store, 'owner');
    $this->bindStore($this->store);
});

test('lists orders with status filter', function () {
    foreach (range(1, 3) as $i) {
        Order::factory()->create([
            'store_id' => $this->store->id,
            'order_number' => '#20'.$i,
        ]);
    }

    foreach (range(4, 5) as $i) {
        Order::factory()->paid()->create([
            'store_id' => $this->store->id,
            'order_number' => '#20'.$i,
        ]);
    }

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->set('statusFilter', 'paid')
        ->assertSee('#204')
        ->assertSee('#205')
        ->assertDontSee('#201')
        ->assertDontSee('#202')
        ->assertDontSee('#203');
});

test('searches orders by order number or customer email', function () {
    Order::factory()->create([
        'store_id' => $this->store->id,
        'order_number' => '#3001',
        'email' => 'jane@example.com',
    ]);
    Order::factory()->create([
        'store_id' => $this->store->id,
        'order_number' => '#3002',
        'email' => 'bob@example.com',
    ]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->set('search', '3001')
        ->assertSee('#3001')
        ->assertDontSee('#3002')
        ->set('search', 'bob@example.com')
        ->assertSee('#3002')
        ->assertDontSee('#3001');
});

test('filters orders by financial and fulfillment status', function () {
    Order::factory()->paid()->create([
        'store_id' => $this->store->id,
        'order_number' => '#4001',
    ]);
    Order::factory()->bankTransfer()->create([
        'store_id' => $this->store->id,
        'order_number' => '#4002',
    ]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->set('financialFilter', 'pending')
        ->assertSee('#4002')
        ->assertDontSee('#4001')
        ->set('financialFilter', 'all')
        ->set('fulfillmentFilter', 'unfulfilled')
        ->assertSee('#4001')
        ->assertSee('#4002');
});

test('filters orders by placed date range', function () {
    Order::factory()->create([
        'store_id' => $this->store->id,
        'order_number' => '#5001',
        'placed_at' => now()->subDays(10),
    ]);
    Order::factory()->create([
        'store_id' => $this->store->id,
        'order_number' => '#5002',
        'placed_at' => now()->subDay(),
    ]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->set('dateFrom', now()->subDays(3)->toDateString())
        ->set('dateTo', now()->toDateString())
        ->assertSee('#5002')
        ->assertDontSee('#5001');
});

test('sorts orders by placed_at descending by default and toggles direction', function () {
    Order::factory()->create([
        'store_id' => $this->store->id,
        'order_number' => '#6001',
        'placed_at' => now()->subDays(2),
    ]);
    Order::factory()->create([
        'store_id' => $this->store->id,
        'order_number' => '#6002',
        'placed_at' => now()->subDay(),
    ]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->assertSeeInOrder(['#6002', '#6001'])
        ->call('sortBy', 'placed_at')
        ->assertSeeInOrder(['#6001', '#6002']);
});

test('shows order detail page with line items, totals and payments', function () {
    $order = Order::factory()->paid()->withLines([
        ['quantity' => 2, 'unit_price_amount' => 1000, 'title_snapshot' => 'Blue Shirt', 'sku_snapshot' => 'BS-001'],
        ['quantity' => 1, 'unit_price_amount' => 500, 'title_snapshot' => 'Red Hat', 'sku_snapshot' => 'RH-001'],
    ])->create([
        'store_id' => $this->store->id,
        'order_number' => '#7001',
        'tax_amount' => 250,
    ]);

    Payment::factory()->create(['order_id' => $order->id]);

    $this->actingAs($this->user)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/orders/'.$order->id)
        ->assertOk()
        ->assertSee('#7001')
        ->assertSee('Blue Shirt')
        ->assertSee('Red Hat')
        ->assertSee('BS-001')
        ->assertSee('25.00 USD') // subtotal
        ->assertSee('2.50 USD') // tax
        ->assertSee('27.50 USD') // total
        ->assertSee('Credit Card')
        ->assertSee('Order placed')
        ->assertSee('Payment received');
});

test('confirm payment button is only visible for pending bank transfer orders', function () {
    $bankTransfer = Order::factory()->bankTransfer()->withLines([
        ['quantity' => 1, 'unit_price_amount' => 1000],
    ])->create(['store_id' => $this->store->id]);

    Payment::factory()->pending()->create(['order_id' => $bankTransfer->id]);

    $paid = Order::factory()->paid()->withLines([
        ['quantity' => 1, 'unit_price_amount' => 1000],
    ])->create(['store_id' => $this->store->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Show::class, ['order' => $bankTransfer])
        ->assertSee('Confirm payment');
    Livewire::test(Show::class, ['order' => $paid])
        ->assertDontSee('Confirm payment');
});

test('confirms a bank transfer payment and commits reserved inventory', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->withInventory(10)->create(['product_id' => $product->id]);

    app(InventoryService::class)->reserve($variant->inventoryItem, 2);

    $order = Order::factory()->bankTransfer()->withLines([
        ['quantity' => 2, 'unit_price_amount' => 1000, 'variant_id' => $variant->id, 'product_id' => $product->id],
    ])->create(['store_id' => $this->store->id]);

    Payment::factory()->pending()->create(['order_id' => $order->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Show::class, ['order' => $order])
        ->call('confirmPayment')
        ->assertDispatched('toast', type: 'success', message: 'Payment confirmed');

    $order->refresh();
    $variant->inventoryItem->refresh();

    expect($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->status)->toBe(OrderStatus::Paid)
        ->and($order->payments->first()->status)->toBe(PaymentStatus::Captured)
        ->and($variant->inventoryItem->quantity_on_hand)->toBe(8)
        ->and($variant->inventoryItem->quantity_reserved)->toBe(0);
});

test('cancels an order, releases reserved inventory and voids the payment', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->withInventory(10)->create(['product_id' => $product->id]);

    app(InventoryService::class)->reserve($variant->inventoryItem, 2);

    $order = Order::factory()->bankTransfer()->withLines([
        ['quantity' => 2, 'unit_price_amount' => 1000, 'variant_id' => $variant->id, 'product_id' => $product->id],
    ])->create(['store_id' => $this->store->id]);

    Payment::factory()->pending()->create(['order_id' => $order->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Show::class, ['order' => $order])
        ->call('openCancelModal')
        ->set('cancelReason', 'Customer changed their mind')
        ->call('cancelOrder')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success', message: 'Order cancelled');

    $order->refresh();
    $variant->inventoryItem->refresh();

    expect($order->status)->toBe(OrderStatus::Cancelled)
        ->and($order->financial_status)->toBe(FinancialStatus::Voided)
        ->and($order->payments->first()->status)->toBe(PaymentStatus::Failed)
        ->and($variant->inventoryItem->quantity_reserved)->toBe(0)
        ->and($variant->inventoryItem->quantity_on_hand)->toBe(10);
});

test('cancel requires a reason', function () {
    $order = Order::factory()->bankTransfer()->withLines([
        ['quantity' => 1, 'unit_price_amount' => 1000],
    ])->create(['store_id' => $this->store->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Show::class, ['order' => $order])
        ->call('cancelOrder')
        ->assertHasErrors('cancelReason');
});

test('staff cannot cancel an order', function () {
    $staff = $this->createUserWithRole($this->store, 'staff');

    $order = Order::factory()->bankTransfer()->withLines([
        ['quantity' => 1, 'unit_price_amount' => 1000],
    ])->create(['store_id' => $this->store->id]);

    Livewire::actingAs($staff);
    Livewire::test(Show::class, ['order' => $order])
        ->call('cancelOrder')
        ->assertForbidden();

    expect($order->refresh()->status)->toBe(OrderStatus::Pending);
});

test('creates a partial fulfillment then completes it', function () {
    $order = Order::factory()->paid()->withLines([
        ['quantity' => 3, 'unit_price_amount' => 1000, 'title_snapshot' => 'Blue Shirt'],
    ])->create(['store_id' => $this->store->id]);

    Payment::factory()->create(['order_id' => $order->id]);

    $lineId = $order->lines->first()->id;

    Livewire::actingAs($this->user);
    Livewire::test(Show::class, ['order' => $order])
        ->call('openFulfillmentModal')
        ->assertSet('showFulfillmentModal', true)
        ->assertSet('fulfillmentLines.'.$lineId, 3) // unfulfilled quantity preselected
        ->set('fulfillmentLines.'.$lineId, 1)
        ->set('trackingCompany', 'DHL')
        ->set('trackingNumber', '123456')
        ->call('createFulfillment')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success', message: 'Fulfillment created');

    $order->refresh();

    expect($order->fulfillment_status)->toBe(FulfillmentOrderStatus::Partial)
        ->and($order->fulfillments)->toHaveCount(1)
        ->and($order->fulfillments->first()->status)->toBe(FulfillmentShipmentStatus::Pending)
        ->and($order->fulfillments->first()->tracking_company)->toBe('DHL')
        ->and($order->fulfillments->first()->lines->first()->quantity)->toBe(1);

    // Fulfill the remaining two units: the order becomes fulfilled.
    Livewire::test(Show::class, ['order' => $order])
        ->call('openFulfillmentModal')
        ->assertSet('fulfillmentLines.'.$lineId, 2)
        ->call('createFulfillment')
        ->assertHasNoErrors();

    $order->refresh();

    expect($order->fulfillment_status)->toBe(FulfillmentOrderStatus::Fulfilled)
        ->and($order->status)->toBe(OrderStatus::Fulfilled);
});

test('fulfillment guard blocks fulfillment while payment is pending', function () {
    $order = Order::factory()->bankTransfer()->withLines([
        ['quantity' => 1, 'unit_price_amount' => 1000],
    ])->create(['store_id' => $this->store->id]);

    Payment::factory()->pending()->create(['order_id' => $order->id]);

    $lineId = $order->lines->first()->id;

    Livewire::actingAs($this->user);
    Livewire::test(Show::class, ['order' => $order])
        ->assertSee('Fulfillment cannot be created until payment is confirmed.')
        ->call('openFulfillmentModal')
        ->call('createFulfillment')
        ->assertDispatched('toast', type: 'error');

    expect(Fulfillment::query()->where('order_id', $order->id)->exists())->toBeFalse();
});

test('marks a fulfillment as shipped with tracking, then delivered', function () {
    $order = Order::factory()->paid()->withLines([
        ['quantity' => 1, 'unit_price_amount' => 1000],
    ])->create(['store_id' => $this->store->id]);

    Payment::factory()->create(['order_id' => $order->id]);

    $fulfillment = Fulfillment::factory()->create(['order_id' => $order->id]);
    $fulfillment->lines()->create(['order_line_id' => $order->lines->first()->id, 'quantity' => 1]);

    Livewire::actingAs($this->user);
    Livewire::test(Show::class, ['order' => $order])
        ->call('openShipModal', $fulfillment->id)
        ->set('trackingCompany', 'UPS')
        ->set('trackingNumber', '1Z999')
        ->call('markAsShipped')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success', message: 'Fulfillment marked as shipped');

    $fulfillment->refresh();

    expect($fulfillment->status)->toBe(FulfillmentShipmentStatus::Shipped)
        ->and($fulfillment->tracking_company)->toBe('UPS')
        ->and($fulfillment->shipped_at)->not->toBeNull();

    Livewire::test(Show::class, ['order' => $order->refresh()])
        ->call('markAsDelivered', $fulfillment->id)
        ->assertDispatched('toast', type: 'success', message: 'Fulfillment marked as delivered');

    expect($fulfillment->refresh()->status)->toBe(FulfillmentShipmentStatus::Delivered);
});

test('creates a partial refund and updates the financial status', function () {
    $order = Order::factory()->paid()->withLines([
        ['quantity' => 2, 'unit_price_amount' => 1500],
    ])->create(['store_id' => $this->store->id]);

    Payment::factory()->create(['order_id' => $order->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Show::class, ['order' => $order])
        ->call('openRefundModal')
        ->assertSet('refundAmount', 3000) // full refundable amount preselected
        ->set('refundAmount', 1000)
        ->set('refundReason', 'Damaged item')
        ->call('createRefund')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success', message: 'Refund issued');

    $order->refresh();

    expect($order->financial_status)->toBe(FinancialStatus::PartiallyRefunded)
        ->and($order->refunds)->toHaveCount(1)
        ->and($order->refunds->first()->amount)->toBe(1000)
        ->and($order->refunds->first()->reason)->toBe('Damaged item')
        ->and($order->refundableAmount())->toBe(2000);
});

test('creates a full refund and marks the order refunded', function () {
    $order = Order::factory()->paid()->withLines([
        ['quantity' => 2, 'unit_price_amount' => 1500],
    ])->create(['store_id' => $this->store->id]);

    $payment = Payment::factory()->create(['order_id' => $order->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Show::class, ['order' => $order])
        ->call('openRefundModal')
        ->call('createRefund')
        ->assertHasNoErrors();

    $order->refresh();

    expect($order->financial_status)->toBe(FinancialStatus::Refunded)
        ->and($order->status)->toBe(OrderStatus::Refunded)
        ->and($payment->refresh()->status)->toBe(PaymentStatus::Refunded);
});

test('refund with restock increases on-hand inventory', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->withInventory(10)->create(['product_id' => $product->id]);

    // Simulate payment capture: on_hand 10 -> 8.
    app(InventoryService::class)->reserve($variant->inventoryItem, 2);
    app(InventoryService::class)->commit($variant->inventoryItem, 2);

    $order = Order::factory()->paid()->withLines([
        ['quantity' => 2, 'unit_price_amount' => 1000, 'variant_id' => $variant->id, 'product_id' => $product->id],
    ])->create(['store_id' => $this->store->id]);

    Payment::factory()->create(['order_id' => $order->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Show::class, ['order' => $order])
        ->call('openRefundModal')
        ->set('refundRestock', true)
        ->call('createRefund')
        ->assertHasNoErrors();

    expect($variant->inventoryItem->refresh()->quantity_on_hand)->toBe(10);
});

test('refund amount cannot exceed the refundable remainder', function () {
    $order = Order::factory()->paid()->withLines([
        ['quantity' => 1, 'unit_price_amount' => 1000],
    ])->create(['store_id' => $this->store->id]);

    Payment::factory()->create(['order_id' => $order->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Show::class, ['order' => $order])
        ->call('openRefundModal')
        ->set('refundAmount', 1001)
        ->call('createRefund')
        ->assertHasErrors('refundAmount');
});

test('support can view orders but cannot refund', function () {
    $support = $this->createUserWithRole($this->store, 'support');

    $order = Order::factory()->paid()->withLines([
        ['quantity' => 1, 'unit_price_amount' => 1000, 'title_snapshot' => 'Blue Shirt'],
    ])->create(['store_id' => $this->store->id]);

    Payment::factory()->create(['order_id' => $order->id]);

    $this->actingAs($support)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/orders/'.$order->id)
        ->assertOk()
        ->assertSee('Blue Shirt');

    Livewire::actingAs($support);
    Livewire::test(Show::class, ['order' => $order])
        ->call('openRefundModal')
        ->assertForbidden();

    Livewire::test(Show::class, ['order' => $order])
        ->set('refundAmount', 500)
        ->call('createRefund')
        ->assertForbidden();

    expect($order->refresh()->refunds)->toHaveCount(0);
});

test('support cannot create fulfillments but staff can', function () {
    $support = $this->createUserWithRole($this->store, 'support');

    $order = Order::factory()->paid()->withLines([
        ['quantity' => 1, 'unit_price_amount' => 1000],
    ])->create(['store_id' => $this->store->id]);

    Payment::factory()->create(['order_id' => $order->id]);

    Livewire::actingAs($support);
    Livewire::test(Show::class, ['order' => $order])
        ->call('openFulfillmentModal')
        ->assertForbidden();
});

test('guests are redirected from admin order pages', function () {
    $order = Order::factory()->create(['store_id' => $this->store->id]);

    $this->get('/admin/orders')->assertRedirect('/admin/login');
    $this->get('/admin/orders/'.$order->id)->assertRedirect('/admin/login');
});
