<?php

use App\Livewire\Admin\Search\Settings as SearchSettings;
use App\Models\SearchSettings as SearchSettingsModel;
use Livewire\Livewire;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->actingAs($this->ctx['user']);
    session(['current_store_id' => $this->ctx['store']->id]);

    SearchSettingsModel::create([
        'store_id' => $this->ctx['store']->id,
        'synonyms_json' => [],
        'stop_words_json' => [],
    ]);
});

it('renders the search settings page', function () {
    $this->get('/admin/search/settings')
        ->assertStatus(200)
        ->assertSee('Search Settings');
});

it('adds a synonym group', function () {
    $component = Livewire::test(SearchSettings::class);
    $component->set('newSynonym', 'shirt, tee, top');
    $component->call('addSynonym');

    $component->assertSet('synonyms', ['shirt, tee, top']);

    $settings = SearchSettingsModel::find($this->ctx['store']->id);
    expect($settings->synonyms_json)->toBe(['shirt, tee, top']);
});

it('removes a synonym group', function () {
    SearchSettingsModel::where('store_id', $this->ctx['store']->id)
        ->update(['synonyms_json' => ['shirt, tee', 'pants, trousers']]);

    $component = Livewire::test(SearchSettings::class);
    $component->call('removeSynonym', 0);

    $component->assertSet('synonyms', ['pants, trousers']);

    $settings = SearchSettingsModel::find($this->ctx['store']->id);
    expect($settings->synonyms_json)->toBe(['pants, trousers']);
});

it('validates synonym is required', function () {
    $component = Livewire::test(SearchSettings::class);
    $component->set('newSynonym', '');
    $component->call('addSynonym');
    $component->assertHasErrors('newSynonym');
});

it('adds a stop word', function () {
    $component = Livewire::test(SearchSettings::class);
    $component->set('newStopWord', 'the');
    $component->call('addStopWord');

    $component->assertSet('stopWords', ['the']);

    $settings = SearchSettingsModel::find($this->ctx['store']->id);
    expect($settings->stop_words_json)->toBe(['the']);
});

it('removes a stop word', function () {
    SearchSettingsModel::where('store_id', $this->ctx['store']->id)
        ->update(['stop_words_json' => ['the', 'and', 'or']]);

    $component = Livewire::test(SearchSettings::class);
    $component->call('removeStopWord', 1);

    $component->assertSet('stopWords', ['the', 'or']);
});

it('validates stop word is required', function () {
    $component = Livewire::test(SearchSettings::class);
    $component->set('newStopWord', '');
    $component->call('addStopWord');
    $component->assertHasErrors('newStopWord');
});

it('reindexes the store', function () {
    $component = Livewire::test(SearchSettings::class);
    $component->call('reindex');
    $component->assertDispatched('toast');
});

it('requires authentication', function () {
    auth()->logout();
    $this->get('/admin/search/settings')->assertRedirect('/admin/login');
});
