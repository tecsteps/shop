<?php

use App\Livewire\Admin\Collections\Form;
use App\Livewire\Admin\Collections\Index;
use App\Models\Collection;
use Livewire\Livewire;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    ['store' => $this->store, 'user' => $this->user] = $this->createStoreContext();
    $this->actingAsAdmin($this->user, $this->store);
});

it('lists collections scoped to the current store', function () {
    Collection::factory()->create(['store_id' => $this->store->getKey(), 'title' => 'Summer Sale']);

    $this->get(route('admin.collections.index'))
        ->assertOk()
        ->assertSee('Summer Sale');
});

it('creates a collection with assigned products', function () {
    $variant = CommerceTestHelper::purchasableVariant($this->store);
    $productId = $variant->product->getKey();

    Livewire::test(Form::class)
        ->set('title', 'New Arrivals')
        ->set('status', 'active')
        ->call('addProduct', $productId)
        ->call('save')
        ->assertRedirect(route('admin.collections.index'));

    $collection = Collection::query()
        ->where('store_id', $this->store->getKey())
        ->where('handle', 'new-arrivals')
        ->firstOrFail();

    expect($collection->products()->pluck('products.id')->all())->toBe([$productId]);
});

it('edits a collection and reorders products', function () {
    $collection = Collection::factory()->create(['store_id' => $this->store->getKey()]);
    $first = CommerceTestHelper::purchasableVariant($this->store)->product;
    $second = CommerceTestHelper::purchasableVariant($this->store)->product;
    $collection->products()->sync([
        $first->getKey() => ['position' => 0],
        $second->getKey() => ['position' => 1],
    ]);

    Livewire::test(Form::class, ['collection' => $collection])
        ->assertSet('assignedProductIds', [$first->getKey(), $second->getKey()])
        ->call('moveProductUp', $second->getKey())
        ->call('save')
        ->assertDispatched('toast');

    expect($collection->products()->orderByPivot('position')->pluck('products.id')->all())
        ->toBe([$second->getKey(), $first->getKey()]);
});

it('deletes a collection with confirmation', function () {
    $collection = Collection::factory()->create(['store_id' => $this->store->getKey()]);

    Livewire::test(Index::class)
        ->call('confirmDelete', $collection->getKey())
        ->call('deleteCollection')
        ->assertDispatched('toast');

    expect(Collection::query()->whereKey($collection->getKey())->exists())->toBeFalse();
});

it('validates duplicate handles', function () {
    Collection::factory()->create(['store_id' => $this->store->getKey(), 'handle' => 'sale']);

    Livewire::test(Form::class)
        ->set('title', 'Sale')
        ->set('handle', 'sale')
        ->call('save')
        ->assertHasErrors('handle');
});

it('returns 404 when editing a collection from another store', function () {
    ['store' => $otherStore] = $this->createStoreContext();
    $collection = Collection::factory()->create(['store_id' => $otherStore->getKey()]);

    $this->get(route('admin.collections.edit', $collection))->assertNotFound();
});
