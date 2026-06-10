<?php

use App\Livewire\Storefront\Search\Index as SearchIndex;
use App\Livewire\Storefront\Search\Modal as SearchModal;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->baseUrl = 'http://'.$this->context['domain']->hostname;
});

/**
 * Create a published product with a priced default variant.
 *
 * @param  array<string, mixed>  $attributes
 */
function createSearchableProduct($store, string $title, int $priceAmount = 2500, array $attributes = []): Product
{
    $product = Product::factory()->active()->for($store)->create(['title' => $title, ...$attributes]);

    ProductVariant::factory()->asDefault()->priced($priceAmount)->for($product)->create();

    return $product;
}

it('renders the search results page with matching products', function () {
    createSearchableProduct($this->store, 'Linen Summer Shirt', 3499);
    createSearchableProduct($this->store, 'Wool Winter Coat', 9900);

    $this->get($this->baseUrl.'/search?q=linen')
        ->assertOk()
        ->assertSee('Linen Summer Shirt')
        ->assertSee('result for')
        ->assertDontSee('Wool Winter Coat');
});

it('shows the empty state for a query without matches', function () {
    createSearchableProduct($this->store, 'Linen Summer Shirt');

    $this->get($this->baseUrl.'/search?q=xyznonexistent')
        ->assertOk()
        ->assertSee('No results found');
});

it('filters search results by vendor', function () {
    createSearchableProduct($this->store, 'Linen Shirt Classic', 2500, ['vendor' => 'Acme Apparel']);
    createSearchableProduct($this->store, 'Linen Shirt Premium', 4500, ['vendor' => 'Other Brand']);

    app()->instance('current_store', $this->store);

    Livewire::test(SearchIndex::class, ['q' => 'linen'])
        ->set('query', 'linen')
        ->assertSee('Linen Shirt Classic')
        ->assertSee('Linen Shirt Premium')
        ->set('vendors', ['Acme Apparel'])
        ->assertSee('Linen Shirt Classic')
        ->assertDontSee('Linen Shirt Premium');
});

it('sorts search results by price', function () {
    createSearchableProduct($this->store, 'Linen Shirt Cheap', 1000);
    createSearchableProduct($this->store, 'Linen Shirt Expensive', 9000);

    app()->instance('current_store', $this->store);

    Livewire::test(SearchIndex::class)
        ->set('query', 'linen')
        ->set('sort', 'price_desc')
        ->assertSeeInOrder(['Linen Shirt Expensive', 'Linen Shirt Cheap']);
});

it('suggests products and collections in the search modal', function () {
    createSearchableProduct($this->store, 'Summer Dress', 5900);
    Collection::factory()->for($this->store)->create(['title' => 'Summer Collection']);

    app()->instance('current_store', $this->store);

    Livewire::test(SearchModal::class)
        ->set('query', 'summer')
        ->assertSee('Summer Dress')
        ->assertSee('Summer Collection')
        ->assertSee('View all');
});

it('shows no modal suggestions below the minimum prefix length', function () {
    createSearchableProduct($this->store, 'Anorak Jacket');

    app()->instance('current_store', $this->store);

    Livewire::test(SearchModal::class)
        ->set('query', 'a')
        ->assertDontSee('Anorak Jacket')
        ->assertSee('Start typing');
});

it('serves search results over the storefront API', function () {
    createSearchableProduct($this->store, 'Linen Summer Shirt', 3499, ['vendor' => 'Acme Apparel']);

    $this->getJson($this->baseUrl.'/api/storefront/v1/search?q=linen')
        ->assertOk()
        ->assertJsonPath('query', 'linen')
        ->assertJsonPath('results.0.title', 'Linen Summer Shirt')
        ->assertJsonPath('results.0.price_amount', 3499)
        ->assertJsonPath('pagination.total', 1)
        ->assertJsonStructure(['facets' => ['vendors', 'tags', 'price_range']]);
});

it('serves autocomplete suggestions over the storefront API', function () {
    createSearchableProduct($this->store, 'Summer Dress', 5900);

    $this->getJson($this->baseUrl.'/api/storefront/v1/search/suggest?q=sum')
        ->assertOk()
        ->assertJsonPath('suggestions.0.type', 'product')
        ->assertJsonPath('suggestions.0.title', 'Summer Dress');
});
