<?php

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function adminSetup(StoreUserRole $role = StoreUserRole::Owner): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create(['email_verified_at' => now()]);
    DB::table('store_users')->insert([
        'store_id' => $store->getKey(),
        'user_id' => $user->getKey(),
        'role' => $role->value,
        'created_at' => now(),
    ]);
    session(['current_store_id' => $store->getKey()]);

    return [$store, $user];
}

it('redirects guests away from admin', function () {
    $this->get('/admin')->assertRedirect();
});

it('shows the admin dashboard for owner', function () {
    [, $user] = adminSetup();

    $this->actingAs($user)->get('/admin')
        ->assertOk()
        ->assertSee('Dashboard');
});
