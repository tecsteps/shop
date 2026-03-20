<?php

use App\Enums\FinancialStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
    $this->actingAs($this->user);
    session()->put('current_store_id', $this->store->id);
});

it('renders order list page', function () {
    $response = $this->get('/admin/orders');

    $response->assertSuccessful();
    $response->assertSee('Orders');
});

it('lists orders belonging to the store', function () {
    $orders = Order::factory()->count(3)->create([
        'store_id' => $this->store->id,
    ]);

    Livewire::test(\App\Livewire\Admin\Orders\Index::class)
        ->assertSee($orders->first()->order_number);
});

it('filters orders by financial status', function () {
    Order::factory()->paid()->create([
        'store_id' => $this->store->id,
        'order_number' => '#PAID-001',
    ]);
    Order::factory()->pending()->create([
        'store_id' => $this->store->id,
        'order_number' => '#PEND-001',
    ]);

    Livewire::test(\App\Livewire\Admin\Orders\Index::class)
        ->set('statusFilter', 'paid')
        ->assertSee('#PAID-001')
        ->assertDontSee('#PEND-001');
});

it('renders order detail page', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'order_number' => '#TEST-100',
    ]);

    OrderLine::factory()->create([
        'order_id' => $order->id,
        'title_snapshot' => 'Test Line Item',
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
    ]);

    $response = $this->get("/admin/orders/{$order->id}");

    $response->assertSuccessful();
    $response->assertSee('#TEST-100');
    $response->assertSee('Test Line Item');
});

it('confirms bank transfer payment on order', function () {
    $order = Order::factory()->pending()->create([
        'store_id' => $this->store->id,
        'payment_method' => PaymentMethod::BankTransfer,
    ]);

    Payment::create([
        'order_id' => $order->id,
        'store_id' => $this->store->id,
        'method' => PaymentMethod::BankTransfer->value,
        'status' => PaymentStatus::Pending->value,
        'amount' => $order->total_amount,
        'currency' => 'USD',
        'provider_ref' => 'BT-'.uniqid(),
        'provider_data_json' => [],
    ]);

    Livewire::test(\App\Livewire\Admin\Orders\Show::class, ['order' => $order])
        ->call('confirmPayment')
        ->assertDispatched('toast');

    $order->refresh();
    expect($order->financial_status)->toBe(FinancialStatus::Paid);
});
