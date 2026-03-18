<?php

use App\Enums\CollectionStatus;
use App\Enums\PageStatus;
use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;

beforeEach(function () {
    $this->context = createStoreContext('test-store.test');
});

it('returns 200 for homepage', function () {
    $this->get('http://test-store.test/')
        ->assertStatus(200)
        ->assertSee('Welcome to our store');
});

it('returns 200 for collections index', function () {
    $this->get('http://test-store.test/collections')
        ->assertStatus(200)
        ->assertSee('Collections');
});

it('returns 200 for collection show page', function () {
    $collection = Collection::factory()->create([
        'store_id' => $this->context['store']->id,
        'title' => 'Summer Collection',
        'handle' => 'summer-collection',
        'status' => CollectionStatus::Active,
    ]);

    $this->get('http://test-store.test/collections/summer-collection')
        ->assertStatus(200)
        ->assertSee('Summer Collection');
});

it('returns 404 for non-existent collection', function () {
    $this->get('http://test-store.test/collections/non-existent')
        ->assertStatus(404);
});

it('returns 200 for product show page', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->context['store']->id,
        'title' => 'Cool T-Shirt',
        'handle' => 'cool-t-shirt',
    ]);

    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_default' => true,
        'price_amount' => 2999,
        'currency' => 'EUR',
    ]);

    $this->get('http://test-store.test/products/cool-t-shirt')
        ->assertStatus(200)
        ->assertSee('Cool T-Shirt');
});

it('returns 404 for draft product', function () {
    Product::factory()->create([
        'store_id' => $this->context['store']->id,
        'handle' => 'draft-product',
        'status' => ProductStatus::Draft,
    ]);

    $this->get('http://test-store.test/products/draft-product')
        ->assertStatus(404);
});

it('returns 200 for cart page', function () {
    $this->get('http://test-store.test/cart')
        ->assertStatus(200)
        ->assertSee('Your Cart');
});

it('returns 200 for search page', function () {
    $this->get('http://test-store.test/search')
        ->assertStatus(200)
        ->assertSee('Search');
});

it('returns 200 for published CMS page', function () {
    Page::factory()->published()->create([
        'store_id' => $this->context['store']->id,
        'title' => 'About Us',
        'handle' => 'about-us',
        'body_html' => '<p>We are a great company.</p>',
    ]);

    $this->get('http://test-store.test/pages/about-us')
        ->assertStatus(200)
        ->assertSee('About Us')
        ->assertSee('We are a great company.');
});

it('returns 404 for draft CMS page', function () {
    Page::factory()->create([
        'store_id' => $this->context['store']->id,
        'handle' => 'draft-page',
        'status' => PageStatus::Draft,
    ]);

    $this->get('http://test-store.test/pages/draft-page')
        ->assertStatus(404);
});

it('shows products in collection', function () {
    $collection = Collection::factory()->create([
        'store_id' => $this->context['store']->id,
        'title' => 'Test Collection',
        'handle' => 'test-collection',
        'status' => CollectionStatus::Active,
    ]);

    $product = Product::factory()->active()->create([
        'store_id' => $this->context['store']->id,
        'title' => 'Collection Product',
    ]);

    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_default' => true,
        'price_amount' => 1999,
        'currency' => 'EUR',
    ]);

    $collection->products()->attach($product->id, ['position' => 0]);

    $this->get('http://test-store.test/collections/test-collection')
        ->assertStatus(200)
        ->assertSee('Collection Product');
});

it('shows product price on product page', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->context['store']->id,
        'handle' => 'priced-product',
    ]);

    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_default' => true,
        'price_amount' => 4999,
        'currency' => 'EUR',
    ]);

    $this->get('http://test-store.test/products/priced-product')
        ->assertStatus(200)
        ->assertSee('49.99 EUR');
});

it('shows featured products on home page', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->context['store']->id,
        'title' => 'Featured Item',
    ]);

    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_default' => true,
        'price_amount' => 2999,
        'currency' => 'EUR',
    ]);

    $this->get('http://test-store.test/')
        ->assertStatus(200)
        ->assertSee('Featured Item');
});
