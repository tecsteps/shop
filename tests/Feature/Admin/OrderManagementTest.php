<?php

use App\Livewire\Admin\Orders\Index as OrdersIndex;
use App\Livewire\Admin\Orders\Show as OrdersShow;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    session()->put('current_store_id', $this->context['store']->id);
});

it('renders the orders index', function () {
    $this->actingAs($this->context['user'])
        ->get('/admin/orders')
        ->assertOk()
        ->assertSeeLivewire(OrdersIndex::class);
});

it('lists orders for the store', function () {
    $customer = Customer::factory()->create(['store_id' => $this->context['store']->id]);

    Order::factory()->paid()->create([
        'store_id' => $this->context['store']->id,
        'customer_id' => $customer->id,
        'order_number' => '#1001',
    ]);

    Livewire::actingAs($this->context['user'])
        ->test(OrdersIndex::class)
        ->assertSee('#1001');
});

it('searches orders by number', function () {
    $customer = Customer::factory()->create(['store_id' => $this->context['store']->id]);

    Order::factory()->paid()->create([
        'store_id' => $this->context['store']->id,
        'customer_id' => $customer->id,
        'order_number' => '#2001',
    ]);
    Order::factory()->paid()->create([
        'store_id' => $this->context['store']->id,
        'customer_id' => $customer->id,
        'order_number' => '#3001',
    ]);

    Livewire::actingAs($this->context['user'])
        ->test(OrdersIndex::class)
        ->set('search', '#2001')
        ->assertSee('#2001')
        ->assertDontSee('#3001');
});

it('renders the order detail page', function () {
    $customer = Customer::factory()->create(['store_id' => $this->context['store']->id]);

    $order = Order::factory()->paid()->create([
        'store_id' => $this->context['store']->id,
        'customer_id' => $customer->id,
        'order_number' => '#4001',
        'total_amount' => 9999,
    ]);

    OrderLine::factory()->create([
        'order_id' => $order->id,
        'title_snapshot' => 'Widget X',
    ]);

    Livewire::actingAs($this->context['user'])
        ->test(OrdersShow::class, ['orderId' => $order->id])
        ->assertSee('#4001')
        ->assertSee('Widget X')
        ->assertSee('$99.99');
});

it('filters orders by status', function () {
    $customer = Customer::factory()->create(['store_id' => $this->context['store']->id]);

    Order::factory()->paid()->create([
        'store_id' => $this->context['store']->id,
        'customer_id' => $customer->id,
        'order_number' => '#5001',
    ]);
    Order::factory()->cancelled()->create([
        'store_id' => $this->context['store']->id,
        'customer_id' => $customer->id,
        'order_number' => '#5002',
    ]);

    Livewire::actingAs($this->context['user'])
        ->test(OrdersIndex::class)
        ->set('statusFilter', 'paid')
        ->assertSee('#5001')
        ->assertDontSee('#5002');
});
