<?php

use App\Livewire\Admin\Collections\Form;
use App\Livewire\Admin\Collections\Index;
use App\Models\Collection;
use App\Models\Product;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('requires authentication for collections index', function () {
    $this->get(route('admin.collections.index'))
        ->assertRedirect(route('admin.login'));
});

it('renders the collections index page', function () {
    $this->actingAs($this->ctx['user']);

    $this->get(route('admin.collections.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('lists collections for the current store', function () {
    $collections = Collection::factory()->count(3)->create(['store_id' => $this->ctx['store']->id]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->assertSee($collections[0]->title)
        ->assertSee($collections[1]->title)
        ->assertSee($collections[2]->title);
});

it('searches collections by title', function () {
    Collection::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Summer Sale',
    ]);
    Collection::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Winter Clearance',
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->set('search', 'Summer')
        ->assertSee('Summer Sale')
        ->assertDontSee('Winter Clearance');
});

it('can delete a collection', function () {
    $collection = Collection::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->call('confirmDelete', $collection->id)
        ->call('deleteCollection');

    expect(Collection::find($collection->id))->toBeNull();
});

it('renders the collection create page', function () {
    $this->actingAs($this->ctx['user']);

    $this->get(route('admin.collections.create'))
        ->assertOk()
        ->assertSeeLivewire(Form::class);
});

it('creates a new collection', function () {
    Livewire::actingAs($this->ctx['user'])
        ->test(Form::class)
        ->set('title', 'New Collection')
        ->set('status', 'active')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(Collection::where('title', 'New Collection')->exists())->toBeTrue();
});

it('validates required title on save', function () {
    Livewire::actingAs($this->ctx['user'])
        ->test(Form::class)
        ->set('title', '')
        ->call('save')
        ->assertHasErrors('title');
});

it('renders the collection edit page', function () {
    $collection = Collection::factory()->create(['store_id' => $this->ctx['store']->id]);

    $this->actingAs($this->ctx['user']);

    $this->get(route('admin.collections.edit', $collection))
        ->assertOk()
        ->assertSeeLivewire(Form::class);
});

it('adds and removes products in collection form', function () {
    $product = Product::factory()->create(['store_id' => $this->ctx['store']->id]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Form::class)
        ->call('addProduct', $product->id)
        ->assertSet('assignedProductIds', [$product->id])
        ->call('removeProduct', $product->id)
        ->assertSet('assignedProductIds', []);
});
