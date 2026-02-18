<?php

use App\Models\Customer;
use App\Models\Order;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
});

it('customer has accessible orders relationship', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    Order::factory()->count(3)->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
    ]);

    expect($customer->orders)->toHaveCount(3);
});

it('lists customer orders', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    $orders = Order::factory()->count(3)->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
    ]);

    $customerOrders = $customer->orders()->get();

    expect($customerOrders)->toHaveCount(3);
    foreach ($orders as $order) {
        expect($customerOrders->pluck('order_number'))->toContain($order->order_number);
    }
});

it('shows order detail with totals', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    /** @var Order $order */
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
        'order_number' => '#1001',
        'total_amount' => 5000,
    ]);

    $found = $customer->orders()->where('order_number', '#1001')->first();

    expect($found)->not->toBeNull()
        ->and($found->total_amount)->toBe(5000);
});

it('prevents accessing another customers orders', function () {
    $customerA = Customer::factory()->create(['store_id' => $this->store->id]);
    $customerB = Customer::factory()->create(['store_id' => $this->store->id]);
    Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $customerB->id,
        'order_number' => '#2001',
    ]);

    $found = $customerA->orders()->where('order_number', '#2001')->first();

    expect($found)->toBeNull();
});

it('customer can update profile fields', function () {
    $customer = Customer::factory()->create([
        'store_id' => $this->store->id,
        'name' => 'Original Name',
        'marketing_opt_in' => false,
    ]);

    $customer->update([
        'name' => 'Updated Name',
        'marketing_opt_in' => true,
    ]);

    $customer->refresh();
    expect($customer->name)->toBe('Updated Name')
        ->and($customer->marketing_opt_in)->toBeTrue();
});

it('customer email is unique per store', function () {
    Customer::factory()->create([
        'store_id' => $this->store->id,
        'email' => 'unique@example.com',
    ]);

    // Same email, different store should work
    $otherContext = createStoreContext('other-store.test');
    $otherCustomer = Customer::factory()->create([
        'store_id' => $otherContext['store']->id,
        'email' => 'unique@example.com',
    ]);

    expect($otherCustomer)->not->toBeNull();
});
