<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;
use Livewire\Livewire;

it('renders the admin login page', function () {
    $response = $this->get('/admin/login');

    $response->assertStatus(200);
    $response->assertSee('Admin Login');
});

it('authenticates an admin user with valid credentials', function () {
    $ctx = createStoreContext();
    $user = $ctx['user'];

    Livewire::test(AdminLogin::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login');

    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials', function () {
    $ctx = createStoreContext();
    $user = $ctx['user'];

    Livewire::test(AdminLogin::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('does not reveal whether email or password is incorrect', function () {
    Livewire::test(AdminLogin::class)
        ->set('email', 'nonexistent@example.com')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('rate limits login attempts', function () {
    $ctx = createStoreContext();

    for ($i = 0; $i < 5; $i++) {
        Livewire::test(AdminLogin::class)
            ->set('email', 'wrong@example.com')
            ->set('password', 'wrong-password')
            ->call('login');
    }

    Livewire::test(AdminLogin::class)
        ->set('email', 'wrong@example.com')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertStatus(429);
});

it('regenerates session on successful login', function () {
    $ctx = createStoreContext();
    $user = $ctx['user'];

    $oldSessionId = session()->getId();

    Livewire::test(AdminLogin::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login');

    expect(session()->getId())->not->toBe($oldSessionId);
});

it('logs out and invalidates session', function () {
    $ctx = createStoreContext();
    $user = $ctx['user'];

    $this->actingAs($user);

    $response = $this->post('/admin/logout');

    $response->assertRedirect('/admin/login');
    $this->assertGuest();
});

it('redirects unauthenticated users to admin login', function () {
    $ctx = createStoreContext();

    $response = $this->withSession(['current_store_id' => $ctx['store']->id])
        ->get('/admin');

    $response->assertRedirect('/admin/login');
});

it('redirects authenticated users away from admin login page', function () {
    $ctx = createStoreContext();
    $user = $ctx['user'];

    $this->actingAs($user);

    $response = $this->get('/admin/login');

    $response->assertRedirect('/admin');
});

it('supports remember me functionality', function () {
    $ctx = createStoreContext();
    $user = $ctx['user'];

    Livewire::test(AdminLogin::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->set('remember', true)
        ->call('login');

    $this->assertAuthenticatedAs($user);
    $user->refresh();
    expect($user->remember_token)->not->toBeNull();
});

it('records last_login_at on successful login', function () {
    $ctx = createStoreContext();
    $user = $ctx['user'];

    expect($user->last_login_at)->toBeNull();

    Livewire::test(AdminLogin::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login');

    $user->refresh();
    expect($user->last_login_at)->not->toBeNull();
});
