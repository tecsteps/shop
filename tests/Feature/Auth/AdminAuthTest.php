<?php

use App\Livewire\Admin\Auth\ForgotPassword;
use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Auth\ResetPassword;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

test('renders the admin login page', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('Log in');
});

test('authenticates an admin user with valid credentials', function () {
    $store = $this->createStore();
    $user = $this->createUserWithRole($store, 'owner');

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($user, 'web');

    expect(session('current_store_id'))->toBe($store->id);
});

test('rejects invalid credentials with a generic error', function () {
    $store = $this->createStore();
    $user = $this->createUserWithRole($store, 'owner');

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertSet('errorMessage', 'Invalid credentials.');

    $this->assertGuest('web');
});

test('does not reveal whether the email or the password is incorrect', function () {
    $this->createStore();

    Livewire::test(Login::class)
        ->set('email', 'nobody@example.com')
        ->set('password', 'whatever-password')
        ->call('login')
        ->assertSet('errorMessage', 'Invalid credentials.');
});

test('rate limits login attempts', function () {
    $store = $this->createStore();
    $user = $this->createUserWithRole($store, 'owner');

    for ($attempt = 0; $attempt < 5; $attempt++) {
        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertSet('errorMessage', 'Invalid credentials.');
    }

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertStatus(429);
});

test('regenerates the session on successful login', function () {
    $store = $this->createStore();
    $user = $this->createUserWithRole($store, 'owner');

    $this->get('/admin/login');
    $sessionIdBefore = session()->getId();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login');

    expect(session()->getId())->not->toBe($sessionIdBefore);
});

test('logs out and invalidates the session', function () {
    $store = $this->createStore();
    $user = $this->createUserWithRole($store, 'owner');

    $response = $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->post('/admin/logout');

    $response->assertRedirect('/admin/login');

    expect($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and($response->headers->get('Pragma'))->toBe('no-cache');

    $this->assertGuest('web');
});

test('redirects unauthenticated users to the admin login', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

test('unverified admins are sent to the verification notice', function () {
    $store = $this->createStore();
    $user = $this->createUserWithRole($store, 'owner');
    $user->forceFill(['email_verified_at' => null])->save();

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->get('/admin')
        ->assertRedirect(route('verification.notice'));

    $this->actingAs($user)
        ->get('/email/verify')
        ->assertOk()
        ->assertSee('Verify your email');
});

test('authenticated admins reach the dashboard placeholder', function () {
    $store = $this->createStore();
    $user = $this->createUserWithRole($store, 'owner');

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->get('/admin')
        ->assertOk()
        ->assertSee('admin ok');
});

test('authenticated admins are redirected away from the login page', function () {
    $store = $this->createStore();
    $user = $this->createUserWithRole($store, 'owner');

    Livewire::actingAs($user);

    Livewire::test(Login::class)->assertRedirect('/admin');
});

test('supports remember me functionality', function () {
    $store = $this->createStore();
    $user = $this->createUserWithRole($store, 'owner');
    $user->forceFill(['remember_token' => null])->save();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->set('remember', true)
        ->call('login')
        ->assertRedirect('/admin');

    // The session guard cycles a fresh 60-char remember token into the DB.
    expect($user->refresh()->remember_token)->not->toBeNull()->toHaveLength(60);
});

test('records last_login_at on successful login', function () {
    $store = $this->createStore();
    $user = $this->createUserWithRole($store, 'owner');

    expect($user->last_login_at)->toBeNull();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login');

    expect($user->refresh()->last_login_at)->not->toBeNull();
});

test('user without a store membership cannot log in', function () {
    $this->createStore();
    $user = User::factory()->create();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertSet('errorMessage', 'You do not have access to any store.');

    $this->assertGuest('web');
});

test('forgot password sends a reset link and creates a token', function () {
    Notification::fake();

    $store = $this->createStore();
    $user = $this->createUserWithRole($store, 'owner');

    Livewire::test(ForgotPassword::class)
        ->set('email', $user->email)
        ->call('sendResetLink')
        ->assertSet('linkSent', true);

    $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user): bool {
        $mail = $notification->toMail($user);

        return str_contains((string) $mail->actionUrl, '/admin/reset-password/');
    });
});

test('forgot password response is generic for unknown emails', function () {
    Notification::fake();

    $this->createStore();

    Livewire::test(ForgotPassword::class)
        ->set('email', 'nobody@example.com')
        ->call('sendResetLink')
        ->assertSet('linkSent', true);

    Notification::assertNothingSent();
});

test('resets the password with a valid token', function () {
    $store = $this->createStore();
    $user = $this->createUserWithRole($store, 'owner');

    $token = Password::broker('users')->createToken($user);

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('email', $user->email)
        ->set('password', 'brand-new-password')
        ->set('password_confirmation', 'brand-new-password')
        ->call('resetPassword')
        ->assertRedirect(route('admin.login'));

    expect(Hash::check('brand-new-password', $user->refresh()->password_hash))->toBeTrue();
});

test('rejects an invalid reset token', function () {
    $store = $this->createStore();
    $user = $this->createUserWithRole($store, 'owner');

    Livewire::test(ResetPassword::class, ['token' => 'bogus-token'])
        ->set('email', $user->email)
        ->set('password', 'brand-new-password')
        ->set('password_confirmation', 'brand-new-password')
        ->call('resetPassword')
        ->assertSet('errorMessage', 'This password reset link is invalid or has expired.');

    expect(Hash::check('brand-new-password', $user->refresh()->password_hash))->toBeFalse();
});
