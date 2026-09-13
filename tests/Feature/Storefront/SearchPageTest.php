<?php

use App\Livewire\Storefront\Search\Index;
use App\Livewire\Storefront\SearchModal;
use Livewire\Livewire;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders search results for a query', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store, productOverrides: ['title' => 'Blue Cotton T-Shirt']);

    $response = $this->get(storefrontUrl($domain->hostname, '/search?q=cotton'));

    $response->assertOk();
    $response->assertSee('results for');
    $response->assertSee($variant->product->title);
});

it('shows an empty state when nothing matches', function () {
    ['domain' => $domain] = $this->createStoreContext();

    $response = $this->get(storefrontUrl($domain->hostname, '/search?q=zzz-no-match-zzz'));

    $response->assertOk();
    $response->assertSee('No results found');
});

it('does not leak other stores products in search', function () {
    ['domain' => $domain] = $this->createStoreContext();
    ['store' => $otherStore] = $this->createStoreContext();
    CommerceTestHelper::purchasableVariant($otherStore, productOverrides: ['title' => 'Foreign Searchable Gadget']);

    $response = $this->get(storefrontUrl($domain->hostname, '/search?q=gadget'));

    $response->assertOk();
    $response->assertDontSee('Foreign Searchable Gadget');
});

it('searches via the Livewire component', function () {
    ['store' => $store] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store, productOverrides: ['title' => 'Red Wool Sweater']);

    Livewire::test(Index::class)
        ->set('query', 'wool')
        ->assertSee($variant->product->title);
});

it('suggests products in the search modal', function () {
    ['store' => $store] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store, productOverrides: ['title' => 'Modal Suggest Jacket']);

    Livewire::test(SearchModal::class)
        ->assertSet('open', false)
        ->dispatch('open-search-modal')
        ->assertSet('open', true)
        ->set('query', 'Modal Suggest')
        ->assertSee($variant->product->title);
});
