<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;

it('renders the admin login page', function () {
    $response = $this->get('/admin/login');

    $response->assertStatus(200);
    $response->assertSee('Login');
});

it('authenticates an admin user with valid credentials', function () {
    $ctx = createStoreContext('admin-auth.test');

    \Livewire\Livewire::test(AdminLogin::class)
        ->set('email', $ctx['user']->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($ctx['user'], 'web');
});

it('rejects invalid credentials', function () {
    $ctx = createStoreContext('admin-reject.test');

    \Livewire\Livewire::test(AdminLogin::class)
        ->set('email', $ctx['user']->email)
        ->set('password', 'wrong-password')
        ->call('authenticate')
        ->assertHasErrors('email');
});

it('does not reveal whether email or password is incorrect', function () {
    createStoreContext('admin-generic.test');

    \Livewire\Livewire::test(AdminLogin::class)
        ->set('email', 'nonexistent@example.com')
        ->set('password', 'wrong-password')
        ->call('authenticate')
        ->assertHasErrors(['email' => 'Invalid credentials']);
});

it('rate limits login attempts', function () {
    createStoreContext('admin-rate.test');

    for ($i = 0; $i < 6; $i++) {
        \Livewire\Livewire::test(AdminLogin::class)
            ->set('email', 'test@example.com')
            ->set('password', 'wrong')
            ->call('authenticate');
    }

    $component = \Livewire\Livewire::test(AdminLogin::class)
        ->set('email', 'test@example.com')
        ->set('password', 'wrong')
        ->call('authenticate');

    $component->assertHasErrors('email');
});

it('regenerates session on successful login', function () {
    $ctx = createStoreContext('admin-session.test');

    $oldSessionId = session()->getId();

    \Livewire\Livewire::test(AdminLogin::class)
        ->set('email', $ctx['user']->email)
        ->set('password', 'password')
        ->call('authenticate');

    expect(session()->getId())->not->toBe($oldSessionId);
});

it('logs out and invalidates session', function () {
    $ctx = createStoreContext('admin-logout.test');

    \Livewire\Livewire::actingAs($ctx['user'])
        ->test(\App\Livewire\Admin\Auth\Logout::class)
        ->call('logout')
        ->assertRedirect('/admin/login');

    $this->assertGuest('web');
});

it('redirects unauthenticated users to login', function () {
    $ctx = createStoreContext('admin-unauth.test');

    $response = $this->withSession(['current_store_id' => $ctx['store']->id])
        ->get('/admin');

    $response->assertRedirect('/login');
});

it('supports remember me functionality', function () {
    $ctx = createStoreContext('admin-remember.test');

    \Livewire\Livewire::test(AdminLogin::class)
        ->set('email', $ctx['user']->email)
        ->set('password', 'password')
        ->set('remember', true)
        ->call('authenticate');

    $this->assertAuthenticatedAs($ctx['user'], 'web');
    expect($ctx['user']->fresh()->remember_token)->not->toBeNull();
});

it('records last_login_at on successful login', function () {
    $ctx = createStoreContext('admin-lastlogin.test');

    expect($ctx['user']->last_login_at)->toBeNull();

    \Livewire\Livewire::test(AdminLogin::class)
        ->set('email', $ctx['user']->email)
        ->set('password', 'password')
        ->call('authenticate');

    expect($ctx['user']->fresh()->last_login_at)->not->toBeNull();
});
