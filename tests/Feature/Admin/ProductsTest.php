<?php

use App\Enums\StoreUserRole;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function productsSetup(StoreUserRole $role = StoreUserRole::Owner): array
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

it('lists products for owner', function () {
    [$store, $user] = productsSetup();
    $product = Product::factory()->create(['store_id' => $store->getKey(), 'title' => 'Blue Shirt']);
    ProductVariant::factory()->create(['product_id' => $product->getKey()]);

    $this->actingAs($user)->get('/admin/products')
        ->assertOk()
        ->assertSee('Blue Shirt');
});

it('shows product edit for owner', function () {
    [$store, $user] = productsSetup();
    $product = Product::factory()->create(['store_id' => $store->getKey(), 'title' => 'Red Hat']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->getKey()]);
    InventoryItem::factory()->create(['store_id' => $store->getKey(), 'variant_id' => $variant->getKey()]);

    $this->actingAs($user)->get('/admin/products/'.$product->getKey().'/edit')
        ->assertOk()
        ->assertSee('Red Hat');
});

it('denies support role from updating product', function () {
    [$store, $user] = productsSetup(StoreUserRole::Support);
    $product = Product::factory()->create(['store_id' => $store->getKey()]);

    $response = $this->actingAs($user)->get('/admin/products/'.$product->getKey().'/edit')
        ->assertOk();

    Livewire\Livewire::test(\App\Livewire\Admin\Products\Edit::class, ['product' => $product->getKey()])
        ->set('title', 'Changed')
        ->call('save')
        ->assertForbidden();
});
