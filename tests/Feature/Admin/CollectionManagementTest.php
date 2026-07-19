<?php

use App\Enums\CollectionStatus;
use App\Livewire\Admin\Collections\Form;
use App\Livewire\Admin\Collections\Index;
use App\Models\Collection;
use App\Models\Product;
use Livewire\Livewire;

beforeEach(function () {
    $this->store = $this->createStore();
    $this->user = $this->createUserWithRole($this->store, 'owner');
    $this->bindStore($this->store);
});

test('lists collections with product counts', function () {
    $collection = Collection::factory()->create(['store_id' => $this->store->id, 'title' => 'Summer Sale']);
    $collection->products()->attach(Product::factory()->create(['store_id' => $this->store->id]), ['position' => 0]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->assertSee('Summer Sale')
        ->assertSee('1');
});

test('searches and filters collections', function () {
    Collection::factory()->create(['store_id' => $this->store->id, 'title' => 'Summer Sale']);
    Collection::factory()->draft()->create(['store_id' => $this->store->id, 'title' => 'Winter Draft']);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->set('search', 'Summer')
        ->assertSee('Summer Sale')
        ->assertDontSee('Winter Draft')
        ->set('search', '')
        ->set('statusFilter', 'draft')
        ->assertSee('Winter Draft')
        ->assertDontSee('Summer Sale');
});

test('creates a collection via admin form', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Form::class)
        ->set('title', 'Summer Collection')
        ->set('descriptionHtml', '<p>Warm stuff</p><script>alert(1)</script>')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $collection = Collection::query()->where('title', 'Summer Collection')->sole();

    expect($collection->handle)->toBe('summer-collection')
        ->and($collection->status)->toBe(CollectionStatus::Active)
        ->and($collection->type->value)->toBe('manual')
        ->and($collection->description_html)->toBe('<p>Warm stuff</p>')
        ->and($collection->description_html)->not->toContain('<script>');
});

test('edits a collection via admin form', function () {
    $collection = Collection::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Old Title',
    ]);

    Livewire::actingAs($this->user);
    Livewire::test(Form::class, ['collection' => $collection])
        ->assertSet('title', 'Old Title')
        ->set('title', 'New Title')
        ->set('status', 'archived')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $collection->refresh();

    expect($collection->title)->toBe('New Title')
        ->and($collection->status)->toBe(CollectionStatus::Archived);
});

test('attaches and reorders products with persisted positions', function () {
    $first = Product::factory()->create(['store_id' => $this->store->id, 'title' => 'Alpha']);
    $second = Product::factory()->create(['store_id' => $this->store->id, 'title' => 'Beta']);
    $third = Product::factory()->create(['store_id' => $this->store->id, 'title' => 'Gamma']);

    Livewire::actingAs($this->user);
    Livewire::test(Form::class)
        ->set('title', 'Ordered Collection')
        ->call('addProduct', $first->id)
        ->call('addProduct', $second->id)
        ->call('addProduct', $third->id)
        ->call('moveProduct', 2, 'up')
        ->call('save')
        ->assertHasNoErrors();

    $collection = Collection::query()->where('title', 'Ordered Collection')->sole();

    // Gamma was moved above Beta.
    expect($collection->products->pluck('id')->all())->toBe([$first->id, $third->id, $second->id])
        ->and($collection->products->pluck('pivot.position')->all())->toBe([0, 1, 2]);
});

test('removes an assigned product', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $collection = Collection::factory()->create(['store_id' => $this->store->id]);
    $collection->products()->attach($product, ['position' => 0]);

    Livewire::actingAs($this->user);
    Livewire::test(Form::class, ['collection' => $collection])
        ->call('removeProduct', $product->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($collection->products()->count())->toBe(0);
});

test('deletes a collection', function () {
    $collection = Collection::factory()->create(['store_id' => $this->store->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->call('confirmDelete', $collection->id)
        ->call('delete')
        ->assertDispatched('toast');

    $this->assertDatabaseMissing('collections', ['id' => $collection->id]);
});

test('support role cannot create collections', function () {
    $support = $this->createUserWithRole($this->store, 'support');

    $this->actingAs($support)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/collections/create')
        ->assertForbidden();
});

test('staff can create but not delete collections', function () {
    $staff = $this->createUserWithRole($this->store, 'staff');

    Livewire::actingAs($staff);
    Livewire::test(Form::class)
        ->set('title', 'Staff Collection')
        ->call('save')
        ->assertHasNoErrors();

    $collection = Collection::query()->where('title', 'Staff Collection')->sole();

    Livewire::actingAs($staff);
    Livewire::test(Index::class)
        ->call('confirmDelete', $collection->id)
        ->call('delete')
        ->assertForbidden();

    $this->assertDatabaseHas('collections', ['id' => $collection->id]);
});
