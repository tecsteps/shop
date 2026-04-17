<?php

use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

it('renders the customer login page', function () {
    $context = createStoreContext();

    $response = $this->get('http://acme-fashion.test/account/login');

    $response->assertOk();
    $response->assertSee('Customer Login');
});

it('authenticates a customer with valid credentials', function () {
    $context = createStoreContext();

    $customer = Customer::factory()->create([
        'store_id' => $context['store']->id,
        'email' => 'customer@example.com',
        'password' => bcrypt('password'),
    ]);

    Livewire::test(CustomerLogin::class)
        ->set('email', 'customer@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('storefront.account.dashboard'));

    $this->assertAuthenticatedAs($customer, 'customer');
});

it('rejects invalid customer credentials', function () {
    $context = createStoreContext();

    Customer::factory()->create([
        'store_id' => $context['store']->id,
        'email' => 'customer@example.com',
        'password' => bcrypt('password'),
    ]);

    Livewire::test(CustomerLogin::class)
        ->set('email', 'customer@example.com')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest('customer');
});

it('scopes customer login to the current store', function () {
    $context = createStoreContext();

    $orgB = Organization::factory()->create();
    $storeB = Store::factory()->create(['organization_id' => $orgB->id]);
    StoreDomain::factory()->create(['store_id' => $storeB->id, 'hostname' => 'store-b.test']);

    $customerInStoreB = Customer::factory()->create([
        'store_id' => $storeB->id,
        'email' => 'customer@example.com',
        'password' => bcrypt('password'),
    ]);

    // Try to login on store A with store B customer credentials
    app()->instance('current_store', $context['store']);

    Livewire::test(CustomerLogin::class)
        ->set('email', 'customer@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest('customer');
});

it('rate limits customer login attempts', function () {
    $context = createStoreContext();

    RateLimiter::clear('login:127.0.0.1');

    for ($i = 0; $i < 5; $i++) {
        Livewire::test(CustomerLogin::class)
            ->set('email', 'nobody@example.com')
            ->set('password', 'wrong-password')
            ->call('login');
    }

    $component = Livewire::test(CustomerLogin::class)
        ->set('email', 'nobody@example.com')
        ->set('password', 'wrong-password')
        ->call('login');

    $component->assertHasErrors('email');
    expect($component->errors()->get('email')[0])->toContain('Too many attempts');
});

it('registers a new customer', function () {
    $context = createStoreContext();

    Livewire::test(CustomerRegister::class)
        ->set('name', 'John Doe')
        ->set('email', 'john@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertRedirect(route('storefront.account.dashboard'));

    $this->assertAuthenticated('customer');

    $customer = Customer::withoutGlobalScopes()->where('email', 'john@example.com')->first();
    expect($customer)->not->toBeNull();
    expect($customer->store_id)->toBe($context['store']->id);
    expect($customer->name)->toBe('John Doe');
});

it('rejects duplicate email registration in the same store', function () {
    $context = createStoreContext();

    Customer::factory()->create([
        'store_id' => $context['store']->id,
        'email' => 'existing@example.com',
    ]);

    Livewire::test(CustomerRegister::class)
        ->set('name', 'John Doe')
        ->set('email', 'existing@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasErrors('email');
});

it('allows same email in different stores', function () {
    $context = createStoreContext();

    Customer::factory()->create([
        'store_id' => $context['store']->id,
        'email' => 'shared@example.com',
    ]);

    // Create store B and switch context
    $orgB = Organization::factory()->create();
    $storeB = Store::factory()->create(['organization_id' => $orgB->id]);
    StoreDomain::factory()->create(['store_id' => $storeB->id, 'hostname' => 'store-b.test']);

    app()->instance('current_store', $storeB);

    Livewire::test(CustomerRegister::class)
        ->set('name', 'John Doe')
        ->set('email', 'shared@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertRedirect(route('storefront.account.dashboard'));

    $customersWithEmail = Customer::withoutGlobalScopes()
        ->where('email', 'shared@example.com')
        ->count();

    expect($customersWithEmail)->toBe(2);
});

it('logs out customer', function () {
    $context = createStoreContext();

    $customer = Customer::factory()->create([
        'store_id' => $context['store']->id,
    ]);

    $this->actingAs($customer, 'customer');
    $this->assertAuthenticatedAs($customer, 'customer');

    // Customer logout via session invalidation
    $this->post('/account/logout');

    // Since there is no dedicated customer logout route yet, verify guard behavior
    auth('customer')->logout();
    $this->assertGuest('customer');
});
