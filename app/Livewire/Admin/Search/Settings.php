<?php

namespace App\Livewire\Admin\Search;

use App\Models\SearchSettings;
use App\Services\SearchService;
use Livewire\Component;

class Settings extends Component
{
    public string $synonymsText = '';

    public string $stopWordsText = '';

    public ?string $reindexMessage = null;

    public function mount(): void
    {
        $store = app('current_store');
        $settings = SearchSettings::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->first();

        if ($settings) {
            $this->synonymsText = implode("\n", $settings->synonyms_json ?? []);
            $this->stopWordsText = implode("\n", $settings->stop_words_json ?? []);
        }
    }

    public function save(): void
    {
        $store = app('current_store');

        $synonyms = array_values(array_filter(
            array_map('trim', explode("\n", $this->synonymsText))
        ));

        $stopWords = array_values(array_filter(
            array_map('trim', explode("\n", $this->stopWordsText))
        ));

        SearchSettings::withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $store->id],
            [
                'synonyms_json' => $synonyms,
                'stop_words_json' => $stopWords,
                'updated_at' => now(),
            ]
        );

        $this->dispatch('toast', message: 'Search settings saved.', type: 'success');
    }

    public function reindex(): void
    {
        $store = app('current_store');
        $searchService = app(SearchService::class);
        $count = $searchService->reindexAll($store);

        $this->reindexMessage = "Reindexed {$count} products.";
        $this->dispatch('toast', message: "Reindexed {$count} products.", type: 'success');
    }

    public function render(): mixed
    {
        return view('livewire.admin.search.settings')
            ->layout('layouts.admin', ['title' => 'Search Settings']);
    }
}
