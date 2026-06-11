<?php

use App\Models\Collection;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use App\Models\Theme;
use App\Models\ThemeSettings;

/**
 * Create a published product with a default variant for the given store.
 *
 * @param  array<string, mixed>  $productAttributes
 */
function createPublishedProduct($store, int $priceAmount = 2499, array $productAttributes = []): Product
{
    $product = Product::factory()->active()->for($store)->create($productAttributes);

    ProductVariant::factory()->asDefault()->priced($priceAmount)->for($product)->create();

    return $product;
}

it('renders the home page with the store name and products', function () {
    $context = createStoreContext();

    $product = createPublishedProduct($context['store'], 2499, ['title' => 'Organic Cotton Tee']);

    $response = $this->get('http://'.$context['domain']->hostname.'/');

    $response->assertOk();
    $response->assertSee($context['store']->name);
    $response->assertSee('Organic Cotton Tee');
    $response->assertSee('24.99 EUR');
});

it('renders home page sections from the active theme settings', function () {
    $context = createStoreContext();

    $theme = Theme::factory()->for($context['store'])->create();
    ThemeSettings::factory()->for($theme)->withSettings([
        'hero_heading' => 'Summer Essentials Are Here',
        'show_announcement_bar' => true,
        'announcement_text' => 'Free shipping over 50 EUR',
    ])->create();

    $response = $this->get('http://'.$context['domain']->hostname.'/');

    $response->assertOk();
    $response->assertSee('Summer Essentials Are Here');
    $response->assertSee('Free shipping over 50 EUR');
});

it('renders the collections index with published collections', function () {
    $context = createStoreContext();

    Collection::factory()->for($context['store'])->create(['title' => 'Summer Collection']);
    Collection::factory()->draft()->for($context['store'])->create(['title' => 'Hidden Drafts']);

    $response = $this->get('http://'.$context['domain']->hostname.'/collections');

    $response->assertOk();
    $response->assertSee('Summer Collection');
    $response->assertDontSee('Hidden Drafts');
});

it('renders a collection page with products and pagination', function () {
    $context = createStoreContext();

    $collection = Collection::factory()->for($context['store'])->create([
        'title' => 'New Arrivals',
        'handle' => 'new-arrivals',
    ]);

    $products = collect(range(1, 15))->map(
        fn (int $i): Product => createPublishedProduct($context['store'], 1000 + $i, ['title' => "Catalog Item {$i}"]),
    );

    $collection->products()->attach(
        $products->mapWithKeys(fn (Product $product, int $index): array => [$product->getKey() => ['position' => $index]])->all(),
    );

    $firstPage = $this->get('http://'.$context['domain']->hostname.'/collections/new-arrivals');

    $firstPage->assertOk();
    $firstPage->assertSee('New Arrivals');
    $firstPage->assertSee('15 products');
    $firstPage->assertSee('Catalog Item 1');
    $firstPage->assertDontSee('Catalog Item 13');

    $secondPage = $this->get('http://'.$context['domain']->hostname.'/collections/new-arrivals?page=2');

    $secondPage->assertOk();
    $secondPage->assertSee('Catalog Item 13');
});

it('renders a product page with title, price, and options', function () {
    $context = createStoreContext();

    $product = Product::factory()->active()->for($context['store'])->create([
        'title' => 'Classic Crewneck',
        'handle' => 'classic-crewneck',
    ]);

    $option = ProductOption::factory()->for($product)->create(['name' => 'Size']);
    $small = $option->values()->create(['value' => 'S', 'position' => 0]);
    $medium = $option->values()->create(['value' => 'M', 'position' => 1]);

    $smallVariant = ProductVariant::factory()->asDefault()->priced(2999)->for($product)->create();
    $mediumVariant = ProductVariant::factory()->priced(3499)->for($product)->create(['position' => 1]);

    $smallVariant->optionValues()->attach($small);
    $mediumVariant->optionValues()->attach($medium);

    $response = $this->get('http://'.$context['domain']->hostname.'/products/classic-crewneck');

    $response->assertOk();
    $response->assertSee('Classic Crewneck');
    $response->assertSee('29.99 EUR');
    $response->assertSee('Size');
    $response->assertSee('Add to cart');
});

