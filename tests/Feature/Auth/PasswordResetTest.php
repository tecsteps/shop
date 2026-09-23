<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('admin reset password link screen can be rendered', function () {
    $response = $this->get(route('admin.password.request'));

    $response->assertOk();
});

test('admin reset password link can be requested without exposing account existence', function () {
    Notification::fake();

    $user = User::factory()->create();

    Livewire::test(\App\Livewire\Admin\Auth\ForgotPassword::class)
        ->set('email', $user->email)
        ->call('sendResetLink')
        ->assertSet('statusMessage', 'If an account with that email exists, we sent a password reset link.');

    Notification::assertSentTo($user, ResetPassword::class);
});

test('admin reset password form can be rendered', function () {
    $response = $this->get(route('admin.password.reset', ['token' => 'test-reset-token']));

    $response->assertOk();
});

test('admin password can be reset with a valid broker token', function () {
    $user = User::factory()->create();
    $token = Password::broker('users')->createToken($user);

    Livewire::withQueryParams(['email' => $user->email])
        ->test(\App\Livewire\Admin\Auth\ResetPassword::class, ['token' => $token])
        ->set('password', 'new-password-123')
        ->set('password_confirmation', 'new-password-123')
        ->call('resetPassword')
        ->assertRedirect(route('admin.login'));

    expect(Password::broker('users')->tokenExists($user->fresh(), $token))->toBeFalse();
});
