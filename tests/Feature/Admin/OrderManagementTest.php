<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Livewire\Admin\Orders\Index as OrderIndex;
use App\Livewire\Admin\Orders\Show as OrderShow;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use Livewire\Livewire;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->actingAs($this->ctx['user']);
    session(['current_store_id' => $this->ctx['store']->id]);
});

it('requires authentication to access orders page', function () {
    auth()->logout();
    $this->get('/admin/orders')->assertRedirect('/admin/login');
});

it('renders the orders index page', function () {
    $this->get('/admin/orders')
        ->assertStatus(200)
        ->assertSee('Orders');
});

it('lists orders with pagination', function () {
    Order::factory()->count(3)->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    $component = Livewire::test(OrderIndex::class);
    expect($component->instance()->orders->total())->toBe(3);
});

it('searches orders by order number', function () {
    Order::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'order_number' => '#1001',
    ]);
    Order::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'order_number' => '#2002',
    ]);

    $component = Livewire::test(OrderIndex::class);
    $component->set('search', '1001');

    expect($component->instance()->orders->total())->toBe(1);
});

it('searches orders by email', function () {
    Order::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'email' => 'alice@example.com',
    ]);
    Order::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'email' => 'bob@example.com',
    ]);

    $component = Livewire::test(OrderIndex::class);
    $component->set('search', 'alice');

    expect($component->instance()->orders->total())->toBe(1);
});

it('filters orders by financial status', function () {
    Order::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'financial_status' => FinancialStatus::Paid,
    ]);
    Order::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'financial_status' => FinancialStatus::Pending,
    ]);

    $component = Livewire::test(OrderIndex::class);
    $component->set('financialFilter', 'paid');

    expect($component->instance()->orders->total())->toBe(1);
});

it('filters orders by fulfillment status', function () {
    Order::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
    ]);
    Order::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
    ]);

    $component = Livewire::test(OrderIndex::class);
    $component->set('fulfillmentFilter', 'fulfilled');

    expect($component->instance()->orders->total())->toBe(1);
});

it('shows empty state when no orders exist', function () {
    $component = Livewire::test(OrderIndex::class);
    $component->assertSee('No orders yet');
});

it('renders the order show page', function () {
    $order = Order::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'order_number' => '#5555',
    ]);

    $this->get("/admin/orders/{$order->id}")
        ->assertStatus(200)
        ->assertSee('#5555');
});

it('displays order line items', function () {
    $order = Order::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    OrderLine::factory()->create([
        'order_id' => $order->id,
        'title_snapshot' => 'Blue Shirt',
        'quantity' => 2,
        'price_amount' => 2500,
        'total_amount' => 5000,
    ]);

    $component = Livewire::test(OrderShow::class, ['order' => $order]);
    $component->assertSee('Blue Shirt');
    $component->assertSee('Items');
});

it('creates a fulfillment for an order', function () {
    $order = Order::factory()->paid()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    $line = OrderLine::factory()->create([
        'order_id' => $order->id,
        'quantity' => 2,
        'fulfilled_quantity' => 0,
    ]);

    $component = Livewire::test(OrderShow::class, ['order' => $order]);
    $component->set("fulfillmentQuantities.{$line->id}", 2);
    $component->set('trackingNumber', 'TRACK123');
    $component->set('trackingCompany', 'DHL');
    $component->call('createFulfillment');

    $order->refresh();
    expect($order->fulfillments)->toHaveCount(1);
    expect($order->fulfillments->first()->tracking_number)->toBe('TRACK123');
});

it('confirms payment for bank transfer orders', function () {
    $order = Order::factory()->pending()->create([
        'store_id' => $this->ctx['store']->id,
        'payment_method' => PaymentMethod::BankTransfer,
        'financial_status' => FinancialStatus::Pending,
    ]);

    OrderLine::factory()->create([
        'order_id' => $order->id,
        'requires_shipping' => true,
    ]);

    Payment::create([
        'order_id' => $order->id,
        'provider' => 'mock',
        'method' => PaymentMethod::BankTransfer,
        'status' => PaymentStatus::Pending,
        'amount' => $order->total_amount,
        'currency' => 'EUR',
        'created_at' => now(),
    ]);

    $component = Livewire::test(OrderShow::class, ['order' => $order]);
    $component->call('confirmPayment');

    $order->refresh();
    expect($order->financial_status)->toBe(FinancialStatus::Paid);
    expect($order->status)->toBe(\App\Enums\OrderStatus::Paid);
    expect($order->payments->first()->status)->toBe(PaymentStatus::Captured);
});

it('processes a refund for a paid order', function () {
    $order = Order::factory()->paid()->create([
        'store_id' => $this->ctx['store']->id,
        'total_amount' => 5000,
    ]);

    Payment::create([
        'order_id' => $order->id,
        'provider' => 'mock',
        'method' => PaymentMethod::CreditCard,
        'status' => PaymentStatus::Captured,
        'amount' => 5000,
        'currency' => 'EUR',
        'created_at' => now(),
    ]);

    $component = Livewire::test(OrderShow::class, ['order' => $order]);
    $component->set('refundAmount', 2000);
    $component->set('refundReason', 'Customer request');
    $component->call('createRefund');

    $order->refresh();
    expect($order->refunds)->toHaveCount(1);
    expect($order->refunds->first()->amount)->toBe(2000);
    expect($order->financial_status)->toBe(FinancialStatus::PartiallyRefunded);
});

it('does not allow confirming payment for non-bank-transfer orders', function () {
    $order = Order::factory()->paid()->create([
        'store_id' => $this->ctx['store']->id,
        'payment_method' => PaymentMethod::CreditCard,
    ]);

    $component = Livewire::test(OrderShow::class, ['order' => $order]);
    $component->call('confirmPayment');

    $component->assertDispatched('toast', fn ($name, $data) => $data['type'] === 'error');
});
