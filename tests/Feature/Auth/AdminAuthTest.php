<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

it('renders the admin login page', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('Login');
});

it('authenticates an admin user with valid credentials', function () {
    $context = createStoreContext();
    app()->forgetInstance('current_store');

    $response = $this->post('/admin/login', [
        'email' => $context['user']->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($context['user']);
});

it('rejects invalid credentials', function () {
    $user = User::factory()->create();

    $response = $this->from('/admin/login')->post('/admin/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect('/admin/login');
    $response->assertSessionHasErrors(['email' => 'Invalid credentials']);
    $this->assertGuest();
});

it('does not reveal whether email or password is incorrect', function () {
    $response = $this->from('/admin/login')->post('/admin/login', [
        'email' => 'does-not-exist@example.test',
        'password' => 'whatever-password',
    ]);

    $response->assertRedirect('/admin/login');
    $response->assertSessionHasErrors(['email' => 'Invalid credentials']);
});

it('rate limits login attempts', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertRedirect();
    }

    $this->post('/admin/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertTooManyRequests();
});

it('regenerates session on successful login', function () {
    $context = createStoreContext();
    app()->forgetInstance('current_store');

    $this->get('/admin/login');
    $previousSessionId = session()->getId();

    $this->post('/admin/login', [
        'email' => $context['user']->email,
        'password' => 'password',
    ]);

    expect(session()->getId())->not->toBe($previousSessionId);
});

it('logs out and invalidates session', function () {
    $context = createStoreContext();
    app()->forgetInstance('current_store');

    $response = actingAsAdmin($context['user'], $context['store'])->post('/admin/logout');

    $response->assertRedirect(route('admin.login'));
    $this->assertGuest();
});

it('redirects unauthenticated users to login', function () {
    $this->get('/admin')->assertRedirect(route('admin.login'));
});

it('supports remember me functionality', function () {
    $context = createStoreContext();
    app()->forgetInstance('current_store');

    $response = $this->post('/admin/login', [
        'email' => $context['user']->email,
        'password' => 'password',
        'remember' => 'on',
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    $response->assertCookie(Auth::guard('web')->getRecallerName());
});

it('records last_login_at on successful login', function () {
    $context = createStoreContext();
    app()->forgetInstance('current_store');

    $user = $context['user'];
    $user->forceFill(['last_login_at' => null])->save();

    $this->post('/admin/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $user->refresh();

    expect($user->last_login_at)->not->toBeNull();
    expect($user->last_login_at->diffInSeconds(now()))->toBeLessThan(5);
});