it('renders a published CMS page', function () {
    $context = createStoreContext();

    Page::factory()->for($context['store'])->create([
        'title' => 'About Us',
        'handle' => 'about',
        'body_html' => '<h2>Our Story</h2><p>We make great things.</p>',
    ]);

    $response = $this->get('http://'.$context['domain']->hostname.'/pages/about');

    $response->assertOk();
    $response->assertSee('About Us');
    $response->assertSee('Our Story');
});

it('returns 404 for a draft CMS page', function () {
    $context = createStoreContext();

    Page::factory()->draft()->for($context['store'])->create(['handle' => 'coming-soon']);

    $this->get('http://'.$context['domain']->hostname.'/pages/coming-soon')->assertNotFound();
});

it('returns 404 for an unknown product handle', function () {
    $context = createStoreContext();

    $this->get('http://'.$context['domain']->hostname.'/products/does-not-exist')->assertNotFound();
});

it('returns 404 for an unknown collection handle', function () {
    $context = createStoreContext();

    $this->get('http://'.$context['domain']->hostname.'/collections/does-not-exist')->assertNotFound();
});

it('renders navigation menu items in the header and footer', function () {
    $context = createStoreContext();

    $collection = Collection::factory()->for($context['store'])->create([
        'title' => 'Best Sellers',
        'handle' => 'best-sellers',
    ]);

    $menu = \App\Models\NavigationMenu::factory()->for($context['store'])->create(['handle' => 'main-menu']);
    \App\Models\NavigationItem::factory()->for($menu, 'menu')->collection($collection->getKey())->create(['label' => 'Best Sellers']);

    $response = $this->get('http://'.$context['domain']->hostname.'/');

    $response->assertOk();
    $response->assertSee('Best Sellers');
    $response->assertSee('/collections/best-sellers');
});

it('updates the price when a different variant is selected and adds it to the cart', function () {
    $context = createStoreContext();

    $product = Product::factory()->active()->for($context['store'])->create(['handle' => 'tee']);

    $option = ProductOption::factory()->for($product)->create(['name' => 'Size']);
    $small = $option->values()->create(['value' => 'S', 'position' => 0]);
    $medium = $option->values()->create(['value' => 'M', 'position' => 1]);

    $smallVariant = ProductVariant::factory()->asDefault()->priced(2999)->for($product)->create();
    $mediumVariant = ProductVariant::factory()->priced(3499)->for($product)->create(['position' => 1]);

    $smallVariant->optionValues()->attach($small);
    $mediumVariant->optionValues()->attach($medium);

    Livewire\Livewire::test(\App\Livewire\Storefront\Products\Show::class, ['handle' => 'tee'])
        ->assertSee('29.99 EUR')
        ->set('selectedOptions.Size', 'M')
        ->assertSee('34.99 EUR')
        ->call('addToCart')
        ->assertDispatched('cart-updated', itemCount: 1)
        ->assertSee('Added to cart');

    test()->assertDatabaseHas('cart_lines', [
        'variant_id' => $mediumVariant->getKey(),
        'quantity' => 1,
        'unit_price_amount' => 3499,
    ]);
});

it('does not leak products from another store on the storefront', function () {
    $context = createStoreContext();
    $otherContext = createStoreContext();

    createPublishedProduct($otherContext['store'], 9999, ['title' => 'Foreign Store Product']);

    app()->instance('current_store', $context['store']);

    $response = $this->get('http://'.$context['domain']->hostname.'/');

    $response->assertOk();
    $response->assertDontSee('Foreign Store Product');
});
