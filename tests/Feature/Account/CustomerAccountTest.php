<?php

use App\Livewire\Storefront\Account\Dashboard;
use App\Livewire\Storefront\Account\Orders\Index as OrdersIndex;
use App\Livewire\Storefront\Account\Orders\Show as OrdersShow;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->customer = Customer::factory()->create([
        'store_id' => $this->context['store']->id,
        'email' => 'customer@test.com',
        'password' => Hash::make('password'),
        'name' => 'Test Customer',
    ]);
});

it('redirects unauthenticated users to customer login', function () {
    $hostname = $this->context['domain']->hostname;

    $this->get("http://{$hostname}/account")
        ->assertRedirect(route('customer.login'));
});

it('renders the account dashboard for authenticated customers', function () {
    $hostname = $this->context['domain']->hostname;

    $this->actingAs($this->customer, 'customer')
        ->get("http://{$hostname}/account")
        ->assertOk()
        ->assertSeeLivewire(Dashboard::class);
});

it('shows recent orders on dashboard', function () {
    $order = Order::factory()->paid()->create([
        'store_id' => $this->context['store']->id,
        'customer_id' => $this->customer->id,
        'order_number' => '#1001',
    ]);

    Livewire::actingAs($this->customer, 'customer')
        ->test(Dashboard::class)
        ->assertSee('#1001');
});

it('logs out a customer', function () {
    Livewire::actingAs($this->customer, 'customer')
        ->test(Dashboard::class)
        ->call('logout')
        ->assertRedirect(route('customer.login'));

    $this->assertGuest('customer');
});

it('renders the order history page', function () {
    $hostname = $this->context['domain']->hostname;

    $this->actingAs($this->customer, 'customer')
        ->get("http://{$hostname}/account/orders")
        ->assertOk()
        ->assertSeeLivewire(OrdersIndex::class);
});

it('lists orders for the authenticated customer', function () {
    Order::factory()->paid()->create([
        'store_id' => $this->context['store']->id,
        'customer_id' => $this->customer->id,
        'order_number' => '#2001',
    ]);

    Order::factory()->paid()->create([
        'store_id' => $this->context['store']->id,
        'customer_id' => $this->customer->id,
        'order_number' => '#2002',
    ]);

    Livewire::actingAs($this->customer, 'customer')
        ->test(OrdersIndex::class)
        ->assertSee('#2001')
        ->assertSee('#2002');
});

it('does not show orders from other customers', function () {
    $otherCustomer = Customer::factory()->create([
        'store_id' => $this->context['store']->id,
    ]);

    Order::factory()->paid()->create([
        'store_id' => $this->context['store']->id,
        'customer_id' => $otherCustomer->id,
        'order_number' => '#9999',
    ]);

    Livewire::actingAs($this->customer, 'customer')
        ->test(OrdersIndex::class)
        ->assertDontSee('#9999');
});

it('renders order detail page', function () {
    $order = Order::factory()->paid()->create([
        'store_id' => $this->context['store']->id,
        'customer_id' => $this->customer->id,
        'order_number' => '#3001',
        'total_amount' => 5000,
    ]);

    OrderLine::factory()->create([
        'order_id' => $order->id,
        'title_snapshot' => 'Test Product',
        'quantity' => 2,
        'total_amount' => 5000,
    ]);

    Livewire::actingAs($this->customer, 'customer')
        ->test(OrdersShow::class, ['orderNumber' => '#3001'])
        ->assertSee('#3001')
        ->assertSee('Test Product');
});

it('blocks access to another customer order', function () {
    $otherCustomer = Customer::factory()->create([
        'store_id' => $this->context['store']->id,
    ]);

    Order::factory()->paid()->create([
        'store_id' => $this->context['store']->id,
        'customer_id' => $otherCustomer->id,
        'order_number' => '#5001',
    ]);

    expect(fn () => Livewire::actingAs($this->customer, 'customer')
        ->test(OrdersShow::class, ['orderNumber' => '#5001'])
    )->toThrow(ModelNotFoundException::class);
});

it('shows order summary totals', function () {
    Order::factory()->paid()->create([
        'store_id' => $this->context['store']->id,
        'customer_id' => $this->customer->id,
        'order_number' => '#4001',
        'subtotal_amount' => 4000,
        'discount_amount' => 500,
        'shipping_amount' => 799,
        'tax_amount' => 380,
        'total_amount' => 4679,
    ]);

    Livewire::actingAs($this->customer, 'customer')
        ->test(OrdersShow::class, ['orderNumber' => '#4001'])
        ->assertSee('$40.00')
        ->assertSee('$5.00')
        ->assertSee('$7.99')
        ->assertSee('$3.80')
        ->assertSee('$46.79');
});
