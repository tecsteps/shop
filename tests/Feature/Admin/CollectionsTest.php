<?php

use App\Enums\StoreUserRole;
use App\Models\Collection;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function collectionsSetup(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create(['email_verified_at' => now()]);
    DB::table('store_users')->insert([
        'store_id' => $store->getKey(),
        'user_id' => $user->getKey(),
        'role' => StoreUserRole::Owner->value,
        'created_at' => now(),
    ]);
    session(['current_store_id' => $store->getKey()]);

    return [$store, $user];
}

it('lists collections', function () {
    [$store, $user] = collectionsSetup();
    Collection::factory()->create(['store_id' => $store->getKey(), 'title' => 'Summer Collection']);

    $this->actingAs($user)->get('/admin/collections')
        ->assertOk()
        ->assertSee('Summer Collection');
});

it('edits an existing collection', function () {
    [$store, $user] = collectionsSetup();
    $collection = Collection::factory()->create(['store_id' => $store->getKey(), 'title' => 'Winter Picks']);

    $this->actingAs($user)->get('/admin/collections/'.$collection->getKey().'/edit')
        ->assertOk()
        ->assertSee('Winter Picks');
});
