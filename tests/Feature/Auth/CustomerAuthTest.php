<?php

use App\Models\Customer;
use App\Models\Store;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('customer login screen can be rendered', function () {
    $ctx = createStoreContext();

    $response = $this->call('GET', 'http://'.$ctx['domain']->hostname.'/account/login');

    $response->assertOk();
});

test('customer register screen can be rendered', function () {
    $ctx = createStoreContext();

    $response = $this->call('GET', 'http://'.$ctx['domain']->hostname.'/account/register');

    $response->assertOk();
});

test('customer can authenticate via livewire login', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    $customer = Customer::factory()->create(['store_id' => $store->id]);

    Livewire::test(\App\Livewire\Storefront\Account\Auth\Login::class)
        ->set('email', $customer->email)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('customer.account'));

    $this->assertAuthenticatedAs($customer, 'customer');
});

test('customer cannot authenticate with invalid password', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    $customer = Customer::factory()->create(['store_id' => $store->id]);

    Livewire::test(\App\Livewire\Storefront\Account\Auth\Login::class)
        ->set('email', $customer->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest('customer');
});

test('customer login is rate limited after 5 attempts', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    $customer = Customer::factory()->create(['store_id' => $store->id]);

    for ($i = 0; $i < 5; $i++) {
        Livewire::test(\App\Livewire\Storefront\Account\Auth\Login::class)
            ->set('email', $customer->email)
            ->set('password', 'wrong-password')
            ->call('login');
    }

    Livewire::test(\App\Livewire\Storefront\Account\Auth\Login::class)
        ->set('email', $customer->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');
});

test('customer can register a new account', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    Livewire::test(\App\Livewire\Storefront\Account\Auth\Register::class)
        ->set('name', 'Test Customer')
        ->set('email', 'customer@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertRedirect(route('customer.account'));

    $this->assertAuthenticatedAs(
        Customer::where('email', 'customer@example.com')->first(),
        'customer'
    );

    $this->assertDatabaseHas('customers', [
        'store_id' => $store->id,
        'email' => 'customer@example.com',
        'name' => 'Test Customer',
    ]);
});

test('customer cannot register with duplicate email in same store', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    Customer::factory()->create([
        'store_id' => $store->id,
        'email' => 'existing@example.com',
    ]);

    Livewire::test(\App\Livewire\Storefront\Account\Auth\Register::class)
        ->set('name', 'Test Customer')
        ->set('email', 'existing@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasErrors('email');
});

test('customer can register with same email in different store', function () {
    $store1 = Store::factory()->create();
    $store2 = Store::factory()->create();

    Customer::factory()->create([
        'store_id' => $store1->id,
        'email' => 'shared@example.com',
    ]);

    app()->instance('current_store', $store2);

    Livewire::test(\App\Livewire\Storefront\Account\Auth\Register::class)
        ->set('name', 'Test Customer')
        ->set('email', 'shared@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertRedirect(route('customer.account'));

    expect(Customer::where('email', 'shared@example.com')->count())->toBe(2);
});

test('customer can logout', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    $response = $this->actingAs($customer, 'customer')
        ->call('POST', 'http://'.$ctx['domain']->hostname.'/account/logout');

    $response->assertRedirect(route('customer.login'));
    $this->assertGuest('customer');
});
