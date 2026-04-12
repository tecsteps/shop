<?php

use App\Livewire\Storefront\Collections\Index;
use App\Livewire\Storefront\Collections\Show;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('renders the collection index with active collections', function (): void {
    $collection = Collection::factory()->for($this->store)->create([
        'title' => 'Summer Picks',
        'handle' => 'summer-picks',
    ]);

    Livewire::test(Index::class)
        ->assertStatus(200)
        ->assertSee('All collections')
        ->assertSee('Summer Picks');

    expect(Collection::query()->count())->toBe(1);
});

it('renders a collection detail with its products', function (): void {
    $collection = Collection::factory()->for($this->store)->create([
        'title' => 'Essentials',
        'handle' => 'essentials',
    ]);

    $product = Product::factory()->for($this->store)->create(['title' => 'Linen Shirt']);
    ProductVariant::factory()->for($product)->create(['price_amount' => 4999]);
    $collection->products()->attach($product->id, ['position' => 0]);

    Livewire::test(Show::class, ['handle' => 'essentials'])
        ->assertStatus(200)
        ->assertSee('Essentials')
        ->assertSee('Linen Shirt');
});

it('aborts 404 for missing collection handle', function (): void {
    Livewire::test(Show::class, ['handle' => 'missing']);
})->throws(Illuminate\Database\Eloquent\ModelNotFoundException::class);
