<?php

use App\Enums\StoreUserRole;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()->forgetInstance('current_store');
    $this->store = Store::factory()->create();
    $this->user = User::factory()->create();
    StoreUser::query()->create(['store_id' => $this->store->id, 'user_id' => $this->user->id, 'role' => StoreUserRole::Owner, 'created_at' => now()]);
});

it('lists and creates products with the correct abilities', function () {
    Sanctum::actingAs($this->user, ['read-products', 'write-products']);
    app()->instance('current_store', $this->store);
    Product::factory()->count(2)->for($this->store)->create();
    app()->forgetInstance('current_store');

    $this->getJson("/api/admin/v1/stores/{$this->store->id}/products")
        ->assertSuccessful()->assertJsonCount(2, 'data');

    $this->postJson("/api/admin/v1/stores/{$this->store->id}/products", ['title' => 'API Product'])
        ->assertCreated()->assertJsonPath('data.title', 'API Product');
});

it('enforces write product abilities and authentication', function () {
    Sanctum::actingAs($this->user, ['read-products']);
    $this->postJson("/api/admin/v1/stores/{$this->store->id}/products", ['title' => 'Forbidden'])->assertForbidden();

    auth()->forgetGuards();
    $this->getJson("/api/admin/v1/stores/{$this->store->id}/products")->assertUnauthorized();
});
