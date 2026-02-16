<?php

use App\Livewire\Admin\Auth\Login;
use Livewire\Livewire;

it('renders the admin login page', function () {
    $response = $this->get('/admin/login');
    $response->assertStatus(200);
});

it('authenticates an admin user with valid credentials', function () {
    $ctx = createStoreContext();
    $ctx['user']->update(['password' => bcrypt('secret123')]);

    Livewire::test(Login::class)
        ->set('email', $ctx['user']->email)
        ->set('password', 'secret123')
        ->call('login')
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($ctx['user']);
});

it('rejects invalid credentials', function () {
    $ctx = createStoreContext();

    Livewire::test(Login::class)
        ->set('email', $ctx['user']->email)
        ->set('password', 'wrongpassword')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/admin');
    $response->assertRedirect();
});
