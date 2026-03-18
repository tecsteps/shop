<?php

namespace App\Livewire\Admin\Search;

use App\Models\SearchSettings as SearchSettingsModel;
use App\Services\SearchService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Settings extends Component
{
    public string $newSynonym = '';

    public string $newStopWord = '';

    /** @var array<int, string> */
    public array $synonyms = [];

    /** @var array<int, string> */
    public array $stopWords = [];

    public function mount(): void
    {
        $store = app('current_store');
        $settings = SearchSettingsModel::find($store->id);

        $this->synonyms = $settings->synonyms_json ?? [];
        $this->stopWords = $settings->stop_words_json ?? [];
    }

    public function addSynonym(): void
    {
        $this->validate([
            'newSynonym' => ['required', 'string', 'max:255'],
        ]);

        $this->synonyms[] = $this->newSynonym;
        $this->newSynonym = '';
        $this->persistSettings();

        $this->dispatch('toast', type: 'success', message: __('Synonym added.'));
    }

    public function removeSynonym(int $index): void
    {
        unset($this->synonyms[$index]);
        $this->synonyms = array_values($this->synonyms);
        $this->persistSettings();

        $this->dispatch('toast', type: 'success', message: __('Synonym removed.'));
    }

    public function addStopWord(): void
    {
        $this->validate([
            'newStopWord' => ['required', 'string', 'max:255'],
        ]);

        $this->stopWords[] = $this->newStopWord;
        $this->newStopWord = '';
        $this->persistSettings();

        $this->dispatch('toast', type: 'success', message: __('Stop word added.'));
    }

    public function removeStopWord(int $index): void
    {
        unset($this->stopWords[$index]);
        $this->stopWords = array_values($this->stopWords);
        $this->persistSettings();

        $this->dispatch('toast', type: 'success', message: __('Stop word removed.'));
    }

    public function reindex(): void
    {
        $store = app('current_store');
        $count = app(SearchService::class)->reindexStore($store);

        $this->dispatch('toast', type: 'success', message: __(':count products reindexed.', ['count' => $count]));
    }

    protected function persistSettings(): void
    {
        $store = app('current_store');

        SearchSettingsModel::updateOrCreate(
            ['store_id' => $store->id],
            [
                'synonyms_json' => $this->synonyms,
                'stop_words_json' => $this->stopWords,
            ],
        );
    }

    public function render(): View
    {
        return view('livewire.admin.search.settings');
    }
}
