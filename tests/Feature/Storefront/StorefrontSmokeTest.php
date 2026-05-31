<?php

use App\Models\Collection as ProductCollection;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\DemoStoreSeeder;
use Database\Seeders\StorefrontSeeder;

beforeEach(function () {
    // Seed the demo store + storefront presentation (theme, nav, pages) on the
    // canonical shop.test host so host-based store resolution works.
    $this->seed(StorefrontSeeder::class);
    $this->host = DemoStoreSeeder::STOREFRONT_HOST;
});

it('renders the home page without errors', function () {
    $this->get(storefrontUrl($this->host, '/'))
        ->assertOk()
        ->assertSee('Acme Fashion', escape: false);
});

it('renders the home page hero and announcement from theme settings', function () {
    $this->get(storefrontUrl($this->host, '/'))
        ->assertOk()
        ->assertSee('New season, new style')
        ->assertSee('Free shipping', escape: false);
});

it('renders the collections index page', function () {
    $this->get(storefrontUrl($this->host, '/collections'))
        ->assertOk()
        ->assertSee('Collections');
});

it('renders a collection page with its products', function () {
    $store = (new DemoStoreSeeder)->store();
    app()->instance('current_store', $store);

    $collection = ProductCollection::factory()->create([
        'store_id' => $store->id,
        'title' => 'Summer Edit',
        'handle' => 'summer-edit',
        'status' => 'active',
    ]);

    $product = Product::factory()->active()->create([
        'store_id' => $store->id,
        'title' => 'Linen Shirt',
        'handle' => 'linen-shirt',
    ]);
    ProductVariant::factory()->default()->create([
        'product_id' => $product->id,
        'price_amount' => 4999,
    ]);
    $collection->products()->attach($product->id, ['position' => 0]);

    $this->get(storefrontUrl($this->host, '/collections/summer-edit'))
        ->assertOk()
        ->assertSee('Summer Edit')
        ->assertSee('Linen Shirt')
        ->assertSee('49.99 USD', escape: false);
});

it('sorts collection products by price descending', function () {
    $store = (new DemoStoreSeeder)->store();
    app()->instance('current_store', $store);

    $collection = ProductCollection::factory()->create([
        'store_id' => $store->id,
        'handle' => 'sorted',
        'status' => 'active',
    ]);

    foreach ([['Cheap', 1000], ['Pricey', 9000], ['Mid', 5000]] as $index => [$title, $price]) {
        $product = Product::factory()->active()->create([
            'store_id' => $store->id,
            'title' => $title,
            'handle' => \Illuminate\Support\Str::slug($title),
        ]);
        ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'price_amount' => $price,
        ]);
        $collection->products()->attach($product->id, ['position' => $index]);
    }

    $page = \Livewire\Livewire::test(\App\Livewire\Storefront\Collections\Show::class, ['handle' => 'sorted'])
        ->set('sort', 'price-desc');

    $titles = $page->viewData('cards')->pluck('title')->all();

    expect($titles)->toBe(['Pricey', 'Mid', 'Cheap']);
});

it('filters the collection grid to in-stock products only', function () {
    $store = (new DemoStoreSeeder)->store();
    app()->instance('current_store', $store);

    $collection = ProductCollection::factory()->create([
        'store_id' => $store->id,
        'handle' => 'stocked',
        'status' => 'active',
    ]);

    // Creating a variant auto-creates its inventory item (ProductVariant boot
    // hook); update that row rather than creating a second one.
    $inStock = Product::factory()->active()->create(['store_id' => $store->id, 'title' => 'Available Item', 'handle' => 'available-item']);
    $inStockVariant = ProductVariant::factory()->default()->create(['product_id' => $inStock->id, 'price_amount' => 2000]);
    $inStockVariant->inventoryItem->update(['quantity_on_hand' => 5, 'quantity_reserved' => 0]);

    $outOfStock = Product::factory()->active()->create(['store_id' => $store->id, 'title' => 'Gone Item', 'handle' => 'gone-item']);
    $outVariant = ProductVariant::factory()->default()->create(['product_id' => $outOfStock->id, 'price_amount' => 3000]);
    $outVariant->inventoryItem->update(['quantity_on_hand' => 0, 'quantity_reserved' => 0]);

    $collection->products()->attach([$inStock->id => ['position' => 0], $outOfStock->id => ['position' => 1]]);

    $page = \Livewire\Livewire::test(\App\Livewire\Storefront\Collections\Show::class, ['handle' => 'stocked'])
        ->set('inStockOnly', true);

    $titles = $page->viewData('cards')->pluck('title')->all();

    expect($titles)->toContain('Available Item');
    expect($titles)->not->toContain('Gone Item');
});

it('marks a product sold out when all variants are out of stock under a deny policy', function () {
    $store = (new DemoStoreSeeder)->store();
    app()->instance('current_store', $store);

    $collection = ProductCollection::factory()->create(['store_id' => $store->id, 'handle' => 'soldout', 'status' => 'active']);
    $product = Product::factory()->active()->create(['store_id' => $store->id, 'title' => 'No Stock', 'handle' => 'no-stock']);
    $variant = ProductVariant::factory()->default()->create(['product_id' => $product->id, 'price_amount' => 1500]);
    $variant->inventoryItem->update(['quantity_on_hand' => 0, 'quantity_reserved' => 0, 'policy' => 'deny']);
    $collection->products()->attach($product->id, ['position' => 0]);

    $page = \Livewire\Livewire::test(\App\Livewire\Storefront\Collections\Show::class, ['handle' => 'soldout']);

    expect($page->viewData('cards')->first()['sold_out'])->toBeTrue();
});

it('shows an empty state for a collection with no products', function () {
    $store = (new DemoStoreSeeder)->store();
    app()->instance('current_store', $store);

    ProductCollection::factory()->create([
        'store_id' => $store->id,
        'handle' => 'empty-collection',
        'status' => 'active',
    ]);

    $this->get(storefrontUrl($this->host, '/collections/empty-collection'))
        ->assertOk()
        ->assertSee('No products found');
});

it('returns 404 for an unknown collection handle', function () {
    $this->get(storefrontUrl($this->host, '/collections/nope'))
        ->assertNotFound();
});

it('renders a published CMS page', function () {
    $this->get(storefrontUrl($this->host, '/pages/about'))
        ->assertOk()
        ->assertSee('About us');
});

it('returns 404 for a draft CMS page', function () {
    $store = (new DemoStoreSeeder)->store();
    app()->instance('current_store', $store);

    Page::factory()->draft()->create([
        'store_id' => $store->id,
        'handle' => 'secret',
    ]);

    $this->get(storefrontUrl($this->host, '/pages/secret'))
        ->assertNotFound();
});

it('renders the main navigation in the layout', function () {
    $this->get(storefrontUrl($this->host, '/'))
        ->assertOk()
        ->assertSee('Shop')
        ->assertSee('About');
});
