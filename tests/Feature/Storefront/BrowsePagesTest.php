<?php

use App\Enums\InventoryPolicy;
use App\Enums\VariantStatus;
use App\Models\Collection;
use App\Models\InventoryItem;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;

it('renders storefront browse pages for home, collections, product, search, and page content', function () {
    $store = Store::factory()->create();

    $collection = Collection::factory()
        ->for($store)
        ->create([
            'title' => 'Featured',
            'handle' => 'featured',
        ]);

    $product = Product::factory()
        ->for($store)
        ->create([
            'title' => 'Explorer Jacket',
            'handle' => 'explorer-jacket',
        ]);

    $variant = ProductVariant::factory()
        ->for($product)
        ->default()
        ->state([
            'status' => VariantStatus::Active,
            'price_amount' => 7900,
            'requires_shipping' => true,
        ])
        ->create();

    InventoryItem::factory()
        ->for($store)
        ->for($variant, 'variant')
        ->state([
            'quantity_on_hand' => 50,
            'quantity_reserved' => 0,
            'policy' => InventoryPolicy::Deny,
        ])
        ->create();

    $collection->products()->attach($product->id, ['position' => 0]);

    $page = Page::factory()
        ->for($store)
        ->create([
            'title' => 'About',
            'handle' => 'about',
        ]);

    $this->get('/')
        ->assertOk()
        ->assertViewHas('collections', fn ($collections): bool => $collections->contains('id', $collection->id))
        ->assertViewHas('products', fn ($products): bool => $products->contains('id', $product->id));

    $this->get('/collections')
        ->assertOk()
        ->assertViewHas('collections', fn ($collections): bool => $collections->getCollection()->contains('id', $collection->id));

    $this->get("/collections/{$collection->handle}")
        ->assertOk()
        ->assertViewHas('collection', fn (Collection $resolved): bool => $resolved->id === $collection->id)
        ->assertViewHas('products', fn ($products): bool => $products->getCollection()->contains('id', $product->id));

    $this->get("/products/{$product->handle}")
        ->assertOk()
        ->assertViewHas('product', fn (Product $resolved): bool => $resolved->id === $product->id)
        ->assertViewHas('selectedVariant', fn (ProductVariant $resolved): bool => $resolved->id === $variant->id);

    $this->get('/search?q=Explorer')
        ->assertOk()
        ->assertViewHas('products', fn ($products): bool => $products->getCollection()->contains('id', $product->id));

    $this->get("/pages/{$page->handle}")
        ->assertOk()
        ->assertViewHas('page', fn (Page $resolved): bool => $resolved->id === $page->id);
});

it('shows continue-selling variants as backorder-available on storefront product cards', function () {
    $store = Store::factory()->create();

    $product = Product::factory()
        ->for($store)
        ->create([
            'title' => 'Backorder Denim Jacket',
            'handle' => 'backorder-denim-jacket',
        ]);

    $variant = ProductVariant::factory()
        ->for($product)
        ->default()
        ->state([
            'status' => VariantStatus::Active,
            'price_amount' => 9999,
            'requires_shipping' => true,
        ])
        ->create();

    InventoryItem::factory()
        ->for($store)
        ->for($variant, 'variant')
        ->state([
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
            'policy' => InventoryPolicy::ContinueSelling,
        ])
        ->create();

    $this->get('/')
        ->assertOk()
        ->assertSee('Backorder Denim Jacket')
        ->assertSee('Available on backorder')
        ->assertSee('Add to cart')
        ->assertDontSee('Sold out');
});
