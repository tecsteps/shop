<?php

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $ctx = createStoreContext('account-test.test');
    $this->store = $ctx['store'];

    $this->customer = Customer::create([
        'store_id' => $this->store->id,
        'email' => 'customer@example.com',
        'password_hash' => Hash::make('password'),
        'name' => 'Test Customer',
        'marketing_opt_in' => false,
    ]);
});

test('authenticated customer can view dashboard', function () {
    $response = $this->actingAs($this->customer, 'customer')
        ->get('http://account-test.test/account');

    $response->assertOk()
        ->assertSee('My Account')
        ->assertSee('Test Customer');
});

test('unauthenticated user is redirected to login', function () {
    $this->get('http://account-test.test/account')
        ->assertRedirect('/account/login');
});

test('dashboard shows recent orders', function () {
    Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
        'order_number' => '#TEST-001',
    ]);

    $this->actingAs($this->customer, 'customer')
        ->get('http://account-test.test/account')
        ->assertOk()
        ->assertSee('#TEST-001');
});

test('customer can view order history', function () {
    Order::factory()->count(3)->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
    ]);

    $this->actingAs($this->customer, 'customer')
        ->get('http://account-test.test/account/orders')
        ->assertOk()
        ->assertSee('Order History');
});

test('customer can view order detail', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
        'order_number' => '#DETAIL-001',
    ]);

    $this->actingAs($this->customer, 'customer')
        ->get('http://account-test.test/account/orders/'.urlencode($order->order_number))
        ->assertOk()
        ->assertSee('#DETAIL-001');
});

test('customer cannot view another customers order', function () {
    $otherCustomer = Customer::create([
        'store_id' => $this->store->id,
        'email' => 'other@example.com',
        'password_hash' => Hash::make('password'),
        'name' => 'Other Customer',
        'marketing_opt_in' => false,
    ]);

    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $otherCustomer->id,
        'order_number' => '#OTHER-001',
    ]);

    $this->actingAs($this->customer, 'customer')
        ->get('http://account-test.test/account/orders/'.urlencode($order->order_number))
        ->assertNotFound();
});
