<?php

use App\Models\Customer;
use App\Models\Order;

it('renders the customer dashboard', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id, 'name' => 'Jane Doe']);

    $this->actingAs($customer, 'customer')->get('/account')
        ->assertStatus(200);
});

it('lists customer orders', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);
    Order::factory()->count(3)->create(['store_id' => $ctx['store']->id, 'customer_id' => $customer->id]);

    $this->actingAs($customer, 'customer')->get('/account/orders')
        ->assertStatus(200);
});

it('shows order detail', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);
    $order = Order::factory()->create(['store_id' => $ctx['store']->id, 'customer_id' => $customer->id, 'order_number' => '#1001']);

    $this->actingAs($customer, 'customer')->get('/account/orders/'.rawurlencode($order->order_number))
        ->assertStatus(200);
});

it('prevents accessing another customers orders', function () {
    $ctx = createStoreContext();
    $customerA = Customer::factory()->create(['store_id' => $ctx['store']->id]);
    $customerB = Customer::factory()->create(['store_id' => $ctx['store']->id]);
    $order = Order::factory()->create(['store_id' => $ctx['store']->id, 'customer_id' => $customerB->id, 'order_number' => '#1001']);

    $this->actingAs($customerA, 'customer')->get('/account/orders/'.rawurlencode($order->order_number))
        ->assertStatus(404);
});

it('redirects unauthenticated requests to login', function () {
    createStoreContext();

    $this->get('/account')->assertRedirect(route('account.login'));
});
