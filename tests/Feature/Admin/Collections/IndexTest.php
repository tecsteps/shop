<?php

use App\Livewire\Admin\Collections\Index;
use App\Models\Collection;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function collectionAdmin(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

test('it renders the collections index page', function () {
    [$user, $store] = collectionAdmin();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Collections')
        ->assertSuccessful();
});

test('it lists collections for the current store', function () {
    [$user, $store] = collectionAdmin();

    Collection::factory()->create([
        'store_id' => $store->id,
        'title' => 'Summer Sale',
    ]);

    Collection::factory()->create([
        'store_id' => $store->id,
        'title' => 'Winter Deals',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Summer Sale')
        ->assertSee('Winter Deals');
});

test('it does not show collections from other stores', function () {
    [$user, $store] = collectionAdmin();

    $otherStore = Store::factory()->create();

    Collection::factory()->create([
        'store_id' => $store->id,
        'title' => 'My Collection',
    ]);

    Collection::factory()->create([
        'store_id' => $otherStore->id,
        'title' => 'Other Collection',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('My Collection')
        ->assertDontSee('Other Collection');
});

test('it filters collections by search term', function () {
    [$user, $store] = collectionAdmin();

    Collection::factory()->create([
        'store_id' => $store->id,
        'title' => 'Summer Shirts',
    ]);

    Collection::factory()->create([
        'store_id' => $store->id,
        'title' => 'Winter Jackets',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('search', 'Shirts')
        ->assertSee('Summer Shirts')
        ->assertDontSee('Winter Jackets');
});

test('it filters collections by status', function () {
    [$user, $store] = collectionAdmin();

    Collection::factory()->create([
        'store_id' => $store->id,
        'title' => 'Active Collection',
        'status' => 'active',
    ]);

    Collection::factory()->draft()->create([
        'store_id' => $store->id,
        'title' => 'Draft Collection',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('statusFilter', 'active')
        ->assertSee('Active Collection')
        ->assertDontSee('Draft Collection');
});

test('it filters collections by type', function () {
    [$user, $store] = collectionAdmin();

    Collection::factory()->create([
        'store_id' => $store->id,
        'title' => 'Manual Collection',
        'type' => 'manual',
    ]);

    Collection::factory()->automated()->create([
        'store_id' => $store->id,
        'title' => 'Auto Collection',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('typeFilter', 'manual')
        ->assertSee('Manual Collection')
        ->assertDontSee('Auto Collection');
});

test('it deletes a collection', function () {
    [$user, $store] = collectionAdmin();

    $collection = Collection::factory()->create([
        'store_id' => $store->id,
        'title' => 'To Delete',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('delete', $collection->id)
        ->assertDispatched('toast');

    expect(Collection::find($collection->id))->toBeNull();
});

test('it shows product count for collections', function () {
    [$user, $store] = collectionAdmin();

    $collection = Collection::factory()->create([
        'store_id' => $store->id,
        'title' => 'Products Collection',
    ]);

    $product = Product::factory()->create(['store_id' => $store->id]);
    $collection->products()->attach($product->id, ['position' => 1]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Products Collection');
});

test('it shows empty state when no collections exist', function () {
    [$user, $store] = collectionAdmin();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('No collections');
});
