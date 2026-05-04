<?php

use App\Livewire\Storefront\Account\Auth\ForgotPassword as CustomerForgotPassword;
use App\Livewire\Storefront\Account\Auth\ResetPassword as CustomerResetPassword;
use App\Models\Customer;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Notifications\CustomerResetPassword as CustomerResetPasswordNotification;
use App\Services\CustomerPasswordResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    Cache::flush();
});

test('customer password reset pages render for the resolved storefront store', function (): void {
    $store = Store::factory()->create();
    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => 'customer-reset.test',
    ]);

    $this->get('http://customer-reset.test/account/forgot-password')
        ->assertSuccessful()
        ->assertSee('Reset password')
        ->assertSee('Send reset link');

    $this->get('http://customer-reset.test/account/reset-password/test-token?email=customer@example.test')
        ->assertSuccessful()
        ->assertSee('Set a new password')
        ->assertSee('Reset password');
});

test('customer reset links are sent generically and scoped to the current store', function (): void {
    Notification::fake();

    $store = Store::factory()->create();
    $otherStore = Store::factory()->create();

    $customer = Customer::factory()->create([
        'store_id' => $store->getKey(),
        'email' => 'customer@example.test',
    ]);

    $otherCustomer = Customer::factory()->create([
        'store_id' => $otherStore->getKey(),
        'email' => 'customer@example.test',
    ]);

    app()->instance('current_store', $store);

    Livewire::test(CustomerForgotPassword::class)
        ->set('email', 'CUSTOMER@example.test')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSee('If an account matches that email, a reset link has been sent.');

    Notification::assertSentTo(
        $customer,
        CustomerResetPasswordNotification::class,
        fn (CustomerResetPasswordNotification $notification): bool => strlen($notification->token) === 64
            && $notification->store->is($store),
    );

    Notification::assertNotSentTo($otherCustomer, CustomerResetPasswordNotification::class);

    $this->assertDatabaseHas('customer_password_reset_tokens', [
        'store_id' => $store->getKey(),
        'email' => $customer->email,
    ]);

    $this->assertDatabaseMissing('customer_password_reset_tokens', [
        'store_id' => $otherStore->getKey(),
        'email' => $otherCustomer->email,
    ]);
});

test('unknown customer reset requests keep the generic response', function (): void {
    Notification::fake();

    $store = Store::factory()->create();

    app()->instance('current_store', $store);

    Livewire::test(CustomerForgotPassword::class)
        ->set('email', 'missing@example.test')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSee('If an account matches that email, a reset link has been sent.');

    Notification::assertNothingSent();

    expect(DB::table('customer_password_reset_tokens')->count())->toBe(0);
});

test('customer password reset updates the password and deletes the token', function (): void {
    Notification::fake();

    $store = Store::factory()->create();
    $customer = Customer::factory()->create([
        'store_id' => $store->getKey(),
        'email' => 'customer@example.test',
        'password' => 'old-password',
    ]);

    app()->instance('current_store', $store);

    app(CustomerPasswordResetService::class)->sendResetLink($store, $customer->email);

    $token = null;

    Notification::assertSentTo(
        $customer,
        CustomerResetPasswordNotification::class,
        function (CustomerResetPasswordNotification $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        },
    );

    Livewire::test(CustomerResetPassword::class, ['token' => $token])
        ->set('email', $customer->email)
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('resetPassword')
        ->assertHasNoErrors()
        ->assertRedirect(route('account.login', absolute: false));

    expect(Hash::check('new-password', $customer->refresh()->password_hash))->toBeTrue();

    $this->assertDatabaseMissing('customer_password_reset_tokens', [
        'store_id' => $store->getKey(),
        'email' => $customer->email,
    ]);

    app()->instance('current_store', $store);

    expect(Auth::guard('customer')->attempt([
        'email' => $customer->email,
        'password' => 'new-password',
    ]))->toBeTrue();
});

test('customer reset tokens cannot be reused across stores or after expiry', function (): void {
    $store = Store::factory()->create();
    $otherStore = Store::factory()->create();

    $customer = Customer::factory()->create([
        'store_id' => $store->getKey(),
        'email' => 'shared@example.test',
        'password' => 'old-password',
    ]);

    $otherCustomer = Customer::factory()->create([
        'store_id' => $otherStore->getKey(),
        'email' => 'shared@example.test',
        'password' => 'other-password',
    ]);

    DB::table('customer_password_reset_tokens')->insert([
        'store_id' => $store->getKey(),
        'email' => $customer->email,
        'token' => Hash::make('valid-token'),
        'created_at' => now(),
    ]);

    app()->instance('current_store', $otherStore);

    Livewire::test(CustomerResetPassword::class, ['token' => 'valid-token'])
        ->set('email', $otherCustomer->email)
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('resetPassword')
        ->assertHasErrors(['email']);

    expect(Hash::check('other-password', $otherCustomer->refresh()->password_hash))->toBeTrue();

    DB::table('customer_password_reset_tokens')
        ->where('store_id', $store->getKey())
        ->where('email', $customer->email)
        ->update(['created_at' => now()->subMinutes(61)]);

    app()->instance('current_store', $store);

    Livewire::test(CustomerResetPassword::class, ['token' => 'valid-token'])
        ->set('email', $customer->email)
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('resetPassword')
        ->assertHasErrors(['email']);

    expect(Hash::check('old-password', $customer->refresh()->password_hash))->toBeTrue();
});
