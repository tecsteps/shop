<?php

use App\Enums\CollectionStatus;
use App\Enums\VariantStatus;
use App\Livewire\Storefront\Collections\Index as CollectionIndex;
use App\Livewire\Storefront\Collections\Show as CollectionShow;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('lists all active collections', function () {
    Collection::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Active Collection',
        'status' => CollectionStatus::Active,
        'published_at' => now(),
    ]);

    Collection::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Draft Collection',
        'status' => CollectionStatus::Draft,
    ]);

    Livewire::test(CollectionIndex::class)
        ->assertSee('Active Collection')
        ->assertDontSee('Draft Collection');
});

it('shows collection detail with products', function () {
    $collection = Collection::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Test Collection',
        'handle' => 'test-collection',
        'status' => CollectionStatus::Active,
        'published_at' => now(),
    ]);

    $product = Product::factory()->active()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Collection Product',
    ]);
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'status' => VariantStatus::Active,
        'price_amount' => 2000,
    ]);
    $collection->products()->attach($product->id, ['position' => 0]);

    Livewire::test(CollectionShow::class, ['handle' => 'test-collection'])
        ->assertSee('Test Collection')
        ->assertSee('Collection Product');
});

it('filters products by vendor', function () {
    $collection = Collection::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'handle' => 'vendor-filter',
        'status' => CollectionStatus::Active,
        'published_at' => now(),
    ]);

    $productA = Product::factory()->active()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Nike Shoe',
        'vendor' => 'Nike',
    ]);
    ProductVariant::factory()->create([
        'product_id' => $productA->id,
        'status' => VariantStatus::Active,
        'price_amount' => 5000,
    ]);

    $productB = Product::factory()->active()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Adidas Shoe',
        'vendor' => 'Adidas',
    ]);
    ProductVariant::factory()->create([
        'product_id' => $productB->id,
        'status' => VariantStatus::Active,
        'price_amount' => 4500,
    ]);

    $collection->products()->attach($productA->id, ['position' => 0]);
    $collection->products()->attach($productB->id, ['position' => 1]);

    Livewire::test(CollectionShow::class, ['handle' => 'vendor-filter'])
        ->set('vendor', 'Nike')
        ->assertSee('Nike Shoe')
        ->assertDontSee('Adidas Shoe');
});

it('sorts products by price', function () {
    $collection = Collection::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'handle' => 'price-sort',
        'status' => CollectionStatus::Active,
        'published_at' => now(),
    ]);

    $cheap = Product::factory()->active()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Cheap Item',
    ]);
    ProductVariant::factory()->create([
        'product_id' => $cheap->id,
        'status' => VariantStatus::Active,
        'price_amount' => 1000,
    ]);

    $expensive = Product::factory()->active()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Expensive Item',
    ]);
    ProductVariant::factory()->create([
        'product_id' => $expensive->id,
        'status' => VariantStatus::Active,
        'price_amount' => 9000,
    ]);

    $collection->products()->attach($cheap->id, ['position' => 0]);
    $collection->products()->attach($expensive->id, ['position' => 1]);

    $component = Livewire::test(CollectionShow::class, ['handle' => 'price-sort'])
        ->set('sort', 'price-asc');

    $component->assertSeeInOrder(['Cheap Item', 'Expensive Item']);
});

it('paginates products at 12 per page', function () {
    $collection = Collection::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'handle' => 'paginated',
        'status' => CollectionStatus::Active,
        'published_at' => now(),
    ]);

    $letters = range('A', 'N'); // 14 items
    foreach ($letters as $i => $letter) {
        $product = Product::factory()->active()->create([
            'store_id' => $this->ctx['store']->id,
            'title' => "Paginated Item {$letter}",
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'status' => VariantStatus::Active,
            'price_amount' => 1000 + (($i + 1) * 100),
        ]);
        $collection->products()->attach($product->id, ['position' => $i]);
    }

    // Sort by price-asc for predictable order (A=cheapest, N=most expensive)
    $component = Livewire::test(CollectionShow::class, ['handle' => 'paginated'])
        ->set('sort', 'price-asc');

    // Page 1 should have 12 products (A-L), page 2 should have 2 (M-N)
    $component->assertSee('Paginated Item A')
        ->assertSee('Paginated Item L')
        ->assertDontSee('Paginated Item M');

    $component->call('gotoPage', 2)
        ->assertSee('Paginated Item M')
        ->assertSee('Paginated Item N')
        ->assertDontSee('Paginated Item A');
});
