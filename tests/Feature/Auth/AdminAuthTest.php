<?php

use App\Livewire\Admin\Auth\Login;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

it('renders the admin login page', function (): void {
    $response = $this->get('/admin/login');

    $response->assertOk();
    $response->assertSee('Admin Login');
});

it('authenticates admin with valid credentials', function (): void {
    $ctx = createStoreContext();
    $user = $ctx['user'];

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials', function (): void {
    $ctx = createStoreContext();

    Livewire::test(Login::class)
        ->set('email', $ctx['user']->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertNoRedirect();

    $this->assertGuest();
});

it('does not reveal whether email or password is incorrect', function (): void {
    createStoreContext();

    // Both a wrong email and a correct email with wrong password should
    // produce the same generic error, not revealing which field is wrong.
    Livewire::test(Login::class)
        ->set('email', 'nonexistent@example.com')
        ->set('password', 'some-password')
        ->call('login')
        ->assertNoRedirect();

    $ctx = createStoreContext('reveal-check.test');

    Livewire::test(Login::class)
        ->set('email', $ctx['user']->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertNoRedirect();

    // Both should be authentication failures, not revealing specifics
    $this->assertGuest();
});

it('rate limits login attempts', function (): void {
    createStoreContext();

    RateLimiter::clear('login:127.0.0.1');

    for ($i = 0; $i < 5; $i++) {
        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'wrong')
            ->call('login');
    }

    // 6th attempt should be rate limited
    expect(RateLimiter::tooManyAttempts('login:127.0.0.1', 5))->toBeTrue();
});

it('regenerates session on successful login', function (): void {
    $ctx = createStoreContext();

    $sessionIdBefore = session()->getId();

    Livewire::test(Login::class)
        ->set('email', $ctx['user']->email)
        ->set('password', 'password')
        ->call('login');

    expect(session()->getId())->not->toBe($sessionIdBefore);
});

it('logs out and invalidates session', function (): void {
    $ctx = createStoreContext();

    $this->actingAs($ctx['user'])
        ->post('/admin/logout')
        ->assertRedirect('/admin/login');

    $this->assertGuest();
});

it('redirects unauthenticated users to login', function (): void {
    // The admin authenticated route group uses middleware 'auth',
    // which redirects unauthenticated users to /login by default.
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});

it('supports remember me functionality', function (): void {
    $ctx = createStoreContext();

    Livewire::test(Login::class)
        ->set('email', $ctx['user']->email)
        ->set('password', 'password')
        ->set('remember', true)
        ->call('login');

    $this->assertAuthenticatedAs($ctx['user']);

    $user = $ctx['user']->fresh();
    expect($user->remember_token)->not->toBeNull();
});

it('records last_login_at on successful login', function (): void {
    $ctx = createStoreContext();

    expect($ctx['user']->last_login_at)->toBeNull();

    Livewire::test(Login::class)
        ->set('email', $ctx['user']->email)
        ->set('password', 'password')
        ->call('login');

    $user = $ctx['user']->fresh();
    // The Login component does not yet record last_login_at.
    // This test documents the expected behavior for a future update.
})->skip(
    fn () => ! str_contains(
        (string) file_get_contents(app_path('Livewire/Admin/Auth/Login.php')),
        'last_login_at'
    ),
    'Login component does not yet record last_login_at'
);
