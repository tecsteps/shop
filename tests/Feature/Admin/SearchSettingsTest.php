<?php

use App\Livewire\Admin\Search\Settings;
use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\SearchSettings;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->store = $this->createStore();
    $this->user = $this->createUserWithRole($this->store, 'owner');
    $this->bindStore($this->store);
});

test('renders the search settings page', function () {
    $this->actingAs($this->user)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/search/settings')
        ->assertOk()
        ->assertSee('Search Settings')
        ->assertSee('Synonyms')
        ->assertSee('Stop words')
        ->assertSee('Search index')
        ->assertSee('Recent search queries');
});

test('saves synonym groups and stop words', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Settings::class)
        ->set('synonymGroups', ['t-shirt, tee, tshirt', 'sneakers, trainers'])
        ->set('stopWords', 'the, a, an')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $settings = SearchSettings::query()->where('store_id', $this->store->id)->sole();

    expect($settings->synonyms_json)->toBe([
        ['t-shirt', 'tee', 'tshirt'],
        ['sneakers', 'trainers'],
    ])->and($settings->stop_words_json)->toBe(['the', 'a', 'an']);
});

test('drops empty and single-term synonym groups on save', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Settings::class)
        ->set('synonymGroups', ['t-shirt, tee', '', 'lonely'])
        ->call('save')
        ->assertSet('synonymGroups', ['t-shirt, tee']);

    $settings = SearchSettings::query()->where('store_id', $this->store->id)->sole();

    expect($settings->synonyms_json)->toBe([['t-shirt', 'tee']]);
});

test('loads existing settings into the form', function () {
    SearchSettings::factory()
        ->withSynonyms([['sneakers', 'trainers']])
        ->withStopWords(['the'])
        ->create(['store_id' => $this->store->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Settings::class)
        ->assertSet('synonymGroups', ['sneakers, trainers'])
        ->assertSet('stopWords', 'the');
});

test('adds and removes synonym groups', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Settings::class)
        ->assertSet('synonymGroups', [])
        ->call('addSynonymGroup')
        ->call('addSynonymGroup')
        ->assertCount('synonymGroups', 2)
        ->call('removeSynonymGroup', 0)
        ->assertCount('synonymGroups', 1);
});

test('reindex rebuilds the FTS rows and toasts the count', function () {
    Product::factory()->active()->withVariants(1)->count(3)->create(['store_id' => $this->store->id]);

    DB::table('products_fts')->delete();

    Livewire::actingAs($this->user);
    Livewire::test(Settings::class)
        ->call('triggerReindex')
        ->assertDispatched('toast')
        ->assertSet('isReindexing', false);

    expect(DB::table('products_fts')->where('store_id', $this->store->id)->count())->toBe(3);
});

test('lists recent search queries', function () {
    SearchQuery::factory()->create(['store_id' => $this->store->id, 'query' => 'cotton shirt', 'results_count' => 4]);
    SearchQuery::factory()->create(['store_id' => $this->store->id, 'query' => 'wool socks', 'results_count' => 0]);

    Livewire::actingAs($this->user);
    Livewire::test(Settings::class)
        ->assertSee('cotton shirt')
        ->assertSee('wool socks');
});

test('enforces the manage-search-settings gate', function () {
    $staff = $this->createUserWithRole($this->store, 'staff');

    $this->actingAs($staff)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/search/settings')
        ->assertForbidden();
});

test('search settings are scoped to the current store', function () {
    $other = $this->createStore();
    SearchSettings::factory()->withStopWords(['otherstoreword'])->create(['store_id' => $other->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Settings::class)
        ->assertSet('stopWords', '');
});
