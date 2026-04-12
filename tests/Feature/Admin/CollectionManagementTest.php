<?php

use App\Livewire\Admin\Collections\Form as CollectionForm;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('creates a collection', function (): void {
    [$user, $store] = loginAsAdmin();

    Livewire::test(CollectionForm::class)
        ->set('title', 'Summer 2026')
        ->set('status', 'active')
        ->call('save')
        ->assertRedirect(route('admin.collections.index'));

    $collection = Collection::where('title', 'Summer 2026')->first();
    expect($collection)->not->toBeNull()
        ->and($collection->store_id)->toBe($store->id);
});

it('adds products to a collection', function (): void {
    [$user, $store] = loginAsAdmin();

    $product = Product::factory()->create(['store_id' => $store->id]);

    Livewire::test(CollectionForm::class)
        ->set('title', 'Featured')
        ->set('productIds', [$product->id])
        ->call('save')
        ->assertRedirect(route('admin.collections.index'));

    $collection = Collection::where('title', 'Featured')->first();
    expect($collection->products()->count())->toBe(1);
});
