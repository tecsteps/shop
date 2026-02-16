<?php

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->ctx = createStoreContext();
    Cache::flush();
});

it('renders the customer dashboard', function () {
    $customer = Customer::factory()->for($this->ctx['store'])->create();

    $response = actingAsCustomer($customer)
        ->withHeader('Host', 'test-store.test')
        ->get('/account');

    $response->assertStatus(200);
});

it('lists customer orders', function () {
    $customer = Customer::factory()->for($this->ctx['store'])->create();
    Order::factory()->count(3)->for($this->ctx['store'])->create(['customer_id' => $customer->id]);

    $response = actingAsCustomer($customer)
        ->withHeader('Host', 'test-store.test')
        ->get('/account/orders');

    $response->assertStatus(200);
});

it('redirects unauthenticated requests to login', function () {
    $response = $this->withHeader('Host', 'test-store.test')
        ->get('/account');

    $response->assertRedirect();
});
