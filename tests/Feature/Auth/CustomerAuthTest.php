<?php

use App\Livewire\Storefront\Account\Auth\Login;
use App\Livewire\Storefront\Account\Auth\Register;
use App\Models\Customer;
use Livewire\Livewire;

it('renders the customer login page', function () {
    $ctx = createStoreContext();

    $response = $this->withHeader('Host', 'shop.test')
        ->get('/account/login');

    $response->assertStatus(200);
});

it('authenticates a customer with valid credentials', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->for($ctx['store'])->create([
        'password' => bcrypt('secret123'),
    ]);

    Livewire::withHeaders(['Host' => 'shop.test'])
        ->test(Login::class)
        ->set('email', $customer->email)
        ->set('password', 'secret123')
        ->call('login')
        ->assertRedirect(route('storefront.account'));
});

it('rejects invalid customer credentials', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->for($ctx['store'])->create();

    Livewire::withHeaders(['Host' => 'shop.test'])
        ->test(Login::class)
        ->set('email', $customer->email)
        ->set('password', 'wrongpassword')
        ->call('login')
        ->assertHasErrors('email');
});

it('registers a new customer', function () {
    $ctx = createStoreContext();

    Livewire::withHeaders(['Host' => 'shop.test'])
        ->test(Register::class)
        ->set('first_name', 'Jane')
        ->set('last_name', 'Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register');

    expect(Customer::withoutGlobalScopes()->where('email', 'jane@example.com')->exists())->toBeTrue();
});

it('logs out customer and redirects to login', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->for($ctx['store'])->create();

    $response = actingAsCustomer($customer)
        ->withHeader('Host', 'shop.test')
        ->post('/account/logout');

    $response->assertRedirect('/account/login');
});

it('redirects unauthenticated requests to login', function () {
    $ctx = createStoreContext();

    $response = $this->withHeader('Host', 'shop.test')
        ->get('/account');

    $response->assertRedirect();
});
