<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    StoreDomain::factory()->create([
        'store_id' => $this->store->id,
        'hostname' => 'shop.test',
        'type' => 'storefront',
        'is_primary' => true,
    ]);
    $this->customer = Customer::factory()->create([
        'store_id' => $this->store->id,
        'name' => 'Test Customer',
        'email' => 'test@example.com',
    ]);
});

test('shows the customer dashboard with recent orders', function () {
    $orders = Order::factory()
        ->count(3)
        ->create([
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
        ]);

    $response = $this->withHeader('Host', 'shop.test')
        ->actingAs($this->customer, 'customer')
        ->get('/account');

    $response->assertStatus(200);
    $response->assertSee('Test Customer');
    $response->assertSee($orders->first()->order_number);
});

test('shows empty state when no orders', function () {
    $response = $this->withHeader('Host', 'shop.test')
        ->actingAs($this->customer, 'customer')
        ->get('/account');

    $response->assertStatus(200);
    $response->assertSee('placed any orders yet');
});

test('lists paginated order history', function () {
    Order::factory()
        ->count(12)
        ->create([
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
        ]);

    $response = $this->withHeader('Host', 'shop.test')
        ->actingAs($this->customer, 'customer')
        ->get('/account/orders');

    $response->assertStatus(200);
    $response->assertSee('Order History');
});

test('shows order detail for own order', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
        'order_number' => '#1001',
    ]);

    OrderLine::factory()->create([
        'order_id' => $order->id,
        'title_snapshot' => 'Test Widget',
        'quantity' => 2,
        'unit_price_amount' => 1500,
        'total_amount' => 3000,
    ]);

    $response = $this->withHeader('Host', 'shop.test')
        ->actingAs($this->customer, 'customer')
        ->get('/account/orders/'.urlencode('#1001'));

    $response->assertStatus(200);
    $response->assertSee('#1001');
    $response->assertSee('Test Widget');
});

test('denies access to another customers order', function () {
    $otherCustomer = Customer::factory()->create([
        'store_id' => $this->store->id,
    ]);

    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $otherCustomer->id,
        'order_number' => '#2002',
    ]);

    $response = $this->withHeader('Host', 'shop.test')
        ->actingAs($this->customer, 'customer')
        ->get('/account/orders/'.urlencode('#2002'));

    $response->assertStatus(404);
});

test('requires authentication for account pages', function () {
    $routes = ['/account', '/account/orders', '/account/addresses'];

    foreach ($routes as $route) {
        $response = $this->withHeader('Host', 'shop.test')
            ->get($route);

        $response->assertRedirect();
    }
});

test('does not show orders from other stores', function () {
    $otherStore = Store::factory()->create();
    Order::factory()->create([
        'store_id' => $otherStore->id,
        'customer_id' => $this->customer->id,
        'order_number' => '#9999',
    ]);

    $response = $this->withHeader('Host', 'shop.test')
        ->actingAs($this->customer, 'customer')
        ->get('/account');

    $response->assertStatus(200);
    $response->assertDontSee('#9999');
});
