<?php

use App\Models\Customer;
use App\Models\Store;
use App\Models\StoreDomain;

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->domain = StoreDomain::factory()->for($this->store)->create();
    $this->baseUrl = 'http://'.$this->domain->hostname;
});

it('renders the customer login page', function () {
    $this->get($this->baseUrl.'/account/login')
        ->assertOk()
        ->assertSee('Login')
        ->assertSee('Email');
});

it('authenticates a customer with valid credentials', function () {
    $customer = Customer::factory()->for($this->store)->create();

    $response = $this->post($this->baseUrl.'/account/login', [
        'email' => $customer->email,
        'password' => 'password',
    ]);

    $response->assertRedirect('/account');
    $this->assertAuthenticatedAs($customer, 'customer');
});

it('rejects invalid customer credentials', function () {
    $customer = Customer::factory()->for($this->store)->create();

    $response = $this->from($this->baseUrl.'/account/login')->post($this->baseUrl.'/account/login', [
        'email' => $customer->email,
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect($this->baseUrl.'/account/login');
    $response->assertSessionHasErrors('email');
    $this->assertGuest('customer');
});

it('scopes customer login to the current store', function () {
    $storeB = Store::factory()->create();
    $domainB = StoreDomain::factory()->for($storeB)->create();

    $customer = Customer::factory()->for($this->store)->create();

    $response = $this->post('http://'.$domainB->hostname.'/account/login', [
        'email' => $customer->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest('customer');
});

it('rate limits customer login attempts', function () {
    $customer = Customer::factory()->for($this->store)->create();

    foreach (range(1, 5) as $attempt) {
        $this->post($this->baseUrl.'/account/login', [
            'email' => $customer->email,
            'password' => 'wrong-password',
        ])->assertRedirect();
    }

    $this->post($this->baseUrl.'/account/login', [
        'email' => $customer->email,
        'password' => 'wrong-password',
    ])->assertTooManyRequests();
});

it('registers a new customer', function () {
    $response = $this->post($this->baseUrl.'/account/register', [
        'name' => 'Jane Shopper',
        'email' => 'jane@example.test',
        'password' => 'super-secret',
        'password_confirmation' => 'super-secret',
    ]);

    $response->assertRedirect('/account');

    $this->assertDatabaseHas('customers', [
        'store_id' => $this->store->getKey(),
        'email' => 'jane@example.test',
        'name' => 'Jane Shopper',
    ]);

    $this->assertAuthenticated('customer');
});

it('rejects duplicate email registration in the same store', function () {
    Customer::factory()->for($this->store)->create(['email' => 'jane@example.test']);

    $response = $this->from($this->baseUrl.'/account/register')->post($this->baseUrl.'/account/register', [
        'name' => 'Jane Shopper',
        'email' => 'jane@example.test',
        'password' => 'super-secret',
        'password_confirmation' => 'super-secret',
    ]);

    $response->assertSessionHasErrors('email');
    expect(Customer::query()->withoutGlobalScopes()->where('email', 'jane@example.test')->count())->toBe(1);
});

it('allows same email in different stores', function () {
    $storeB = Store::factory()->create();
    $domainB = StoreDomain::factory()->for($storeB)->create();

    Customer::factory()->for($this->store)->create(['email' => 'jane@example.test']);

    $response = $this->post('http://'.$domainB->hostname.'/account/register', [
        'name' => 'Jane Shopper',
        'email' => 'jane@example.test',
        'password' => 'super-secret',
        'password_confirmation' => 'super-secret',
    ]);

    $response->assertRedirect('/account');

    $this->assertDatabaseHas('customers', [
        'store_id' => $storeB->getKey(),
        'email' => 'jane@example.test',
    ]);
});

it('logs out customer and redirects to login', function () {
    $customer = Customer::factory()->for($this->store)->create();

    $response = actingAsCustomer($customer)->post($this->baseUrl.'/account/logout');

    $response->assertRedirect($this->baseUrl.'/account/login');
    $this->assertGuest('customer');
});

it('merges guest cart into customer cart on login')->todo('Phase 4: carts do not exist yet');
