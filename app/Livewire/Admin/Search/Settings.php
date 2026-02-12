<?php

namespace App\Livewire\Admin\Search;

use App\Models\SearchSettings;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Search Settings')]
class Settings extends Component
{
    public string $synonymsJson = '';

    public string $stopWordsJson = '';

    public string $boostFieldsJson = '';

    public function mount(): void
    {
        $store = app('current_store');
        $settings = SearchSettings::where('store_id', $store->id)->first();

        if ($settings) {
            $this->synonymsJson = json_encode($settings->synonyms_json ?? [], JSON_PRETTY_PRINT);
            $this->stopWordsJson = json_encode($settings->stop_words_json ?? [], JSON_PRETTY_PRINT);
            $this->boostFieldsJson = json_encode($settings->boost_fields_json ?? [], JSON_PRETTY_PRINT);
        } else {
            $this->synonymsJson = '[]';
            $this->stopWordsJson = '[]';
            $this->boostFieldsJson = '[]';
        }
    }

    public function save(): void
    {
        $this->validate([
            'synonymsJson' => ['required', 'json'],
            'stopWordsJson' => ['required', 'json'],
            'boostFieldsJson' => ['required', 'json'],
        ]);

        $store = app('current_store');

        SearchSettings::updateOrCreate(
            ['store_id' => $store->id],
            [
                'synonyms_json' => json_decode($this->synonymsJson, true),
                'stop_words_json' => json_decode($this->stopWordsJson, true),
                'boost_fields_json' => json_decode($this->boostFieldsJson, true),
            ]
        );

        $this->dispatch('toast', type: 'success', message: 'Search settings saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.search.settings');
    }
}
