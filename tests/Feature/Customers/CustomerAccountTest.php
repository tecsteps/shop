<?php

use App\Livewire\Storefront\Account\Dashboard;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use Livewire\Livewire;

test('renders the customer dashboard', function () {
    $store = $this->createStore();
    $customer = Customer::factory()->create(['store_id' => $store->id, 'name' => 'Jane Doe']);

    $this->actingAs($customer, 'customer')
        ->get('http://'.$store->handle.'.test/account')
        ->assertOk()
        ->assertSee('Jane Doe')
        ->assertSee($customer->email);
});

test('dashboard shows recent orders', function () {
    $store = $this->createStore();
    $customer = Customer::factory()->create(['store_id' => $store->id]);

    Order::factory()->create([
        'store_id' => $store->id,
        'customer_id' => $customer->id,
        'order_number' => '#3101',
    ]);

    $this->actingAs($customer, 'customer')
        ->get('http://'.$store->handle.'.test/account')
        ->assertOk()
        ->assertSee('#3101');
});

test('lists only the customers own orders', function () {
    $store = $this->createStore();
    $customerA = Customer::factory()->create(['store_id' => $store->id]);
    $customerB = Customer::factory()->create(['store_id' => $store->id]);

    Order::factory()->create([
        'store_id' => $store->id,
        'customer_id' => $customerA->id,
        'order_number' => '#3102',
    ]);
    Order::factory()->create([
        'store_id' => $store->id,
        'customer_id' => $customerB->id,
        'order_number' => '#3103',
    ]);

    $this->actingAs($customerA, 'customer')
        ->get('http://'.$store->handle.'.test/account/orders')
        ->assertOk()
        ->assertSee('#3102')
        ->assertDontSee('#3103');
});

test('shows order detail with items and totals', function () {
    $store = $this->createStore();
    $customer = Customer::factory()->create(['store_id' => $store->id]);

    Order::factory()
        ->withLines([['quantity' => 2, 'unit_price_amount' => 1000, 'title_snapshot' => 'Everyday Tee']])
        ->create([
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'order_number' => '#3104',
        ]);

    $this->actingAs($customer, 'customer')
        ->get('http://'.$store->handle.'.test/account/orders/3104')
        ->assertOk()
        ->assertSee('#3104')
        ->assertSee('Everyday Tee')
        ->assertSee('20.00 USD');
});

test('prevents accessing another customers order', function () {
    $store = $this->createStore();
    $customerA = Customer::factory()->create(['store_id' => $store->id]);
    $customerB = Customer::factory()->create(['store_id' => $store->id]);

    Order::factory()->create([
        'store_id' => $store->id,
        'customer_id' => $customerB->id,
        'order_number' => '#3105',
    ]);

    $this->actingAs($customerA, 'customer')
        ->get('http://'.$store->handle.'.test/account/orders/3105')
        ->assertNotFound();
});

test('redirects unauthenticated requests to login', function () {
    $store = $this->createStore();

    $this->get('http://'.$store->handle.'.test/account')
        ->assertRedirect('http://'.$store->handle.'.test/account/login');
});

test('updates the customer profile', function () {
    $store = $this->createStore();
    $this->bindStore($store);

    $customer = Customer::factory()->create([
        'store_id' => $store->id,
        'name' => 'Jane',
        'marketing_opt_in' => false,
    ]);

    $this->actingAs($customer, 'customer');

    Livewire::test(Dashboard::class)
        ->set('name', 'Janet')
        ->set('marketing_opt_in', true)
        ->call('updateProfile')
        ->assertSet('profileSaved', true);

    expect($customer->refresh()->name)->toBe('Janet')
        ->and($customer->marketing_opt_in)->toBeTrue();
});

test('shows saved addresses on the address book page', function () {
    $store = $this->createStore();
    $customer = Customer::factory()->create(['store_id' => $store->id]);

    CustomerAddress::factory()->create([
        'customer_id' => $customer->id,
        'label' => 'Home',
        'address_json' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country' => 'Germany',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
    ]);

    $this->actingAs($customer, 'customer')
        ->get('http://'.$store->handle.'.test/account/addresses')
        ->assertOk()
        ->assertSee('123 Main St')
        ->assertSee('Home');
});
