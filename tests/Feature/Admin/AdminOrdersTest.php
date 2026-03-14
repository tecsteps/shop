<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Livewire\Admin\Orders\Index;
use App\Livewire\Admin\Orders\Show;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('orders index requires authentication', function () {
    $this->get(route('admin.orders.index'))
        ->assertRedirect(route('admin.login'));
});

test('orders index displays orders list', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['order_number' => '1001']);

    $this->actingAs($user)
        ->get(route('admin.orders.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

test('orders index can search by order number', function () {
    $user = User::factory()->create();
    $order1 = Order::factory()->create(['order_number' => '1001']);
    $order2 = Order::factory()->create(['order_number' => '2002']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('search', '1001')
        ->assertSee('1001')
        ->assertDontSee('2002');
});

test('orders index can filter by status', function () {
    $user = User::factory()->create();
    Order::factory()->paid()->create(['order_number' => '1001']);
    Order::factory()->create(['order_number' => '2002', 'status' => OrderStatus::Pending]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('statusFilter', 'paid')
        ->assertSee('1001')
        ->assertDontSee('2002');
});

test('orders index can sort by column', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('sortBy', 'total_amount')
        ->assertSet('sortField', 'total_amount')
        ->assertSet('sortDirection', 'desc');
});

test('order show requires authentication', function () {
    $order = Order::factory()->create();

    $this->get(route('admin.orders.show', $order))
        ->assertRedirect(route('admin.login'));
});

test('order show displays order details', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['name' => 'Jane Smith']);
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'order_number' => '1001',
        'total_amount' => 15000,
    ]);
    OrderLine::factory()->create([
        'order_id' => $order->id,
        'title_snapshot' => 'Blue Shirt',
        'quantity' => 2,
        'unit_price_amount' => 5000,
        'total_amount' => 10000,
    ]);
    Payment::factory()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::Captured,
        'amount' => 15000,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['order' => $order])
        ->assertSee('1001')
        ->assertSee('Blue Shirt')
        ->assertSee('Jane Smith');
});

test('order show can confirm bank transfer payment', function () {
    $ctx = createStoreContext();
    $order = Order::factory()->create([
        'store_id' => $ctx['store']->id,
        'payment_method' => PaymentMethod::BankTransfer,
        'financial_status' => FinancialStatus::Pending,
        'status' => OrderStatus::Pending,
    ]);
    Payment::factory()->create([
        'order_id' => $order->id,
        'method' => PaymentMethod::BankTransfer,
        'status' => PaymentStatus::Pending,
        'amount' => $order->total_amount,
    ]);

    Livewire::actingAs($ctx['user'])
        ->test(Show::class, ['order' => $order])
        ->call('confirmPayment');

    expect($order->fresh()->financial_status)->toBe(FinancialStatus::Paid);
});

test('order show displays fulfillment guard for unpaid orders', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'financial_status' => FinancialStatus::Pending,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['order' => $order])
        ->assertSee('Cannot create fulfillment');
});
