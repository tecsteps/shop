<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;

beforeEach(function () {
    $this->context = createStoreContext(['hostname' => 'acme-fashion.test']);
    $this->store = $this->context['store'];
    $this->customer = Customer::factory()->create(['store_id' => $this->store->id, 'name' => 'Jane Doe']);
});

it('renders the customer dashboard', function () {
    actingAsCustomer($this->customer);

    $this->get(storefrontUrl('acme-fashion.test', '/account'))
        ->assertOk()
        ->assertSee('Jane Doe');
});

it('lists customer orders', function () {
    actingAsCustomer($this->customer);

    foreach (['#2001', '#2002', '#2003'] as $number) {
        Order::factory()->for($this->store)->create([
            'customer_id' => $this->customer->id,
            'order_number' => $number,
        ]);
    }

    $response = $this->get(storefrontUrl('acme-fashion.test', '/account/orders'))->assertOk();

    foreach (['#2001', '#2002', '#2003'] as $number) {
        $response->assertSee($number);
    }
});

it('shows order detail', function () {
    actingAsCustomer($this->customer);

    $order = Order::factory()->for($this->store)->create([
        'customer_id' => $this->customer->id,
        'order_number' => '#2010',
        'total_amount' => 5000,
    ]);
    OrderLine::factory()->for($order)->create([
        'store_id' => $this->store->id,
        'title_snapshot' => 'Wool Scarf',
        'quantity' => 1,
        'total_amount' => 5000,
    ]);

    $this->get(storefrontUrl('acme-fashion.test', '/account/orders/2010'))
        ->assertOk()
        ->assertSee('#2010')
        ->assertSee('Wool Scarf')
        ->assertSee('50.00 USD', escape: false);
});

it('prevents accessing another customers orders', function () {
    $other = Customer::factory()->create(['store_id' => $this->store->id]);
    $otherOrder = Order::factory()->for($this->store)->create([
        'customer_id' => $other->id,
        'order_number' => '#9999',
    ]);

    actingAsCustomer($this->customer);

    $this->get(storefrontUrl('acme-fashion.test', '/account/orders/9999'))
        ->assertNotFound();
});

it('redirects unauthenticated requests to login', function () {
    $this->get(storefrontUrl('acme-fashion.test', '/account'))
        ->assertRedirect(route('account.login'));
});

it('scopes order history to the authenticated customer', function () {
    $other = Customer::factory()->create(['store_id' => $this->store->id]);
    Order::factory()->for($this->store)->create(['customer_id' => $other->id, 'order_number' => '#7777']);
    Order::factory()->for($this->store)->create(['customer_id' => $this->customer->id, 'order_number' => '#7001']);

    actingAsCustomer($this->customer);

    $this->get(storefrontUrl('acme-fashion.test', '/account/orders'))
        ->assertOk()
        ->assertSee('#7001')
        ->assertDontSee('#7777');
});
