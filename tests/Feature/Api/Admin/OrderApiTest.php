<?php

use App\Enums\StoreUserRole;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('lists orders for the authenticated user store', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    \DB::table('store_users')->insert([
        'store_id' => $store->getKey(),
        'user_id' => $user->getKey(),
        'role' => StoreUserRole::Owner->value,
        'created_at' => now(),
    ]);
    Order::factory()->count(2)->create(['store_id' => $store->getKey()]);

    Sanctum::actingAs($user, ['*']);

    $this->getJson("/api/admin/v1/stores/{$store->getKey()}/orders")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});
