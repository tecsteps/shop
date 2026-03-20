<?php

use App\Livewire\Storefront\Account\Auth\Login;
use App\Livewire\Storefront\Account\Auth\Register;
use App\Models\Customer;
use App\Models\Store;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('renders the customer login page', function () {
    $context = createStoreContext();
    $hostname = $context['domain']->hostname;

    $this->get("http://{$hostname}/account/login")
        ->assertOk();
});

it('authenticates a customer with valid credentials', function () {
    $context = createStoreContext();

    $customer = Customer::factory()->create([
        'store_id' => $context['store']->id,
        'email' => 'customer@example.com',
        'password' => Hash::make('password'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'customer@example.com')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect('/account');

    $this->assertAuthenticatedAs($customer, 'customer');
});

it('rejects invalid customer credentials', function () {
    $context = createStoreContext();

    Customer::factory()->create([
        'store_id' => $context['store']->id,
        'email' => 'customer@example.com',
        'password' => Hash::make('password'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'customer@example.com')
        ->set('password', 'wrong-password')
        ->call('authenticate')
        ->assertHasErrors('email');

    $this->assertGuest('customer');
});

it('validates required fields on customer login', function () {
    createStoreContext();

    Livewire::test(Login::class)
        ->set('email', '')
        ->set('password', '')
        ->call('authenticate')
        ->assertHasErrors(['email', 'password']);
});

it('rate limits customer login attempts', function () {
    $context = createStoreContext();

    Customer::factory()->create([
        'store_id' => $context['store']->id,
        'email' => 'customer@example.com',
        'password' => Hash::make('password'),
    ]);

    $component = Livewire::test(Login::class);

    for ($i = 0; $i < 5; $i++) {
        $component->set('email', 'customer@example.com')
            ->set('password', 'wrong')
            ->call('authenticate');
    }

    $component->set('email', 'customer@example.com')
        ->set('password', 'wrong')
        ->call('authenticate')
        ->assertHasErrors('email');
});

it('renders the customer registration page', function () {
    $context = createStoreContext();
    $hostname = $context['domain']->hostname;

    $this->get("http://{$hostname}/account/register")
        ->assertOk();
});

it('registers a new customer', function () {
    $context = createStoreContext();

    Livewire::test(Register::class)
        ->set('name', 'New Customer')
        ->set('email', 'new@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertRedirect('/account');

    $this->assertDatabaseHas('customers', [
        'store_id' => $context['store']->id,
        'email' => 'new@example.com',
        'name' => 'New Customer',
    ]);
});

it('validates registration fields', function () {
    createStoreContext();

    Livewire::test(Register::class)
        ->set('name', '')
        ->set('email', '')
        ->set('password', '')
        ->set('password_confirmation', '')
        ->call('register')
        ->assertHasErrors(['name', 'email', 'password']);
});

it('prevents duplicate email registration per store', function () {
    $context = createStoreContext();

    Customer::factory()->create([
        'store_id' => $context['store']->id,
        'email' => 'existing@example.com',
    ]);

    Livewire::test(Register::class)
        ->set('name', 'Another')
        ->set('email', 'existing@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasErrors('email');
});

it('scopes customer auth to current store', function () {
    $context = createStoreContext();

    // Create customer in a different store
    $otherStore = Store::factory()->create([
        'organization_id' => $context['organization']->id,
    ]);

    Customer::factory()->create([
        'store_id' => $otherStore->id,
        'email' => 'customer@example.com',
        'password' => Hash::make('password'),
    ]);

    // Try to log in from the main store - should fail because the customer
    // belongs to a different store
    Livewire::test(Login::class)
        ->set('email', 'customer@example.com')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors('email');

    $this->assertGuest('customer');
});
