<?php

use App\Enums\PageStatus;
use App\Enums\ProductStatus;
use App\Livewire\Storefront\Collections\Show as CollectionShow;
use App\Livewire\Storefront\Products\Show as ProductShow;
use App\Models\Collection;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create(['name' => 'Acme Fashion', 'default_currency' => 'EUR']);
    StoreDomain::factory()->for($this->store)->create(['hostname' => 'acme-fashion.test']);
    app()->instance('current_store', $this->store);
});

it('renders the storefront home collections search and content routes', function () {
    Collection::factory()->for($this->store)->create(['title' => 'New Arrivals', 'handle' => 'new-arrivals']);
    Page::factory()->for($this->store)->create(['title' => 'About Us', 'handle' => 'about', 'status' => PageStatus::Published, 'published_at' => now()]);

    $this->withHeader('Host', 'acme-fashion.test')->get('/')->assertSuccessful()->assertSee('Acme Fashion');
    $this->withHeader('Host', 'acme-fashion.test')->get('/collections')->assertSuccessful()->assertSee('New Arrivals');
    $this->withHeader('Host', 'acme-fashion.test')->get('/search')->assertSuccessful()->assertSee('Search products');
    $this->withHeader('Host', 'acme-fashion.test')->get('/pages/about')->assertSuccessful()->assertSee('About Us');
});

it('renders active products and hides draft products', function () {
    $collection = Collection::factory()->for($this->store)->create(['handle' => 'featured']);
    $active = Product::factory()->for($this->store)->create(['title' => 'Classic Tee', 'handle' => 'classic-tee', 'status' => ProductStatus::Active, 'published_at' => now()]);
    ProductVariant::factory()->for($active)->default()->create(['price_amount' => 2499]);
    $draft = Product::factory()->for($this->store)->draft()->create(['title' => 'Secret Jacket']);
    $collection->products()->attach([$active->id, $draft->id]);

    Livewire::test(CollectionShow::class, ['handle' => 'featured'])->assertSee('Classic Tee')->assertDontSee('Secret Jacket');
    Livewire::test(ProductShow::class, ['handle' => 'classic-tee'])->assertSee('Classic Tee')->assertSee('Add to cart');
});
