<?php

use App\Livewire\Storefront\Account\Addresses\Index as Addresses;
use App\Livewire\Storefront\Account\Auth\Login;
use App\Livewire\Storefront\Account\Auth\Register;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    StoreDomain::factory()->for($this->store)->create(['hostname' => 'acme-fashion.test']);
    app()->instance('current_store', $this->store);
});

it('redirects guests from account pages to the customer login', function () {
    $this->withHeader('Host', 'acme-fashion.test')->get('/account')
        ->assertRedirect('/account/login');
});

it('registers and signs in a store scoped customer', function () {
    Livewire::test(Register::class)
        ->set('name', 'Jane Doe')->set('email', 'jane@example.com')
        ->set('password', 'password')->set('password_confirmation', 'password')
        ->call('register')->assertRedirectToRoute('storefront.account.dashboard');

    $this->assertAuthenticated('customer');
    $this->assertDatabaseHas('customers', ['store_id' => $this->store->id, 'email' => 'jane@example.com']);
});

it('rejects invalid credentials and accepts valid credentials', function () {
    Customer::factory()->for($this->store)->create(['email' => 'customer@example.com', 'password_hash' => Hash::make('password')]);

    Livewire::test(Login::class)->set('email', 'customer@example.com')->set('password', 'wrong-password')->call('authenticate')->assertHasErrors('email');
    Livewire::test(Login::class)->set('email', 'customer@example.com')->set('password', 'password')->call('authenticate')->assertRedirectToRoute('storefront.account.dashboard');
    $this->assertAuthenticated('customer');
});

it('creates and updates only the signed in customers addresses', function () {
    $customer = Customer::factory()->for($this->store)->create();
    $this->actingAs($customer, 'customer');

    Livewire::test(Addresses::class)
        ->set('label', 'Home')->set('isDefault', true)
        ->set('address.first_name', 'Jane')->set('address.last_name', 'Doe')
        ->set('address.address1', 'Main Street 1')->set('address.city', 'Berlin')
        ->set('address.country_code', 'DE')->set('address.zip', '10115')
        ->call('save')->assertSee('Main Street 1');

    $this->assertDatabaseHas('customer_addresses', ['customer_id' => $customer->id, 'label' => 'Home', 'is_default' => true]);
});

it('links customer orders by database id while displaying the public order number', function () {
    $customer = Customer::factory()->for($this->store)->create();
    $order = Order::factory()->for($this->store)->for($customer)->create(['order_number' => '#1001']);
    $this->actingAs($customer, 'customer');

    $this->withHeader('Host', 'acme-fashion.test')->get('/account')
        ->assertOk()
        ->assertSee('/account/orders/'.$order->id, false)
        ->assertSee('#1001');

    $this->withHeader('Host', 'acme-fashion.test')->get('/account/orders/'.$order->id)
        ->assertOk()
        ->assertSee('Order #1001');
});
