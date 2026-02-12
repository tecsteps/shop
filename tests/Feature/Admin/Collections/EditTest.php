<?php

use App\Livewire\Admin\Collections\Edit;
use App\Models\Collection;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function collectionEditAdmin(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

test('it renders the edit collection page with pre-populated data', function () {
    [$user, $store] = collectionEditAdmin();

    $collection = Collection::factory()->create([
        'store_id' => $store->id,
        'title' => 'Edit Me',
        'handle' => 'edit-me',
        'type' => 'manual',
        'status' => 'active',
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['collection' => $collection])
        ->assertSet('title', 'Edit Me')
        ->assertSet('handle', 'edit-me')
        ->assertSet('type', 'manual')
        ->assertSet('status', 'active')
        ->assertSuccessful();
});

test('it updates collection fields', function () {
    [$user, $store] = collectionEditAdmin();

    $collection = Collection::factory()->create([
        'store_id' => $store->id,
        'title' => 'Original',
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['collection' => $collection])
        ->set('title', 'Updated Title')
        ->set('status', 'active')
        ->call('save')
        ->assertDispatched('toast');

    $collection->refresh();

    expect($collection->title)->toBe('Updated Title')
        ->and($collection->status->value)->toBe('active');
});

test('it validates required fields on update', function () {
    [$user, $store] = collectionEditAdmin();

    $collection = Collection::factory()->create([
        'store_id' => $store->id,
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['collection' => $collection])
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title']);
});

test('it adds a product to a manual collection', function () {
    [$user, $store] = collectionEditAdmin();

    $collection = Collection::factory()->create([
        'store_id' => $store->id,
        'type' => 'manual',
    ]);

    $product = Product::factory()->create([
        'store_id' => $store->id,
        'title' => 'Linkable Product',
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['collection' => $collection])
        ->call('addProduct', $product->id);

    expect($collection->products()->pluck('products.id')->toArray())->toContain($product->id);
});

test('it removes a product from a collection', function () {
    [$user, $store] = collectionEditAdmin();

    $collection = Collection::factory()->create([
        'store_id' => $store->id,
        'type' => 'manual',
    ]);

    $product = Product::factory()->create(['store_id' => $store->id]);
    $collection->products()->attach($product->id, ['position' => 1]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['collection' => $collection])
        ->call('removeProduct', $product->id);

    expect($collection->products()->count())->toBe(0);
});

test('it aborts if collection belongs to a different store', function () {
    [$user, $store] = collectionEditAdmin();

    $otherStore = Store::factory()->create();
    $collection = Collection::factory()->create([
        'store_id' => $otherStore->id,
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['collection' => $collection])
        ->assertNotFound();
});

test('it searches products when product search is set', function () {
    [$user, $store] = collectionEditAdmin();

    $collection = Collection::factory()->create([
        'store_id' => $store->id,
        'type' => 'manual',
    ]);

    $product = Product::factory()->create([
        'store_id' => $store->id,
        'title' => 'Searchable Product',
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['collection' => $collection])
        ->set('productSearch', 'Searchable')
        ->assertViewHas('availableProducts', function ($products) use ($product) {
            return $products->contains('id', $product->id);
        });
});
