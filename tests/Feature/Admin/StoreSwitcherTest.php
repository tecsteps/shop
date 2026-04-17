<?php

use App\Enums\StoreUserRole;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('allows a user with two stores to switch and see the other store products', function () {
    $storeA = Store::factory()->create(['name' => 'Store A']);
    $storeB = Store::factory()->create(['name' => 'Store B']);

    $user = User::factory()->create(['email_verified_at' => now()]);

    foreach ([$storeA, $storeB] as $store) {
        DB::table('store_users')->insert([
            'store_id' => $store->getKey(),
            'user_id' => $user->getKey(),
            'role' => StoreUserRole::Owner->value,
            'created_at' => now(),
        ]);
    }

    Product::factory()->create(['store_id' => $storeA->getKey(), 'title' => 'Alpha Widget']);
    Product::factory()->create(['store_id' => $storeB->getKey(), 'title' => 'Beta Gadget']);

    session(['current_store_id' => $storeA->getKey()]);

    $this->actingAs($user)->get('/admin/products')
        ->assertOk()
        ->assertSee('Alpha Widget')
        ->assertDontSee('Beta Gadget');

    $this->actingAs($user)->get('/admin/switch-store/'.$storeB->getKey())
        ->assertRedirect('/admin');

    expect(session('current_store_id'))->toBe($storeB->getKey());

    $this->actingAs($user)->get('/admin/products')
        ->assertOk()
        ->assertSee('Beta Gadget')
        ->assertDontSee('Alpha Widget');
});
