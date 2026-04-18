<?php

use App\Enums\ProductStatus;
use App\Livewire\Admin\Products\Form;
use App\Livewire\Admin\Products\Index;
use App\Models\Product;
use Livewire\Livewire;

it('lists products filtered by status and search', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'pm-list.test']);

    Product::factory()->create([
        'store_id' => $ctx['store']->id,
        'title' => 'Glacier Hoodie',
        'status' => ProductStatus::Active,
    ]);
    Product::factory()->create([
        'store_id' => $ctx['store']->id,
        'title' => 'Ember Socks',
        'status' => ProductStatus::Draft,
    ]);

    $this->actingAsAdmin($ctx['owner'], $ctx['store']);

    Livewire::test(Index::class)
        ->assertSee('Glacier Hoodie')
        ->assertSee('Ember Socks')
        ->set('search', 'Hoodie')
        ->assertSee('Glacier Hoodie')
        ->assertDontSee('Ember Socks')
        ->set('search', '')
        ->set('status', 'draft')
        ->assertSee('Ember Socks')
        ->assertDontSee('Glacier Hoodie');
});

it('creates a product via the form', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'pm-create.test']);
    $this->actingAsAdmin($ctx['owner'], $ctx['store']);

    Livewire::test(Form::class)
        ->set('title', 'Arctic Parka')
        ->set('status', 'draft')
        ->set('price_amount', 19900)
        ->call('save');

    $product = Product::query()->where('title', 'Arctic Parka')->first();
    expect($product)->not->toBeNull();
    expect($product->handle)->not->toBeEmpty();
    expect($product->variants()->count())->toBe(1);
});

it('edits an existing product', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'pm-edit.test']);
    $product = Product::factory()->create([
        'store_id' => $ctx['store']->id,
        'title' => 'Old title',
    ]);

    $this->actingAsAdmin($ctx['owner'], $ctx['store']);

    Livewire::test(Form::class, ['product' => $product])
        ->set('title', 'New title')
        ->call('save');

    expect($product->fresh()->title)->toBe('New title');
});

it('transitions status from draft to active when priced variants exist', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'pm-status.test']);
    $product = Product::factory()->create([
        'store_id' => $ctx['store']->id,
        'title' => 'Priced item',
        'status' => ProductStatus::Draft,
    ]);
    $product->variants()->create([
        'price_amount' => 5000,
        'currency' => 'USD',
        'is_default' => true,
        'position' => 0,
        'status' => 'active',
    ]);

    $this->actingAsAdmin($ctx['owner'], $ctx['store']);

    Livewire::test(Form::class, ['product' => $product])
        ->set('status', 'active')
        ->call('save');

    expect($product->fresh()->status)->toBe(ProductStatus::Active);
});

it('bulk archives products', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'pm-archive.test']);
    $products = Product::factory()->count(2)->create([
        'store_id' => $ctx['store']->id,
        'status' => ProductStatus::Active,
    ]);

    $this->actingAsAdmin($ctx['owner'], $ctx['store']);

    Livewire::test(Index::class)
        ->set('selected', $products->pluck('id')->toArray())
        ->call('bulkArchive');

    foreach ($products as $p) {
        expect($p->fresh()->status)->toBe(ProductStatus::Archived);
    }
});
