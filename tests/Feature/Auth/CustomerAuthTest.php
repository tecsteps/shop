<?php

use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('renders the customer login page', function () {
    $ctx = createStoreContext('customer-store.test');

    $response = $this->get('http://customer-store.test/account/login');

    $response->assertStatus(200);
});

it('authenticates a customer with valid credentials', function () {
    $ctx = createStoreContext('customer-store.test');
    $customer = Customer::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'customer@example.com',
        'password_hash' => Hash::make('password'),
        'name' => 'Test Customer',
    ]);

    Livewire::test(CustomerLogin::class)
        ->set('email', 'customer@example.com')
        ->set('password', 'password')
        ->call('login');

    $this->assertAuthenticatedAs($customer, 'customer');
});

it('rejects invalid customer credentials', function () {
    $ctx = createStoreContext('customer-store.test');
    Customer::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'customer@example.com',
        'password_hash' => Hash::make('password'),
        'name' => 'Test Customer',
    ]);

    Livewire::test(CustomerLogin::class)
        ->set('email', 'customer@example.com')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest('customer');
});

it('scopes customer login to the current store', function () {
    $ctxA = createStoreContext('store-a.test');
    Customer::withoutGlobalScopes()->create([
        'store_id' => $ctxA['store']->id,
        'email' => 'customer@example.com',
        'password_hash' => Hash::make('password'),
        'name' => 'Test Customer',
    ]);

    $orgB = Organization::factory()->create();
    $storeB = Store::factory()->create(['organization_id' => $orgB->id]);
    StoreDomain::factory()->create([
        'store_id' => $storeB->id,
        'hostname' => 'store-b.test',
    ]);

    // Bind store B as current store so login scopes to it
    app()->instance('current_store', $storeB);

    Livewire::test(CustomerLogin::class)
        ->set('email', 'customer@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest('customer');
});

it('rate limits customer login attempts', function () {
    $ctx = createStoreContext('customer-store.test');

    for ($i = 0; $i < 5; $i++) {
        Livewire::test(CustomerLogin::class)
            ->set('email', 'wrong@example.com')
            ->set('password', 'wrong-password')
            ->call('login');
    }

    Livewire::test(CustomerLogin::class)
        ->set('email', 'wrong@example.com')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertStatus(429);
});

it('registers a new customer', function () {
    $ctx = createStoreContext('customer-store.test');

    Livewire::test(CustomerRegister::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $customer = Customer::withoutGlobalScopes()
        ->where('email', 'jane@example.com')
        ->where('store_id', $ctx['store']->id)
        ->first();

    expect($customer)->not->toBeNull();
    $this->assertAuthenticatedAs($customer, 'customer');

    $this->assertDatabaseHas('customers', [
        'store_id' => $ctx['store']->id,
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
    ]);
});

it('rejects duplicate email registration in the same store', function () {
    $ctx = createStoreContext('customer-store.test');
    Customer::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'existing@example.com',
        'name' => 'Existing',
        'password_hash' => Hash::make('password'),
    ]);

    Livewire::test(CustomerRegister::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'existing@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasErrors('email');

    $this->assertGuest('customer');
});

it('allows same email in different stores', function () {
    $ctxA = createStoreContext('store-a.test');
    Customer::withoutGlobalScopes()->create([
        'store_id' => $ctxA['store']->id,
        'email' => 'shared@example.com',
        'name' => 'Customer A',
        'password_hash' => Hash::make('password'),
    ]);

    $orgB = Organization::factory()->create();
    $storeB = Store::factory()->create(['organization_id' => $orgB->id]);
    StoreDomain::factory()->create([
        'store_id' => $storeB->id,
        'hostname' => 'store-b.test',
    ]);

    // Bind store B as current store
    app()->instance('current_store', $storeB);

    Livewire::test(CustomerRegister::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'shared@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $customer = Customer::withoutGlobalScopes()
        ->where('email', 'shared@example.com')
        ->where('store_id', $storeB->id)
        ->first();

    expect($customer)->not->toBeNull();
    $this->assertAuthenticatedAs($customer, 'customer');
});

it('logs out customer and redirects to login', function () {
    $ctx = createStoreContext('customer-store.test');
    $customer = Customer::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'test@example.com',
        'name' => 'Test',
        'password_hash' => Hash::make('password'),
    ]);

    $response = $this->actingAs($customer, 'customer')
        ->post('http://customer-store.test/account/logout');

    $response->assertRedirect('/account/login');
    $this->assertGuest('customer');
});
