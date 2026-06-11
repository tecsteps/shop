<?php

use App\Enums\StoreUserRole;
use App\Livewire\Admin\Search\Settings as SearchSettingsPage;
use App\Models\Product;
use App\Models\SearchSettings;
use App\Services\SearchService;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
});

it('renders the search settings page', function () {
    actingAsAdmin($this->user)
        ->get('/admin/search/settings')
        ->assertOk()
        ->assertSee('Synonyms')
        ->assertSee('Stop words')
        ->assertSee('Reindex now');
});

it('saves synonym groups and stop words', function () {
    actingAsAdmin($this->user);

    Livewire::test(SearchSettingsPage::class)
        ->call('addSynonymGroup')
        ->set('synonymGroups.0', 't-shirt, tee, tshirt')
        ->set('stopWords', 'the, a, an')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $settings = SearchSettings::query()->findOrFail($this->store->getKey());

    expect($settings->synonymGroups())->toBe([['t-shirt', 'tee', 'tshirt']]);
    expect($settings->stopWords())->toBe(['the', 'a', 'an']);
});

it('removes a synonym group', function () {
    SearchSettings::factory()->for($this->store)->withSynonyms([
        ['tee', 't-shirt'],
        ['pants', 'jeans'],
    ])->create();

    actingAsAdmin($this->user);

    Livewire::test(SearchSettingsPage::class)
        ->call('removeSynonymGroup', 0)
        ->call('save')
        ->assertHasNoErrors();

    expect(SearchSettings::query()->findOrFail($this->store->getKey())->synonymGroups())
        ->toBe([['pants', 'jeans']]);
});

it('rebuilds the search index from the reindex button', function () {
    $product = Product::factory()->active()->for($this->store)->create(['title' => 'Indexable Jacket']);

    DB::delete('DELETE FROM products_fts WHERE rowid = ?', [$product->getKey()]);

    expect(app(SearchService::class)->search($this->store, 'indexable', logQuery: false)->total())->toBe(0);

    actingAsAdmin($this->user);

    Livewire::test(SearchSettingsPage::class)
        ->call('triggerReindex')
        ->assertDispatched('toast');

    expect(app(SearchService::class)->search($this->store, 'indexable', logQuery: false)->total())->toBe(1);
});

it('restricts search settings to owner and admin roles', function () {
    $staff = createStoreMember($this->store, StoreUserRole::Staff);

    actingAsAdmin($staff, $this->store)
        ->get('/admin/search/settings')
        ->assertForbidden();
});
