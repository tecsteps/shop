<?php

use App\Enums\InventoryPolicy;
use App\Enums\NavigationItemType;
use App\Livewire\Storefront\Products\Show as ProductPage;
use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\Theme;
use App\Services\NavigationService;
use Livewire\Livewire;

/**
 * Build the absolute storefront URL for the store's primary domain.
 */
function storefrontUrl(Store $store, string $path = '/'): string
{
    return 'http://'.$store->handle.'.test'.$path;
}

test('home page renders for a store with a published theme and visible products', function () {
    $store = $this->createStore();
    Theme::factory()->published()->create(['store_id' => $store->id]);
    Product::factory()->active()->withVariants(1, ['price_amount' => 2499])->create([
        'store_id' => $store->id,
        'title' => 'Everyday Sneaker',
    ]);

    $response = $this->get(storefrontUrl($store));

    $response->assertOk()
        ->assertSee('Everyday Sneaker')
        ->assertSee('24.99 USD');
});

test('unknown product handle returns 404', function () {
    $store = $this->createStore();

    $this->get(storefrontUrl($store, '/products/no-such-product'))->assertNotFound();
});

test('draft product returns 404 on the storefront', function () {
    $store = $this->createStore();
    $product = Product::factory()->create(['store_id' => $store->id]);

    $this->get(storefrontUrl($store, '/products/'.$product->handle))->assertNotFound();
});

test('collections index lists active collections only', function () {
    $store = $this->createStore();
    Collection::factory()->create(['store_id' => $store->id, 'title' => 'Summer Picks']);
    Collection::factory()->draft()->create(['store_id' => $store->id, 'title' => 'Hidden Vault']);

    $this->get(storefrontUrl($store, '/collections'))
        ->assertOk()
        ->assertSee('Summer Picks')
        ->assertDontSee('Hidden Vault');
});

test('collection show lists only the visible products of that collection', function () {
    $store = $this->createStore();
    $collection = Collection::factory()->create(['store_id' => $store->id, 'title' => 'Essentials']);

    $visible = Product::factory()->active()->withVariants(1)->create(['store_id' => $store->id, 'title' => 'Visible Tee']);
    $draft = Product::factory()->create(['store_id' => $store->id, 'title' => 'Draft Tee']);
    $outsider = Product::factory()->active()->create(['store_id' => $store->id, 'title' => 'Outside Tee']);

    $collection->products()->attach($visible->id, ['position' => 0]);
    $collection->products()->attach($draft->id, ['position' => 1]);

    $this->get(storefrontUrl($store, '/collections/'.$collection->handle))
        ->assertOk()
        ->assertSee('Essentials')
        ->assertSee('Visible Tee')
        ->assertDontSee('Draft Tee')
        ->assertDontSee('Outside Tee');
});

test('draft collection returns 404 on the storefront', function () {
    $store = $this->createStore();
    $collection = Collection::factory()->draft()->create(['store_id' => $store->id]);

    $this->get(storefrontUrl($store, '/collections/'.$collection->handle))->assertNotFound();
});

test('collection show paginates twelve products per page', function () {
    $store = $this->createStore();
    $collection = Collection::factory()->create(['store_id' => $store->id]);

    for ($i = 1; $i <= 15; $i++) {
        $product = Product::factory()->active()->withVariants(1)->create([
            'store_id' => $store->id,
            'title' => sprintf('Product %02d', $i),
        ]);
        $collection->products()->attach($product->id, ['position' => $i - 1]);
    }

    $pageOne = $this->get(storefrontUrl($store, '/collections/'.$collection->handle));
    $pageOne->assertOk()
        ->assertSee('Product 01')
        ->assertSee('Product 12')
        ->assertDontSee('Product 13')
        ->assertSee('page=2');

    $pageTwo = $this->get(storefrontUrl($store, '/collections/'.$collection->handle.'?page=2'));
    $pageTwo->assertOk()
        ->assertSee('Product 13')
        ->assertSee('Product 15')
        ->assertDontSee('Product 01');
});

test('product page shows title, formatted price, and variant options', function () {
    $store = $this->createStore();
    $product = Product::factory()->active()->create([
        'store_id' => $store->id,
        'title' => 'Cotton Shirt',
    ]);

    $option = ProductOption::factory()->create(['product_id' => $product->id, 'name' => 'Size', 'position' => 0]);
    $small = ProductOptionValue::factory()->create(['product_option_id' => $option->id, 'value' => 'Small', 'position' => 0]);
    $medium = ProductOptionValue::factory()->create(['product_option_id' => $option->id, 'value' => 'Medium', 'position' => 1]);

    $variant = ProductVariant::factory()->default()->withInventory(25)->create([
        'product_id' => $product->id,
        'price_amount' => 2499,
    ]);
    $variant->optionValues()->attach($small->id);

    $second = ProductVariant::factory()->withInventory(25)->create([
        'product_id' => $product->id,
        'price_amount' => 2499,
        'position' => 1,
    ]);
    $second->optionValues()->attach($medium->id);

    $this->get(storefrontUrl($store, '/products/'.$product->handle))
        ->assertOk()
        ->assertSee('Cotton Shirt')
        ->assertSee('24.99 USD')
        ->assertSee('Size')
        ->assertSee('Small')
        ->assertSee('Medium')
        ->assertSee('In stock');
});

