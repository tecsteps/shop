<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

it('renders the admin login page', function () {
    $response = $this->get('/admin/login');

    $response->assertOk();
    $response->assertSee('Admin Login');
});

it('authenticates an admin user with valid credentials', function () {
    $context = createStoreContext();

    Livewire::test(AdminLogin::class)
        ->set('email', $context['user']->email)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($context['user']);
});

it('rejects invalid credentials', function () {
    $context = createStoreContext();

    Livewire::test(AdminLogin::class)
        ->set('email', $context['user']->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('does not reveal whether email or password is incorrect', function () {
    createStoreContext();

    $component = Livewire::test(AdminLogin::class)
        ->set('email', 'nonexistent@example.com')
        ->set('password', 'whatever')
        ->call('login');

    $component->assertHasErrors('email');
    expect($component->errors()->get('email'))->toContain('Invalid credentials.');
});

it('rate limits login attempts', function () {
    createStoreContext();

    RateLimiter::clear('login:127.0.0.1');

    for ($i = 0; $i < 5; $i++) {
        Livewire::test(AdminLogin::class)
            ->set('email', 'admin@example.com')
            ->set('password', 'wrong-password')
            ->call('login');
    }

    $component = Livewire::test(AdminLogin::class)
        ->set('email', 'admin@example.com')
        ->set('password', 'wrong-password')
        ->call('login');

    $component->assertHasErrors('email');
    expect($component->errors()->get('email')[0])->toContain('Too many attempts');
});

it('regenerates session on successful login', function () {
    $context = createStoreContext();

    $sessionIdBefore = session()->getId();

    Livewire::test(AdminLogin::class)
        ->set('email', $context['user']->email)
        ->set('password', 'password')
        ->call('login');

    expect(session()->getId())->not->toBe($sessionIdBefore);
});

it('logs out and invalidates session', function () {
    $context = createStoreContext();

    $response = $this->actingAs($context['user'])
        ->post('/admin/logout');

    $response->assertRedirect(route('admin.login'));

    $this->assertGuest();
});

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/admin');

    $response->assertRedirect(route('login'));
});

it('supports remember me functionality', function () {
    $context = createStoreContext();

    Livewire::test(AdminLogin::class)
        ->set('email', $context['user']->email)
        ->set('password', 'password')
        ->set('remember', true)
        ->call('login');

    $this->assertAuthenticatedAs($context['user']);

    $context['user']->refresh();
    expect($context['user']->remember_token)->not->toBeNull();
});

it('records last_login_at on successful login', function () {
    $context = createStoreContext();

    expect($context['user']->last_login_at)->toBeNull();

    Livewire::test(AdminLogin::class)
        ->set('email', $context['user']->email)
        ->set('password', 'password')
        ->call('login');

    $context['user']->refresh();
    expect($context['user']->last_login_at)->not->toBeNull();
});
