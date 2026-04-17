<?php

use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Models\Customer;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Support\Facades\Hash;

it('renders the customer login page', function () {
    $ctx = createStoreContext('customer-login.test');

    $response = $this->get('http://customer-login.test/account/login');

    $response->assertStatus(200);
});

it('authenticates a customer with valid credentials', function () {
    $ctx = createStoreContext('customer-auth.test');
    $customer = Customer::factory()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'customer@example.com',
        'password' => Hash::make('password'),
    ]);

    \Livewire\Livewire::test(CustomerLogin::class)
        ->set('email', 'customer@example.com')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect('/account');

    $this->assertAuthenticatedAs($customer, 'customer');
});

it('rejects invalid customer credentials', function () {
    $ctx = createStoreContext('customer-reject.test');
    Customer::factory()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'customer@example.com',
        'password' => Hash::make('password'),
    ]);

    \Livewire\Livewire::test(CustomerLogin::class)
        ->set('email', 'customer@example.com')
        ->set('password', 'wrong-password')
        ->call('authenticate')
        ->assertHasErrors('email');
});

it('scopes customer login to the current store', function () {
    $ctxA = createStoreContext('store-a-auth.test');
    Customer::factory()->create([
        'store_id' => $ctxA['store']->id,
        'email' => 'shared@example.com',
        'password' => Hash::make('password'),
    ]);

    $storeB = Store::factory()->create(['organization_id' => $ctxA['organization']->id]);
    StoreDomain::factory()->create([
        'store_id' => $storeB->id,
        'hostname' => 'store-b-auth.test',
    ]);

    // Switch to store B context
    app()->instance('current_store', $storeB);

    \Livewire\Livewire::test(CustomerLogin::class)
        ->set('email', 'shared@example.com')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors('email');

    $this->assertGuest('customer');
});

it('rate limits customer login attempts', function () {
    $ctx = createStoreContext('customer-rate.test');

    for ($i = 0; $i < 6; $i++) {
        \Livewire\Livewire::test(CustomerLogin::class)
            ->set('email', 'test@example.com')
            ->set('password', 'wrong')
            ->call('authenticate');
    }

    $component = \Livewire\Livewire::test(CustomerLogin::class)
        ->set('email', 'test@example.com')
        ->set('password', 'wrong')
        ->call('authenticate');

    $component->assertHasErrors('email');
});

it('registers a new customer', function () {
    $ctx = createStoreContext('customer-register.test');

    \Livewire\Livewire::test(CustomerRegister::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'securepassword')
        ->set('password_confirmation', 'securepassword')
        ->call('register')
        ->assertRedirect('/account');

    $this->assertDatabaseHas('customers', [
        'store_id' => $ctx['store']->id,
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
    ]);
    $this->assertAuthenticated('customer');
});

it('rejects duplicate email registration in the same store', function () {
    $ctx = createStoreContext('customer-dup.test');
    Customer::factory()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'existing@example.com',
    ]);

    \Livewire\Livewire::test(CustomerRegister::class)
        ->set('name', 'Duplicate')
        ->set('email', 'existing@example.com')
        ->set('password', 'securepassword')
        ->set('password_confirmation', 'securepassword')
        ->call('register')
        ->assertHasErrors('email');
});

it('allows same email in different stores', function () {
    $ctxA = createStoreContext('store-a-reg.test');
    Customer::factory()->create([
        'store_id' => $ctxA['store']->id,
        'email' => 'shared@example.com',
    ]);

    $storeB = Store::factory()->create(['organization_id' => $ctxA['organization']->id]);
    StoreDomain::factory()->create([
        'store_id' => $storeB->id,
        'hostname' => 'store-b-reg.test',
    ]);
    app()->instance('current_store', $storeB);

    \Livewire\Livewire::test(CustomerRegister::class)
        ->set('name', 'Same Email')
        ->set('email', 'shared@example.com')
        ->set('password', 'securepassword')
        ->set('password_confirmation', 'securepassword')
        ->call('register')
        ->assertRedirect('/account');

    $this->assertDatabaseHas('customers', [
        'store_id' => $storeB->id,
        'email' => 'shared@example.com',
    ]);
});

it('logs out customer and redirects to login', function () {
    $ctx = createStoreContext('customer-logout.test');
    $customer = Customer::factory()->create([
        'store_id' => $ctx['store']->id,
    ]);

    $response = $this->actingAs($customer, 'customer')
        ->post('http://customer-logout.test/account/logout');

    $response->assertRedirect(route('customer.login'));
    $this->assertGuest('customer');
});

it('redirects unauthenticated customers to login', function () {
    $ctx = createStoreContext('customer-unauth.test');

    $response = $this->get('http://customer-unauth.test/account');

    $response->assertRedirect();
});
