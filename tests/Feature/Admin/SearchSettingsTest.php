<?php

use App\Jobs\ReindexSearch;
use App\Livewire\Admin\Search\Settings;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    ['store' => $this->store, 'user' => $this->user] = $this->createStoreContext();
    $this->actingAsAdmin($this->user, $this->store);
});

it('renders the search settings page', function () {
    $this->get(route('admin.search.settings'))->assertOk();
});

it('saves synonyms and stop words', function () {
    Livewire::test(Settings::class)
        ->call('addSynonymGroup')
        ->set('synonymGroups.0', 'tee, t-shirt, tshirt')
        ->set('stopWords', 'the, a, an')
        ->call('save')
        ->assertDispatched('toast');

    $settings = $this->store->refresh()->searchSetting;

    expect($settings->synonyms_json)->toBe([['tee', 't-shirt', 'tshirt']])
        ->and($settings->stop_words_json)->toBe(['the', 'a', 'an']);
});

it('dispatches a reindex job', function () {
    Queue::fake([ReindexSearch::class]);

    Livewire::test(Settings::class)
        ->call('triggerReindex');

    Queue::assertPushed(ReindexSearch::class, fn ($job) => $job->storeId === $this->store->getKey());
});
