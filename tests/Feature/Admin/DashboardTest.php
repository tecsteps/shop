<?php

use App\Models\Store;
use App\Models\User;

it('renders the admin dashboard', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    $this->actingAs($user)->withSession(['current_store_id' => $store->id])->get('/admin')
        ->assertStatus(200)
        ->assertSee('Dashboard');
});

it('restricts dashboard to authenticated admins', function () {
    $this->get('/admin')->assertRedirect(route('admin.login'));
});

it('denies admin without store membership', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->withSession(['current_store_id' => $store->id])->get('/admin')
        ->assertStatus(403);
});
