<?php

use App\Livewire\Storefront\Collections\Index;
use App\Livewire\Storefront\Collections\Show;
use App\Models\Collection;
use Livewire\Livewire;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('lists active collections', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $collection = Collection::factory()->create(['store_id' => $store->id, 'status' => 'active']);

    $response = $this->get(storefrontUrl($domain->hostname, '/collections'));

    $response->assertOk();
    $response->assertSee($collection->title);
});

it('does not list draft collections or other stores collections', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    Collection::factory()->create(['store_id' => $store->id, 'status' => 'draft', 'title' => 'Draft Collection Title']);
    ['store' => $otherStore] = $this->createStoreContext();
    Collection::factory()->create(['store_id' => $otherStore->id, 'status' => 'active', 'title' => 'Foreign Collection Title']);

    $response = $this->get(storefrontUrl($domain->hostname, '/collections'));

    $response->assertOk();
    $response->assertDontSee('Draft Collection Title');
    $response->assertDontSee('Foreign Collection Title');
});

it('renders a collection page with its products', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $collection = Collection::factory()->create(['store_id' => $store->id, 'status' => 'active']);
    $variant = CommerceTestHelper::purchasableVariant($store);
    $collection->products()->attach($variant->product_id);

    $response = $this->get(storefrontUrl($domain->hostname, '/collections/'.$collection->handle));

    $response->assertOk();
    $response->assertSee($collection->title);
    $response->assertSee($variant->product->title);
});

it('returns 404 for unknown or foreign collections', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    ['store' => $otherStore] = $this->createStoreContext();
    $foreign = Collection::factory()->create(['store_id' => $otherStore->id, 'status' => 'active']);

    $this->get(storefrontUrl($domain->hostname, '/collections/nope'))->assertNotFound();
    $this->get(storefrontUrl($domain->hostname, '/collections/'.$foreign->handle))->assertNotFound();
});

it('sorts collection products by price', function () {
    ['store' => $store] = $this->createStoreContext();
    $collection = Collection::factory()->create(['store_id' => $store->id, 'status' => 'active']);
    $cheap = CommerceTestHelper::purchasableVariant($store, ['price_amount' => 1000], productOverrides: ['title' => 'Cheap Widget']);
    $pricey = CommerceTestHelper::purchasableVariant($store, ['price_amount' => 9000], productOverrides: ['title' => 'Pricey Widget']);
    $collection->products()->attach([$cheap->product_id, $pricey->product_id]);

    Livewire::test(Show::class, ['handle' => $collection->handle])
        ->set('sort', 'price_asc')
        ->assertSeeInOrder(['Cheap Widget', 'Pricey Widget'])
        ->set('sort', 'price_desc')
        ->assertSeeInOrder(['Pricey Widget', 'Cheap Widget']);
});

it('filters collection products by vendor and stock', function () {
    ['store' => $store] = $this->createStoreContext();
    $collection = Collection::factory()->create(['store_id' => $store->id, 'status' => 'active']);
    $inStock = CommerceTestHelper::purchasableVariant($store, [], [], ['title' => 'Stocked Acme Item', 'vendor' => 'Acme']);
    $outOfStock = CommerceTestHelper::purchasableVariant($store, [], ['quantity_on_hand' => 0], ['title' => 'Gone Globex Item', 'vendor' => 'Globex']);
    $collection->products()->attach([$inStock->product_id, $outOfStock->product_id]);

    Livewire::test(Show::class, ['handle' => $collection->handle])
        ->set('vendors', ['Acme'])
        ->assertSee('Stocked Acme Item')
        ->assertDontSee('Gone Globex Item')
        ->call('clearFilters')
        ->assertSee('Gone Globex Item')
        ->set('inStock', true)
        ->assertSee('Stocked Acme Item')
        ->assertDontSee('Gone Globex Item');
});

it('renders the collections index component', function () {
    ['store' => $store] = $this->createStoreContext();
    $collection = Collection::factory()->create(['store_id' => $store->id, 'status' => 'active']);

    Livewire::test(Index::class)->assertSee($collection->title)->assertStatus(200);
});
