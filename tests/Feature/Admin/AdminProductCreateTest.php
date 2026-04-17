<?php

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('creates a product and shows it in the index', function (): void {
    $store = Store::factory()->create();
    $user = User::factory()->create(['email_verified_at' => now()]);
    DB::table('store_users')->insert([
        'store_id' => $store->getKey(),
        'user_id' => $user->getKey(),
        'role' => StoreUserRole::Owner->value,
        'created_at' => now(),
    ]);

    $this->actingAs($user)->withSession(['current_store_id' => $store->getKey()]);
    session(['current_store_id' => $store->getKey()]);
    app()->instance('current_store', $store);

    Livewire::test(\App\Livewire\Admin\Products\Create::class)
        ->set('title', 'Test Hoodie')
        ->set('status', 'active')
        ->set('priceAmount', 2999)
        ->set('quantityOnHand', 10)
        ->call('save')
        ->assertHasNoErrors();

    $this->get('/admin/products')
        ->assertOk()
        ->assertSee('Test Hoodie');
});
