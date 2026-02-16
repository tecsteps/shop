<?php

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\Collections\Index as CollectionsIndex;
use App\Livewire\Storefront\Collections\Show as CollectionShow;
use App\Livewire\Storefront\Home;
use App\Livewire\Storefront\Products\Show as ProductShow;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Livewire;

it('renders the home page with featured products', function () {
    $ctx = createStoreContext();
    $product = Product::factory()->for($ctx['store'])->create([
        'status' => ProductStatus::Active,
        'title' => 'Test Product',
    ]);
    ProductVariant::factory()->for($product)->create([
        'price_amount' => 1999,
        'is_default' => true,
    ]);

    Livewire::withHeaders(['Host' => 'shop.test'])
        ->test(Home::class)
        ->assertSee('Test Product')
        ->assertSee('19.99');
});

it('renders a product detail page', function () {
    $ctx = createStoreContext();
    $product = Product::factory()->for($ctx['store'])->create([
        'status' => ProductStatus::Active,
        'title' => 'Classic Cotton T-Shirt',
        'handle' => 'classic-cotton-t-shirt',
        'description_html' => '<p>A comfortable cotton t-shirt.</p>',
    ]);
    ProductVariant::factory()->for($product)->create([
        'price_amount' => 2499,
        'is_default' => true,
    ]);

    Livewire::withHeaders(['Host' => 'shop.test'])
        ->test(ProductShow::class, ['handle' => 'classic-cotton-t-shirt'])
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('24.99')
        ->assertSee('A comfortable cotton t-shirt.');
});

it('returns 404 for draft products', function () {
    $ctx = createStoreContext();
    Product::factory()->for($ctx['store'])->create([
        'status' => ProductStatus::Draft,
        'handle' => 'draft-product',
    ]);

    Livewire::withHeaders(['Host' => 'shop.test'])
        ->test(ProductShow::class, ['handle' => 'draft-product'])
        ->assertStatus(404);
});

it('renders the collections index with active collections', function () {
    $ctx = createStoreContext();
    Collection::factory()->for($ctx['store'])->create([
        'status' => CollectionStatus::Active,
        'title' => 'T-Shirts',
        'handle' => 't-shirts',
    ]);

    Livewire::withHeaders(['Host' => 'shop.test'])
        ->test(CollectionsIndex::class)
        ->assertSee('T-Shirts');
});

it('renders a collection page with products', function () {
    $ctx = createStoreContext();
    $collection = Collection::factory()->for($ctx['store'])->create([
        'status' => CollectionStatus::Active,
        'title' => 'T-Shirts',
        'handle' => 't-shirts',
    ]);
    $product = Product::factory()->for($ctx['store'])->create([
        'status' => ProductStatus::Active,
        'title' => 'Cool Tee',
    ]);
    ProductVariant::factory()->for($product)->create([
        'price_amount' => 1500,
        'is_default' => true,
    ]);
    $collection->products()->attach($product, ['position' => 1]);

    Livewire::withHeaders(['Host' => 'shop.test'])
        ->test(CollectionShow::class, ['handle' => 't-shirts'])
        ->assertSee('T-Shirts')
        ->assertSee('Cool Tee')
        ->assertSee('1 product');
});

it('adds a product to the cart', function () {
    $ctx = createStoreContext();
    $product = Product::factory()->for($ctx['store'])->create([
        'status' => ProductStatus::Active,
        'handle' => 'test-product',
    ]);
    $variant = ProductVariant::factory()->for($product)->create([
        'price_amount' => 2000,
        'is_default' => true,
    ]);

    Livewire::withHeaders(['Host' => 'shop.test'])
        ->test(ProductShow::class, ['handle' => 'test-product'])
        ->call('addToCart')
        ->assertDispatched('cart-updated');
});

it('renders the cart page', function () {
    $ctx = createStoreContext();

    Livewire::withHeaders(['Host' => 'shop.test'])
        ->test(CartShow::class)
        ->assertSee('Your Cart')
        ->assertSee('Your cart is empty');
});
