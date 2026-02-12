<?php

use App\Livewire\Admin\Orders\Index;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->store = Store::factory()->create();
    $this->user->stores()->attach($this->store->id, ['role' => 'owner']);
    $this->actingAs($this->user);
    session(['current_store_id' => $this->store->id]);
    app()->instance('current_store', $this->store);
});

test('orders index page can be rendered', function () {
    $response = $this->get(route('admin.orders.index'));

    $response->assertOk();
    $response->assertSeeLivewire(Index::class);
});

test('orders index displays orders', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
        'order_number' => '1234',
    ]);

    Livewire::test(Index::class)
        ->assertSee('#1234')
        ->assertSee($customer->full_name);
});

test('orders index can search by order number', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
        'order_number' => '5555',
    ]);
    Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
        'order_number' => '6666',
    ]);

    Livewire::test(Index::class)
        ->set('search', '5555')
        ->assertSee('#5555')
        ->assertDontSee('#6666');
});

test('orders index can search by customer name', function () {
    $alice = Customer::factory()->create([
        'store_id' => $this->store->id,
        'first_name' => 'Alice',
        'last_name' => 'Wonder',
    ]);
    $bob = Customer::factory()->create([
        'store_id' => $this->store->id,
        'first_name' => 'Bob',
        'last_name' => 'Builder',
    ]);

    Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $alice->id,
        'order_number' => '7001',
    ]);
    Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $bob->id,
        'order_number' => '7002',
    ]);

    Livewire::test(Index::class)
        ->set('search', 'Alice')
        ->assertSee('#7001')
        ->assertDontSee('#7002');
});

test('orders index can filter by status', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
        'order_number' => '8001',
        'status' => 'paid',
    ]);
    Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
        'order_number' => '8002',
        'status' => 'cancelled',
    ]);

    Livewire::test(Index::class)
        ->set('status', 'paid')
        ->assertSee('#8001')
        ->assertDontSee('#8002');
});

test('orders index can filter by financial status', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
        'order_number' => '9001',
        'financial_status' => 'paid',
    ]);
    Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
        'order_number' => '9002',
        'financial_status' => 'refunded',
    ]);

    Livewire::test(Index::class)
        ->set('financialStatus', 'paid')
        ->assertSee('#9001')
        ->assertDontSee('#9002');
});

test('orders index paginates at 15 per page', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    Order::factory()->count(20)->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
    ]);

    Livewire::test(Index::class)
        ->assertViewHas('orders', fn ($orders) => $orders->count() === 15);
});

test('orders index does not show orders from other stores', function () {
    $otherStore = Store::factory()->create();
    $customer = Customer::factory()->create(['store_id' => $otherStore->id]);
    Order::factory()->create([
        'store_id' => $otherStore->id,
        'customer_id' => $customer->id,
        'order_number' => '0001',
    ]);

    Livewire::test(Index::class)
        ->assertDontSee('#0001');
});

test('orders index displays financial status badges', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
        'financial_status' => 'paid',
    ]);

    Livewire::test(Index::class)
        ->assertSeeHtml('Paid');
});

test('orders index displays formatted total amounts', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
        'total_amount' => 5497,
    ]);

    Livewire::test(Index::class)
        ->assertSee('54.97 EUR');
});
