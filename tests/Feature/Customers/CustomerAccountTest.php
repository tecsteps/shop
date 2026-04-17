<?php

use App\Livewire\Storefront\Account\Dashboard;
use App\Livewire\Storefront\Account\Orders\Index as OrdersIndex;
use App\Livewire\Storefront\Account\Orders\Show as OrdersShow;
use App\Models\Customer;
use App\Models\Order;
use Livewire\Livewire;

it('renders the customer dashboard', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->withPassword()->create(['store_id' => $ctx['store']->id]);

    actingAsCustomer($customer, $ctx['store']);

    Livewire::test(Dashboard::class)
        ->assertOk()
        ->assertSee('My Account')
        ->assertSee($customer->email);
});

it('lists customer orders', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->withPassword()->create(['store_id' => $ctx['store']->id]);

    Order::factory()->count(3)->create([
        'store_id' => $ctx['store']->id,
        'customer_id' => $customer->id,
    ]);

    actingAsCustomer($customer, $ctx['store']);

    Livewire::test(OrdersIndex::class)
        ->assertOk()
        ->assertSee('Order History');
});

it('shows order detail', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->withPassword()->create(['store_id' => $ctx['store']->id]);

    $order = Order::factory()->create([
        'store_id' => $ctx['store']->id,
        'customer_id' => $customer->id,
        'order_number' => '#5001',
    ]);

    actingAsCustomer($customer, $ctx['store']);

    Livewire::test(OrdersShow::class, ['orderNumber' => '#5001'])
        ->assertOk()
        ->assertSee('#5001');
});

it('prevents accessing another customers orders', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->withPassword()->create(['store_id' => $ctx['store']->id]);
    $otherCustomer = Customer::factory()->withPassword()->create(['store_id' => $ctx['store']->id]);

    $order = Order::factory()->create([
        'store_id' => $ctx['store']->id,
        'customer_id' => $otherCustomer->id,
        'order_number' => '#6001',
    ]);

    actingAsCustomer($customer, $ctx['store']);

    Livewire::test(OrdersShow::class, ['orderNumber' => '#6001'])
        ->assertNotFound();
});

it('redirects unauthenticated requests to login', function () {
    $this->get('/account')
        ->assertRedirect(route('storefront.account.login'));
});

it('updates customer profile', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->withPassword()->create([
        'store_id' => $ctx['store']->id,
        'name' => 'Original Name',
        'marketing_opt_in' => false,
    ]);

    actingAsCustomer($customer, $ctx['store']);

    Livewire::test(Dashboard::class)
        ->set('name', 'Updated Name')
        ->set('marketingOptIn', true)
        ->call('updateProfile')
        ->assertHasNoErrors();

    $customer->refresh();
    expect($customer->name)->toBe('Updated Name')
        ->and($customer->marketing_opt_in)->toBeTrue();
});
