<?php

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders the admin login page', function () {
    $response = $this->get('/admin/login');

    $response->assertOk();
    $response->assertSee('Login');
});

it('authenticates an admin user with valid credentials', function () {
    ['user' => $user] = $this->createStoreContext();

    $response = $this->post('/admin/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect('/admin');
    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials', function () {
    ['user' => $user] = $this->createStoreContext();

    $response = $this->from('/admin/login')->post('/admin/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
    $this->followRedirects($response)->assertSee('Invalid credentials');
});

it('does not reveal whether email or password is incorrect', function () {
    $response = $this->from('/admin/login')->post('/admin/login', [
        'email' => 'nobody@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->followRedirects($response)->assertSee('Invalid credentials');
});

it('rate limits login attempts', function () {
    ['user' => $user] = $this->createStoreContext();

    for ($i = 0; $i < 5; $i++) {
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'wrong-password']);
    }

    $this->post('/admin/login', ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertStatus(429);
});

it('regenerates session on successful login', function () {
    ['user' => $user] = $this->createStoreContext();
    $this->get('/admin/login');
    $before = session()->getId();

    $this->post('/admin/login', ['email' => $user->email, 'password' => 'password']);

    expect(session()->getId())->not->toBe($before);
});

it('logs out and invalidates session', function () {
    ['user' => $user] = $this->createStoreContext();

    $response = $this->actingAs($user)->post('/admin/logout');

    $response->assertRedirect('/admin/login');
    $this->assertGuest();
});

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/admin');

    $response->assertRedirect('/admin/login');
});

it('supports remember me functionality', function () {
    ['user' => $user] = $this->createStoreContext();

    $response = $this->post('/admin/login', [
        'email' => $user->email,
        'password' => 'password',
        'remember' => true,
    ]);

    $response->assertRedirect('/admin');

    $rememberCookies = collect($response->headers->getCookies())
        ->map(fn ($cookie): string => $cookie->getName())
        ->filter(fn (string $name): bool => str_starts_with($name, 'remember_web_'));

    expect($rememberCookies)->not->toBeEmpty();
});

it('records last_login_at on successful login', function () {
    ['user' => $user] = $this->createStoreContext();
    expect($user->last_login_at)->toBeNull();

    $this->post('/admin/login', ['email' => $user->email, 'password' => 'password']);

    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('selects the only store into the session on login', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();

    $response = $this->post('/admin/login', ['email' => $user->email, 'password' => 'password']);

    $response->assertRedirect('/admin');
    $response->assertSessionHas('current_store_id', $store->id);
});

it('requires email and password', function () {
    $response = $this->post('/admin/login', ['email' => '', 'password' => '']);

    $response->assertSessionHasErrors(['email', 'password']);
    $this->assertGuest();
});
