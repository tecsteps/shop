<?php

use App\Models\Store;
use App\Models\User;

it('renders the settings page', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    $this->actingAs($user)->withSession(['current_store_id' => $store->id])->get('/admin/settings')
        ->assertStatus(200);
});

it('restricts settings to owner and admin roles', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'staff']);

    $this->actingAs($user)->withSession(['current_store_id' => $store->id])->get('/admin/settings')
        ->assertStatus(403);
});