test('out-of-stock variant with deny policy shows sold out and a disabled button', function () {
    $store = $this->createStore();
    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    ProductVariant::factory()->default()->withInventory(0)->create([
        'product_id' => $product->id,
        'price_amount' => 1000,
    ]);

    $response = $this->get(storefrontUrl($store, '/products/'.$product->handle));

    $response->assertOk()
        ->assertSee('Out of stock')
        ->assertSee('Sold out');

    expect($response->getContent())->toContain('disabled');
});

test('zero-stock variant with continue policy shows backorder and an enabled button', function () {
    $store = $this->createStore();
    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->default()->create([
        'product_id' => $product->id,
        'price_amount' => 1000,
    ]);
    $variant->inventoryItem()->create([
        'store_id' => $store->id,
        'quantity_on_hand' => 0,
        'policy' => InventoryPolicy::Continue,
    ]);

    $this->get(storefrontUrl($store, '/products/'.$product->handle))
        ->assertOk()
        ->assertSee('Available on backorder')
        ->assertSee('Add to cart')
        ->assertDontSee('Sold out');
});

test('add to cart dispatches the event with variant id and quantity', function () {
    $store = $this->createStore();
    $this->bindStore($store);

    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->default()->withInventory(5)->create(['product_id' => $product->id]);

    Livewire::test(ProductPage::class, ['handle' => $product->handle])
        ->call('addToCart')
        ->assertDispatched('add-to-cart', variantId: $variant->id, quantity: 1);
});

test('published page renders its title and sanitized body', function () {
    $store = $this->createStore();
    Page::factory()->published()->create([
        'store_id' => $store->id,
        'title' => 'About Us',
        'handle' => 'about-us',
        'body_html' => '<p>We make things.</p><script>alert(1)</script>',
    ]);

    $this->get(storefrontUrl($store, '/pages/about-us'))
        ->assertOk()
        ->assertSee('About Us')
        ->assertSee('We make things.')
        ->assertDontSee('alert(1)');
});

test('draft page returns 404 on the storefront', function () {
    $store = $this->createStore();
    $page = Page::factory()->create(['store_id' => $store->id]);

    $this->get(storefrontUrl($store, '/pages/'.$page->handle))->assertNotFound();
});

test('page handle is generated from the title when empty', function () {
    $store = $this->createStore();

    $page = Page::factory()->create([
        'store_id' => $store->id,
        'title' => 'Terms of Service',
        'handle' => null,
    ]);

    expect($page->handle)->toBe('terms-of-service');
});

test('navigation tree resolves item urls and the header renders the labels', function () {
    $store = $this->createStore();
    $this->bindStore($store);

    $menu = NavigationMenu::factory()->mainMenu()->create(['store_id' => $store->id]);
    $page = Page::factory()->published()->create(['store_id' => $store->id, 'handle' => 'about-us']);
    $collection = Collection::factory()->create(['store_id' => $store->id, 'handle' => 'summer']);
    $product = Product::factory()->active()->create(['store_id' => $store->id, 'handle' => 'sneaker']);

    NavigationItem::factory()->create(['menu_id' => $menu->id, 'type' => NavigationItemType::Page, 'label' => 'Our Story', 'url' => null, 'resource_id' => $page->id, 'position' => 0]);
    NavigationItem::factory()->create(['menu_id' => $menu->id, 'type' => NavigationItemType::Collection, 'label' => 'Summer Drop', 'url' => null, 'resource_id' => $collection->id, 'position' => 1]);
    NavigationItem::factory()->create(['menu_id' => $menu->id, 'type' => NavigationItemType::Product, 'label' => 'Hero Sneaker', 'url' => null, 'resource_id' => $product->id, 'position' => 2]);
    NavigationItem::factory()->create(['menu_id' => $menu->id, 'type' => NavigationItemType::Link, 'label' => 'Gift Cards', 'url' => '/gift-cards', 'resource_id' => null, 'position' => 3]);

    $service = app(NavigationService::class);
    $tree = $service->buildTree($menu->refresh());

    expect($tree)->toHaveCount(4)
        ->and($tree[0]['url'])->toBe('/pages/about-us')
        ->and($tree[1]['url'])->toBe('/collections/summer')
        ->and($tree[2]['url'])->toBe('/products/sneaker')
        ->and($tree[3]['url'])->toBe('/gift-cards');

    expect($service->resolveUrl($menu->items->first()))->toBe('/pages/about-us');

    $this->get(storefrontUrl($store))
        ->assertOk()
        ->assertSee('Our Story')
        ->assertSee('Summer Drop')
        ->assertSee('Hero Sneaker')
        ->assertSee('Gift Cards');
});

test('navigation cache is invalidated when menu items change', function () {
    $store = $this->createStore();
    $this->bindStore($store);

    $menu = NavigationMenu::factory()->mainMenu()->create(['store_id' => $store->id]);
    NavigationItem::factory()->create(['menu_id' => $menu->id, 'label' => 'First Link', 'url' => '/first', 'position' => 0]);

    $service = app(NavigationService::class);

    expect(collect($service->forHandle('main-menu'))->pluck('label')->all())->toBe(['First Link']);

    NavigationItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Second Link', 'url' => '/second', 'position' => 1]);

    expect(collect($service->forHandle('main-menu'))->pluck('label')->all())->toBe(['First Link', 'Second Link']);
});
