<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->customer = Customer::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);
});

it('renders customer dashboard with name and email', function () {
    Livewire::actingAs($this->customer, 'customer')
        ->test(\App\Livewire\Storefront\Account\Dashboard::class)
        ->assertSee('Jane Doe')
        ->assertSee('jane@example.com')
        ->assertStatus(200);
});

it('shows recent orders on dashboard', function () {
    $order = Order::factory()->paid()->create([
        'store_id' => $this->ctx['store']->id,
        'customer_id' => $this->customer->id,
        'order_number' => '2001',
        'total_amount' => 5999,
    ]);

    Livewire::actingAs($this->customer, 'customer')
        ->test(\App\Livewire\Storefront\Account\Dashboard::class)
        ->assertSee('#2001')
        ->assertStatus(200);
});

it('lists customer orders with pagination', function () {
    Order::factory()->count(3)->paid()->create([
        'store_id' => $this->ctx['store']->id,
        'customer_id' => $this->customer->id,
    ]);

    Livewire::actingAs($this->customer, 'customer')
        ->test(\App\Livewire\Storefront\Account\Orders\Index::class)
        ->assertStatus(200);
});

it('shows order detail with line items', function () {
    $order = Order::factory()->paid()->create([
        'store_id' => $this->ctx['store']->id,
        'customer_id' => $this->customer->id,
        'order_number' => '3001',
        'total_amount' => 4999,
    ]);

    OrderLine::factory()->create([
        'order_id' => $order->id,
        'title_snapshot' => 'Test Product',
        'variant_title_snapshot' => 'Large',
        'quantity' => 2,
        'unit_price_amount' => 2000,
        'subtotal_amount' => 4000,
        'total_amount' => 4000,
    ]);

    Livewire::actingAs($this->customer, 'customer')
        ->test(\App\Livewire\Storefront\Account\Orders\Show::class, ['orderNumber' => '3001'])
        ->assertSee('#3001')
        ->assertSee('Test Product')
        ->assertSee('Large')
        ->assertStatus(200);
});

it('prevents accessing another customer order', function () {
    $otherCustomer = Customer::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    Order::factory()->paid()->create([
        'store_id' => $this->ctx['store']->id,
        'customer_id' => $otherCustomer->id,
        'order_number' => '4001',
    ]);

    $this->actingAs($this->customer, 'customer')
        ->get(route('customer.orders.show', '4001'))
        ->assertNotFound();
});

it('redirects unauthenticated user to login', function () {
    $this->get(route('customer.dashboard'))
        ->assertRedirect(route('customer.login'));
});
