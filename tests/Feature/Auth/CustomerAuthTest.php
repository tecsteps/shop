<?php

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;

it('renders the customer login page', function () {
    createStoreContext();

    $this->get('/account/login')->assertStatus(200)->assertSee('Login');
});

it('authenticates a customer with valid credentials', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id, 'email' => 'cust@example.com', 'password_hash' => bcrypt('password')]);

    $this->post('/account/login', ['email' => 'cust@example.com', 'password' => 'password'])
        ->assertRedirect(route('account.dashboard'));

    expect(Auth::guard('customer')->check())->toBeTrue();
});

it('rejects invalid customer credentials', function () {
    $ctx = createStoreContext();
    Customer::factory()->create(['store_id' => $ctx['store']->id, 'email' => 'cust@example.com', 'password_hash' => bcrypt('password')]);

    $this->from('/account/login')->post('/account/login', ['email' => 'cust@example.com', 'password' => 'wrong'])
        ->assertRedirect('/account/login')
        ->assertSessionHasErrors('email');
});

it('scopes customer login to the current store', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();
    Customer::factory()->create(['store_id' => $storeA->id, 'email' => 'cust@example.com', 'password_hash' => bcrypt('password')]);
    bindCurrentStore($storeB);

    $this->from('/account/login')->post('/account/login', ['email' => 'cust@example.com', 'password' => 'password'])
        ->assertRedirect('/account/login')
        ->assertSessionHasErrors('email');
});

it('rate limits customer login attempts', function () {
    createStoreContext();

    for ($i = 0; $i < 5; $i++) {
        $this->post('/account/login', ['email' => 'x@example.com', 'password' => 'wrong']);
    }

    $this->post('/account/login', ['email' => 'x@example.com', 'password' => 'wrong'])->assertStatus(429);
});

it('registers a new customer', function () {
    $ctx = createStoreContext();

    $this->post('/account/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect(route('account.dashboard'));

    expect(Customer::where('store_id', $ctx['store']->id)->where('email', 'jane@example.com')->exists())->toBeTrue();
    expect(Auth::guard('customer')->check())->toBeTrue();
});

it('rejects duplicate email registration in the same store', function () {
    $ctx = createStoreContext();
    Customer::factory()->create(['store_id' => $ctx['store']->id, 'email' => 'dup@example.com']);

    $this->from('/account/register')->post('/account/register', [
        'name' => 'Jane',
        'email' => 'dup@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect('/account/register')->assertSessionHasErrors('email');
});

it('allows same email in different stores', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();
    Customer::factory()->create(['store_id' => $storeA->id, 'email' => 'same@example.com']);
    bindCurrentStore($storeB);

    $this->post('/account/register', [
        'name' => 'Jane',
        'email' => 'same@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect(route('account.dashboard'));

    expect(Customer::where('store_id', $storeB->id)->where('email', 'same@example.com')->exists())->toBeTrue();
});

it('logs out customer and redirects to login', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);
    $this->actingAs($customer, 'customer');

    $this->post('/account/logout')->assertRedirect(route('account.login'));

    expect(Auth::guard('customer')->check())->toBeFalse();
});
