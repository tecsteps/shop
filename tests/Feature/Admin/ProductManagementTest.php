<?php

use App\Enums\StoreUserRole;
use App\Livewire\Admin\Products\Form;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('creates and updates a product from the shared form', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();
    $user->stores()->attach($store, ['role' => StoreUserRole::Owner]);
    app()->instance('current_store', $store);

    Livewire::actingAs($user)->test(Form::class)
        ->set('title', 'Trail Shoes')
        ->set('handle', 'trail-shoes')
        ->set('status', 'draft')
        ->set('sku', 'SHOE-001')
        ->set('priceAmount', 12900)
        ->set('quantity', 12)
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::query()->where('handle', 'trail-shoes')->firstOrFail();
    expect($product->store_id)->toBe($store->id)
        ->and($product->variants()->first()->price_amount)->toBe(12900)
        ->and($product->variants()->first()->inventoryItem->quantity_on_hand)->toBe(12);

    Livewire::actingAs($user)->test(Form::class, ['product' => $product])
        ->set('title', 'Updated Trail Shoes')
        ->call('save')
        ->assertHasNoErrors();

    expect($product->fresh()->title)->toBe('Updated Trail Shoes');
});

it('validates required product fields', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();
    $user->stores()->attach($store, ['role' => StoreUserRole::Owner]);
    app()->instance('current_store', $store);

    Livewire::actingAs($user)->test(Form::class)->set('title', '')->call('save')->assertHasErrors(['title' => 'required']);
});
