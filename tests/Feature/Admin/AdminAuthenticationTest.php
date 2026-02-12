<?php

use App\Models\Store;

it('renders the admin login page', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('Admin')
        ->assertSee('Log in');
});

it('redirects unauthenticated admin requests to admin login', function () {
    Store::factory()->create();

    $this->get('/admin')
        ->assertRedirect('/admin/login');
});

it('authenticates an admin with valid credentials', function () {
    $store = Store::factory()->create();
    $user = createStoreAdmin($store);

    $this->post('/admin/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect('/admin');

    $this->assertAuthenticatedAs($user);
    expect(session('current_store_id'))->toBe($store->id);
});

it('rejects invalid admin credentials', function () {
    $store = Store::factory()->create();
    $user = createStoreAdmin($store);

    $this->from('/admin/login')
        ->post('/admin/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
        ->assertRedirect('/admin/login')
        ->assertSessionHasErrors([
            'email' => 'Invalid credentials.',
        ]);

    $this->assertGuest();
});

it('rate limits admin login attempts', function () {
    $store = Store::factory()->create();
    $user = createStoreAdmin($store);

    foreach (range(1, 5) as $attempt) {
        $this->from('/admin/login')
            ->post('/admin/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors([
                'email' => 'Invalid credentials.',
            ]);
    }

    $this->from('/admin/login')
        ->post('/admin/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
        ->assertRedirect('/admin/login')
        ->assertSessionHasErrors([
            'email' => 'Too many login attempts. Please try again in a minute.',
        ]);
});

it('logs out admins through the admin logout endpoint', function () {
    $store = Store::factory()->create();
    $user = createStoreAdmin($store);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->post('/admin/logout')
        ->assertRedirect('/admin/login');

    $this->assertGuest();
});

