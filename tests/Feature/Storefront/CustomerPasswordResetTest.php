<?php

use App\Livewire\Storefront\Account\Auth\ForgotPassword;
use App\Livewire\Storefront\Account\Auth\ResetPassword;
use App\Models\Customer;
use App\Models\Store;
use App\Notifications\CustomerResetPasswordNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    $this->seed();
    app()->instance('current_store', Store::query()->where('handle', 'acme-fashion')->firstOrFail());
});

test('customer forgot password screen can be rendered', function () {
    $this->get('http://shop.test/forgot-password')
        ->assertOk()
        ->assertSee('Reset password');
});

test('customer reset link can be requested without revealing missing emails', function () {
    Notification::fake();

    $customer = Customer::withoutGlobalScopes()
        ->where('store_id', app('current_store')->id)
        ->where('email', 'jane@example.com')
        ->firstOrFail();

    Livewire::test(ForgotPassword::class)
        ->set('email', $customer->email)
        ->call('sendResetLink')
        ->assertHasNoErrors()
        ->assertSet('resetLinkSent', true);

    Notification::assertSentTo($customer, CustomerResetPasswordNotification::class, function (CustomerResetPasswordNotification $notification): bool {
        return $notification->token !== '';
    });

    Notification::fake();

    Livewire::test(ForgotPassword::class)
        ->set('email', 'missing@example.com')
        ->call('sendResetLink')
        ->assertHasNoErrors()
        ->assertSet('resetLinkSent', true);

    Notification::assertNothingSent();
});

test('customer reset links are scoped to the current store', function () {
    Notification::fake();

    $currentStore = app('current_store');
    $currentStoreCustomer = Customer::factory()->for($currentStore)->registered()->create([
        'email' => 'shared@example.com',
    ]);

    $otherCustomer = Customer::factory()->for(Store::factory())->registered()->create([
        'email' => 'shared@example.com',
    ]);

    Livewire::test(ForgotPassword::class)
        ->set('email', 'shared@example.com')
        ->call('sendResetLink')
        ->assertHasNoErrors();

    Notification::assertSentTo($currentStoreCustomer, CustomerResetPasswordNotification::class);
    Notification::assertNotSentTo($otherCustomer, CustomerResetPasswordNotification::class);

    expect(DB::table('customer_password_reset_tokens')
        ->where('store_id', $currentStore->id)
        ->where('email', 'shared@example.com')
        ->exists())->toBeTrue();
});

test('customer password can be reset with a valid store scoped token', function () {
    $customer = Customer::withoutGlobalScopes()
        ->where('store_id', app('current_store')->id)
        ->where('email', 'jane@example.com')
        ->firstOrFail();

    $token = Password::broker('customers')->createToken($customer);

    $this->get('http://shop.test/reset-password/'.$token.'?email='.$customer->email)
        ->assertOk()
        ->assertSee('Choose a new password');

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('email', $customer->email)
        ->set('password', 'new-password')
        ->set('passwordConfirmation', 'new-password')
        ->call('resetPassword')
        ->assertHasNoErrors()
        ->assertRedirect(route('storefront.account.login'));

    expect(Hash::check('new-password', $customer->fresh()->password_hash))->toBeTrue()
        ->and(DB::table('customer_password_reset_tokens')
            ->where('store_id', app('current_store')->id)
            ->where('email', $customer->email)
            ->exists())->toBeFalse()
        ->and(Auth::guard('customer')->attempt([
            'email' => $customer->email,
            'password' => 'new-password',
        ]))->toBeTrue();
});
