<?php

use App\Livewire\Admin\Customers\Show;
use App\Models\Customer;
use App\Models\CustomerAddress;
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

    $this->customer = Customer::factory()->create([
        'store_id' => $this->store->id,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'phone' => '+49123456789',
    ]);
});

test('customer show page can be rendered', function () {
    $response = $this->get(route('admin.customers.show', $this->customer));

    $response->assertOk();
    $response->assertSeeLivewire(Show::class);
});

test('customer show displays customer details', function () {
    Livewire::test(Show::class, ['customer' => $this->customer])
        ->assertSee('Jane Doe')
        ->assertSee('jane@example.com')
        ->assertSee('+49123456789');
});

test('customer show displays order stats', function () {
    Order::factory()->count(3)->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
        'total_amount' => 5000,
    ]);

    Livewire::test(Show::class, ['customer' => $this->customer])
        ->assertSee('3')
        ->assertSee('150.00 EUR');
});

test('customer show displays recent orders', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
        'order_number' => '3001',
    ]);

    Livewire::test(Show::class, ['customer' => $this->customer])
        ->assertSee('#3001');
});

test('customer show limits recent orders to 10', function () {
    Order::factory()->count(15)->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
    ]);

    Livewire::test(Show::class, ['customer' => $this->customer])
        ->assertViewHas('recentOrders', fn ($orders) => $orders->count() === 10);
});

test('customer show displays addresses', function () {
    CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
        'address1' => '123 Main Street',
        'city' => 'Berlin',
        'country' => 'Germany',
    ]);

    Livewire::test(Show::class, ['customer' => $this->customer])
        ->assertSee('123 Main Street')
        ->assertSee('Berlin')
        ->assertSee('Germany');
});

test('customer show displays default address badge', function () {
    CustomerAddress::factory()->default()->create([
        'customer_id' => $this->customer->id,
    ]);

    Livewire::test(Show::class, ['customer' => $this->customer])
        ->assertSee('Default');
});

test('customer show blocks access to customers from other stores', function () {
    $otherStore = Store::factory()->create();
    $otherCustomer = Customer::factory()->create(['store_id' => $otherStore->id]);

    Livewire::test(Show::class, ['customer' => $otherCustomer])
        ->assertStatus(404);
});

test('customer show displays zero stats for new customer', function () {
    Livewire::test(Show::class, ['customer' => $this->customer])
        ->assertViewHas('ordersCount', 0)
        ->assertViewHas('totalSpent', 0);
});
