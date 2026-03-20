<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\Theme;
use App\Models\ThemeSettings;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

beforeEach(function () {
    $this->store = Store::factory()->create(['name' => 'Test Store']);
    StoreDomain::factory()->create([
        'store_id' => $this->store->id,
        'hostname' => 'test-store.test',
        'type' => 'storefront',
    ]);
    $theme = Theme::factory()->published()->create(['store_id' => $this->store->id]);
    ThemeSettings::factory()->create(['theme_id' => $theme->id]);
    app()->instance('current_store', $this->store);

    $this->customer = Customer::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'email' => 'customer@test.com',
        'password' => bcrypt('password'),
        'name' => 'John Doe',
    ]);
});

it('renders customer dashboard with customer name', function () {
    Auth::guard('customer')->login($this->customer);

    $response = $this->get('https://test-store.test/account');

    $response->assertOk();
    $response->assertSee('John');
});

it('lists customer orders on orders index', function () {
    Auth::guard('customer')->login($this->customer);

    $orders = Order::factory()->count(3)->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
    ]);

    $response = $this->get('https://test-store.test/account/orders');

    $response->assertOk();
    foreach ($orders as $order) {
        $response->assertSee($order->order_number);
    }
});

it('shows order detail with items and totals', function () {
    Auth::guard('customer')->login($this->customer);

    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
        'order_number' => '#1001',
        'subtotal_amount' => 5000,
        'total_amount' => 5975,
    ]);

    OrderLine::factory()->create([
        'order_id' => $order->id,
        'title_snapshot' => 'Classic Cotton Tee - Size: M',
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
    ]);

    $response = $this->get('https://test-store.test/account/orders/1001');

    $response->assertOk();
    $response->assertSee('#1001');
    $response->assertSee('Classic Cotton Tee');
});

it('prevents accessing another customers order', function () {
    Auth::guard('customer')->login($this->customer);

    $otherCustomer = Customer::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'email' => 'other@test.com',
        'password' => bcrypt('password'),
        'name' => 'Other Customer',
    ]);

    Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $otherCustomer->id,
        'order_number' => '#9999',
    ]);

    $response = $this->get('https://test-store.test/account/orders/9999');

    $response->assertNotFound();
});

it('redirects unauthenticated users to login', function () {
    $response = $this->get('https://test-store.test/account');
    $response->assertRedirect('/account/login');

    $response = $this->get('https://test-store.test/account/orders');
    $response->assertRedirect('/account/login');

    $response = $this->get('https://test-store.test/account/addresses');
    $response->assertRedirect('/account/login');
});

it('updates customer profile', function () {
    Auth::guard('customer')->login($this->customer);

    Livewire::test(\App\Livewire\Storefront\Account\Dashboard::class)
        ->set('name', 'Jane Doe')
        ->set('marketingOptIn', true)
        ->call('updateProfile')
        ->assertHasNoErrors();

    $this->customer->refresh();
    expect($this->customer->name)->toBe('Jane Doe');
    expect($this->customer->marketing_opt_in)->toBeTrue();
});
