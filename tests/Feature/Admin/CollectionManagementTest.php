<?php

use App\Enums\StoreUserRole;
use App\Livewire\Admin\Collections\Form as CollectionForm;
use App\Livewire\Admin\Collections\Index as CollectionsIndex;
use App\Models\Collection;
use App\Models\Product;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
});

it('lists collections with product counts', function () {
    $collection = Collection::factory()->for($this->store)->create(['title' => 'Summer Sale']);
    $products = Product::factory()->count(2)->for($this->store)->create();
    $collection->products()->sync([
        $products[0]->getKey() => ['position' => 0],
        $products[1]->getKey() => ['position' => 1],
    ]);

    actingAsAdmin($this->user)
        ->get('/admin/collections')
        ->assertOk()
        ->assertSee('Summer Sale');

    $component = Livewire::test(CollectionsIndex::class);

    expect($component->instance()->collections()->first()->products_count)->toBe(2);
});

it('creates a collection with assigned products', function () {
    $products = Product::factory()->count(2)->for($this->store)->create();

    actingAsAdmin($this->user);

    Livewire::test(CollectionForm::class)
        ->set('title', 'New Arrivals')
        ->call('addProduct', $products[0]->getKey())
        ->call('addProduct', $products[1]->getKey())
        ->call('save')
        ->assertHasNoErrors();

    $collection = Collection::query()->where('title', 'New Arrivals')->firstOrFail();

    expect($collection->handle)->toBe('new-arrivals');
    expect($collection->products()->pluck('products.id')->all())
        ->toBe([$products[0]->getKey(), $products[1]->getKey()]);
});

it('edits a collection', function () {
    $collection = Collection::factory()->for($this->store)->create(['title' => 'Old Title']);

    actingAsAdmin($this->user);

    Livewire::test(CollectionForm::class, ['collectionId' => $collection->getKey()])
        ->set('title', 'Updated Title')
        ->set('status', 'archived')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('collections', [
        'id' => $collection->getKey(),
        'title' => 'Updated Title',
        'status' => 'archived',
    ]);
});

it('reorders products within a collection', function () {
    $collection = Collection::factory()->for($this->store)->create();
    $products = Product::factory()->count(3)->for($this->store)->create();
    $collection->products()->sync([
        $products[0]->getKey() => ['position' => 0],
        $products[1]->getKey() => ['position' => 1],
        $products[2]->getKey() => ['position' => 2],
    ]);

    actingAsAdmin($this->user);

    Livewire::test(CollectionForm::class, ['collectionId' => $collection->getKey()])
        ->call('reorderProducts', $products[2]->getKey(), 0)
        ->call('save')
        ->assertHasNoErrors();

    expect($collection->refresh()->products()->pluck('products.id')->all())
        ->toBe([$products[2]->getKey(), $products[0]->getKey(), $products[1]->getKey()]);
});

it('removes a product from a collection', function () {
    $collection = Collection::factory()->for($this->store)->create();
    $products = Product::factory()->count(2)->for($this->store)->create();
    $collection->products()->sync([
        $products[0]->getKey() => ['position' => 0],
        $products[1]->getKey() => ['position' => 1],
    ]);

    actingAsAdmin($this->user);

    Livewire::test(CollectionForm::class, ['collectionId' => $collection->getKey()])
        ->call('removeProduct', $products[0]->getKey())
        ->call('save')
        ->assertHasNoErrors();

    expect($collection->refresh()->products()->pluck('products.id')->all())
        ->toBe([$products[1]->getKey()]);
});

it('validates handle uniqueness within store', function () {
    Collection::factory()->for($this->store)->create(['handle' => 'summer']);

    actingAsAdmin($this->user);

    Livewire::test(CollectionForm::class)
        ->set('title', 'Another Summer')
        ->set('handle', 'summer')
        ->call('save')
        ->assertHasErrors(['handle']);
});

it('restricts collection management by role', function () {
    $collection = Collection::factory()->for($this->store)->create();

    $support = createStoreMember($this->store, StoreUserRole::Support);

    actingAsAdmin($support, $this->store)
        ->get('/admin/collections')
        ->assertOk();

    actingAsAdmin($support, $this->store)
        ->get('/admin/collections/create')
        ->assertForbidden();

    $staff = createStoreMember($this->store, StoreUserRole::Staff);

    actingAsAdmin($staff, $this->store);

    Livewire::test(CollectionForm::class, ['collectionId' => $collection->getKey()])
        ->call('deleteCollection')
        ->assertForbidden();

    $this->assertDatabaseHas('collections', ['id' => $collection->getKey()]);
});
