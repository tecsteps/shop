<?php

use App\Livewire\Storefront\Account\Auth\Login;
use App\Livewire\Storefront\Account\Auth\Register;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

// Skip all tests if Customer model file does not exist
beforeEach(function (): void {
    if (! file_exists(app_path('Models/Customer.php'))) {
        $this->markTestSkipped('Customer model does not exist yet.');
    }
});

it('renders customer login page', function (): void {
    $ctx = createStoreContext('customer-login.test');

    $response = $this->get('http://customer-login.test/account/login');

    $response->assertOk();
    $response->assertSee('Log in to your account');
});

it('authenticates customer with valid credentials', function (): void {
    $ctx = createStoreContext('cust-auth.test');

    $customerClass = 'App\\Models\\Customer';
    $customer = $customerClass::create([
        'store_id' => $ctx['store']->id,
        'name' => 'Test Customer',
        'email' => 'customer@example.com',
        'password_hash' => Hash::make('password'),
    ]);

    Livewire::withHeaders(['Host' => 'cust-auth.test'])
        ->test(Login::class)
        ->set('email', 'customer@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect('/account');

    expect(auth()->guard('customer')->check())->toBeTrue();
    expect(auth()->guard('customer')->id())->toBe($customer->id);
});

it('rejects invalid credentials', function (): void {
    $ctx = createStoreContext('cust-reject.test');

    $customerClass = 'App\\Models\\Customer';
    $customerClass::create([
        'store_id' => $ctx['store']->id,
        'name' => 'Test Customer',
        'email' => 'customer@example.com',
        'password_hash' => Hash::make('password'),
    ]);

    Livewire::withHeaders(['Host' => 'cust-reject.test'])
        ->test(Login::class)
        ->set('email', 'customer@example.com')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertNoRedirect();

    expect(auth()->guard('customer')->check())->toBeFalse();
});

it('scopes customer login to current store', function (): void {
    $ctxA = createStoreContext('store-a-cust.test');
    $ctxB = createStoreContext('store-b-cust.test');

    $customerClass = 'App\\Models\\Customer';
    $customerClass::create([
        'store_id' => $ctxA['store']->id,
        'name' => 'Store A Customer',
        'email' => 'shared@example.com',
        'password_hash' => Hash::make('password'),
    ]);

    // Try to login on store B with store A credentials
    app()->instance('current_store', $ctxB['store']);

    Livewire::withHeaders(['Host' => 'store-b-cust.test'])
        ->test(Login::class)
        ->set('email', 'shared@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertNoRedirect();

    expect(auth()->guard('customer')->check())->toBeFalse();
});

it('rate limits customer login attempts', function (): void {
    createStoreContext('cust-rate.test');

    RateLimiter::clear('login:127.0.0.1');

    for ($i = 0; $i < 5; $i++) {
        Livewire::withHeaders(['Host' => 'cust-rate.test'])
            ->test(Login::class)
            ->set('email', 'rate@example.com')
            ->set('password', 'wrong')
            ->call('login');
    }

    // 6th attempt should be rate limited
    expect(RateLimiter::tooManyAttempts('login:127.0.0.1', 5))->toBeTrue();
});

it('registers a new customer', function (): void {
    $ctx = createStoreContext('cust-register.test');

    Livewire::withHeaders(['Host' => 'cust-register.test'])
        ->test(Register::class)
        ->set('name', 'New Customer')
        ->set('email', 'new@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertRedirect('/account');

    $this->assertDatabaseHas('customers', [
        'store_id' => $ctx['store']->id,
        'email' => 'new@example.com',
        'name' => 'New Customer',
    ]);

    expect(auth()->guard('customer')->check())->toBeTrue();
});

it('rejects duplicate email in same store', function (): void {
    $ctx = createStoreContext('cust-dup.test');

    $customerClass = 'App\\Models\\Customer';
    $customerClass::create([
        'store_id' => $ctx['store']->id,
        'name' => 'Existing',
        'email' => 'existing@example.com',
        'password_hash' => Hash::make('password'),
    ]);

    Livewire::withHeaders(['Host' => 'cust-dup.test'])
        ->test(Register::class)
        ->set('name', 'Duplicate')
        ->set('email', 'existing@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertHasErrors(['email']);
});

it('allows same email in different stores', function (): void {
    $ctxA = createStoreContext('multi-a.test');
    $ctxB = createStoreContext('multi-b.test');

    $customerClass = 'App\\Models\\Customer';
    $customerClass::create([
        'store_id' => $ctxA['store']->id,
        'name' => 'Store A Customer',
        'email' => 'shared@example.com',
        'password_hash' => Hash::make('password'),
    ]);

    // Register same email on store B
    app()->instance('current_store', $ctxB['store']);

    Livewire::withHeaders(['Host' => 'multi-b.test'])
        ->test(Register::class)
        ->set('name', 'Store B Customer')
        ->set('email', 'shared@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertRedirect('/account');

    $this->assertDatabaseCount('customers', 2);
});

it('logs out customer', function (): void {
    $ctx = createStoreContext('cust-logout.test');

    $customerClass = 'App\\Models\\Customer';
    $customer = $customerClass::create([
        'store_id' => $ctx['store']->id,
        'name' => 'Logout Customer',
        'email' => 'logout@example.com',
        'password_hash' => Hash::make('password'),
    ]);

    auth()->guard('customer')->login($customer);

    $this->post('http://cust-logout.test/account/logout')
        ->assertRedirect('/');

    expect(auth()->guard('customer')->check())->toBeFalse();
});

it('merges guest cart on login', function (): void {
    // Cart model does not exist yet - will be implemented in Phase 4
})->skip('Cart model does not exist yet - will be implemented in Phase 4');
