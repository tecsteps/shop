<?php

use App\Livewire\Admin\Orders\Index as OrdersIndex;
use App\Livewire\Admin\Orders\Show as OrderShow;
use App\Models\Order;
use App\Models\OrderLine;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
});

it('renders the orders list page', function () {
    actingAsAdmin($this->user, $this->store)
        ->get(route('admin.orders.index'))
        ->assertOk();
});

it('lists orders for the current store', function () {
    Order::factory()->create([
        'store_id' => $this->store->id,
        'order_number' => '#A1001',
        'placed_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(OrdersIndex::class)
        ->assertSee('#A1001');
});

it('searches orders by order number', function () {
    Order::factory()->create([
        'store_id' => $this->store->id,
        'order_number' => '#A1001',
        'placed_at' => now(),
    ]);
    Order::factory()->create([
        'store_id' => $this->store->id,
        'order_number' => '#B2002',
        'placed_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(OrdersIndex::class)
        ->set('search', 'A1001')
        ->assertSee('#A1001')
        ->assertDontSee('#B2002');
});

it('renders the order detail page', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'order_number' => '#D1001',
        'placed_at' => now(),
    ]);
    OrderLine::factory()->create([
        'order_id' => $order->id,
        'title_snapshot' => 'Test Item',
    ]);

    Livewire::actingAs($this->user)
        ->test(OrderShow::class, ['order' => $order])
        ->assertSee('#D1001')
        ->assertSee('Test Item');
});

it('filters orders by status', function () {
    Order::factory()->paid()->create([
        'store_id' => $this->store->id,
        'order_number' => '#PAID1',
        'placed_at' => now(),
    ]);
    Order::factory()->cancelled()->create([
        'store_id' => $this->store->id,
        'order_number' => '#CANC1',
        'placed_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(OrdersIndex::class)
        ->set('statusFilter', 'paid')
        ->assertSee('#PAID1')
        ->assertDontSee('#CANC1');
});
