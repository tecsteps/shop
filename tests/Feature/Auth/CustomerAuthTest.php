<?php

use App\Livewire\Storefront\Account\Auth\Login;
use App\Livewire\Storefront\Account\Auth\Register;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext(['hostname' => 'acme-fashion.test']);
});

it('renders the customer login page', function () {
    $this->get(storefrontUrl('acme-fashion.test', '/account/login'))
        ->assertOk()
        ->assertSee('Log in');
});

it('authenticates a customer with valid credentials', function () {
    $customer = Customer::factory()->create([
        'store_id' => $this->context['store']->id,
        'password_hash' => Hash::make('secret-password'),
    ]);

    Livewire::test(Login::class)
        ->set('email', $customer->email)
        ->set('password', 'secret-password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('account.dashboard'));

    expect(auth('customer')->check())->toBeTrue();
});

it('rejects invalid customer credentials', function () {
    $customer = Customer::factory()->create([
        'store_id' => $this->context['store']->id,
        'password_hash' => Hash::make('secret-password'),
    ]);

    Livewire::test(Login::class)
        ->set('email', $customer->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    expect(auth('customer')->check())->toBeFalse();
});

it('scopes customer login to the current store', function () {
    $storeA = createStoreContext(['hostname' => 'store-a.test', 'bind' => false]);

    $customer = Customer::factory()->create([
        'store_id' => $storeA['store']->id,
        'password_hash' => Hash::make('secret-password'),
    ]);

    // Current store (from beforeEach) is the acme store, NOT store A.
    Livewire::test(Login::class)
        ->set('email', $customer->email)
        ->set('password', 'secret-password')
        ->call('login')
        ->assertHasErrors('email');

    expect(auth('customer')->check())->toBeFalse();
});

it('rate limits customer login attempts', function () {
    $customer = Customer::factory()->create([
        'store_id' => $this->context['store']->id,
        'password_hash' => Hash::make('secret-password'),
    ]);

    foreach (range(1, 5) as $attempt) {
        Livewire::test(Login::class)
            ->set('email', $customer->email)
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('email');
    }

    Livewire::test(Login::class)
        ->set('email', $customer->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertSee('Too many attempts', false);
});

it('registers a new customer', function () {
    Livewire::test(Register::class)
        ->set('name', 'New Customer')
        ->set('email', 'new@example.test')
        ->set('password', 'secret-password')
        ->set('password_confirmation', 'secret-password')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('account.dashboard'));

    $this->assertDatabaseHas('customers', [
        'store_id' => $this->context['store']->id,
        'email' => 'new@example.test',
    ]);
    expect(auth('customer')->check())->toBeTrue();
});

it('rejects duplicate email registration in the same store', function () {
    Customer::factory()->create([
        'store_id' => $this->context['store']->id,
        'email' => 'dupe@example.test',
    ]);

    Livewire::test(Register::class)
        ->set('name', 'Dupe')
        ->set('email', 'dupe@example.test')
        ->set('password', 'secret-password')
        ->set('password_confirmation', 'secret-password')
        ->call('register')
        ->assertHasErrors('email');
});

it('allows same email in different stores', function () {
    $storeA = createStoreContext(['hostname' => 'store-a.test', 'bind' => false]);
    Customer::factory()->create([
        'store_id' => $storeA['store']->id,
        'email' => 'shared@example.test',
    ]);

    // Current store is still the acme store from beforeEach.
    Livewire::test(Register::class)
        ->set('name', 'Shared')
        ->set('email', 'shared@example.test')
        ->set('password', 'secret-password')
        ->set('password_confirmation', 'secret-password')
        ->call('register')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('customers', [
        'store_id' => $this->context['store']->id,
        'email' => 'shared@example.test',
    ]);
});

it('logs out customer and redirects to login', function () {
    $customer = Customer::factory()->create(['store_id' => $this->context['store']->id]);
    actingAsCustomer($customer);

    $this->post(storefrontUrl('acme-fashion.test', '/account/logout'))
        ->assertRedirect(route('account.login'));

    expect(auth('customer')->check())->toBeFalse();
});

it('merges guest cart into customer cart on login', function () {
    // CartService and the carts table are introduced in Phase 4. The guest
    // cart merge-on-login behavior is covered by Phase 4's tests.
})->skip('Cart merge depends on CartService (Phase 4).');
