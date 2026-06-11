<?php

use App\Livewire\Storefront\Account\Dashboard;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Store;
use App\Models\StoreDomain;
use Livewire\Livewire;

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->domain = StoreDomain::factory()->for($this->store)->create();
    $this->baseUrl = 'http://'.$this->domain->hostname;
    $this->customer = Customer::factory()->for($this->store)->create(['name' => 'Jane Shopper']);
});

it('renders the customer dashboard', function () {
    actingAsCustomer($this->customer)
        ->get($this->baseUrl.'/account')
        ->assertOk()
        ->assertSee('Jane Shopper');
});

it('lists customer orders', function () {
    foreach (['#1001', '#1002', '#1003'] as $number) {
        Order::factory()->paid()->for($this->store)->create([
            'customer_id' => $this->customer->getKey(),
            'order_number' => $number,
        ]);
    }

    actingAsCustomer($this->customer)
        ->get($this->baseUrl.'/account/orders')
        ->assertOk()
        ->assertSee('#1001')
        ->assertSee('#1002')
        ->assertSee('#1003');
});

it('shows order detail', function () {
    $order = Order::factory()->paid()->for($this->store)->create([
        'customer_id' => $this->customer->getKey(),
        'order_number' => '#1001',
        'currency' => 'USD',
        'subtotal_amount' => 5000,
        'total_amount' => 5000,
    ]);

    OrderLine::factory()->for($order)->create([
        'title_snapshot' => 'Classic Tee',
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
    ]);

    actingAsCustomer($this->customer)
        ->get($this->baseUrl.'/account/orders/1001')
        ->assertOk()
        ->assertSee('#1001')
        ->assertSee('Classic Tee')
        ->assertSee('50.00 USD');
});

it('prevents accessing another customers orders', function () {
    $otherCustomer = Customer::factory()->for($this->store)->create();

    Order::factory()->paid()->for($this->store)->create([
        'customer_id' => $otherCustomer->getKey(),
        'order_number' => '#2001',
    ]);

    actingAsCustomer($this->customer)
        ->get($this->baseUrl.'/account/orders/2001')
        ->assertNotFound();
});

it('redirects unauthenticated requests to login', function () {
    $this->get($this->baseUrl.'/account')
        ->assertRedirect($this->baseUrl.'/account/login');
});

it('updates customer profile', function () {
    app()->instance('current_store', $this->store);

    actingAsCustomer($this->customer);

    Livewire::test(Dashboard::class)
        ->set('name', 'Jane Updated')
        ->set('marketingOptIn', true)
        ->call('updateProfile')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('customers', [
        'id' => $this->customer->getKey(),
        'name' => 'Jane Updated',
        'marketing_opt_in' => 1,
    ]);
});
