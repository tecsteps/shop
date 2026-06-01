<?php

use App\Livewire\Storefront\Search\Index as SearchIndex;
use App\Livewire\Storefront\Search\Modal as SearchModal;
use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext(['hostname' => 'acme-fashion.test']);
    $this->store = $this->context['store'];

    // The ProductObserver indexes these into products_fts on create.
    $this->beanie = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Merino Wool Beanie',
        'handle' => 'merino-wool-beanie',
    ]);
    ProductVariant::factory()->default()->create(['product_id' => $this->beanie->id, 'price_amount' => 3500]);

    $this->gloves = Product::factory()->active()->create([
        'store_id' => $this->store->id,
        'title' => 'Leather Gloves',
        'handle' => 'leather-gloves',
    ]);
    ProductVariant::factory()->default()->create(['product_id' => $this->gloves->id, 'price_amount' => 4500]);
});

it('renders the search results page for a matching query', function () {
    $this->get(storefrontUrl('acme-fashion.test', '/search?q=beanie'))
        ->assertOk()
        ->assertSee('Merino Wool Beanie')
        ->assertDontSee('Leather Gloves');
});

it('shows an empty state for a non-matching query', function () {
    $this->get(storefrontUrl('acme-fashion.test', '/search?q=zzzznomatch'))
        ->assertOk()
        ->assertSee('No results found');
});

it('shows the bare search page with no query', function () {
    $this->get(storefrontUrl('acme-fashion.test', '/search'))
        ->assertOk()
        ->assertSee('Search');
});

it('search results component returns matching product cards', function () {
    $page = Livewire::test(SearchIndex::class, ['query' => 'beanie']);

    $titles = $page->viewData('results')->pluck('title')->all();

    expect($titles)->toContain('Merino Wool Beanie');
    expect($titles)->not->toContain('Leather Gloves');
    expect($page->viewData('total'))->toBe(1);
});

it('autocomplete modal suggests products for a 2+ char query', function () {
    Livewire::test(SearchModal::class)
        ->set('query', 'beanie')
        ->assertViewHas('products', fn ($products) => $products->contains('handle', 'merino-wool-beanie'))
        ->assertSee('Merino Wool Beanie');
});

it('autocomplete modal returns nothing for a single-character query', function () {
    Livewire::test(SearchModal::class)
        ->set('query', 'b')
        ->assertViewHas('products', fn ($products) => $products->isEmpty());
});

it('opens and closes the search modal on events', function () {
    Livewire::test(SearchModal::class)
        ->call('openModal')
        ->assertSet('open', true)
        ->call('closeModal')
        ->assertSet('open', false);
});
