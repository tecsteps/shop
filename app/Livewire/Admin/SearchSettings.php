<?php

namespace App\Livewire\Admin;

use App\Models\SearchSettings as SearchSettingsModel;
use Illuminate\Support\Facades\Gate;

#[\Livewire\Attributes\Layout('layouts.admin')]
class SearchSettings extends AdminComponent
{
    public string $synonyms = '';

    public string $stopWords = '';

    public function mount(): void
    {
        Gate::authorize('viewSettings', $this->currentStore());
        $settings = SearchSettingsModel::query()->firstOrCreate(['store_id' => $this->currentStore()->getKey()]);
        $this->synonyms = collect($settings->synonyms_json)->map(fn ($values, $term): string => $term.'='.implode(',', (array) $values))->implode("\n");
        $this->stopWords = implode(', ', $settings->stop_words_json);
    }

    public function save(): void
    {
        Gate::authorize('updateSettings', $this->currentStore());
        $validated = $this->validate(['synonyms' => ['nullable', 'string', 'max:10000'], 'stopWords' => ['nullable', 'string', 'max:5000']]);
        $synonyms = collect(preg_split('/\r\n|\r|\n/', $validated['synonyms']) ?: [])->filter()->mapWithKeys(function (string $line): array {
            [$term, $values] = array_pad(explode('=', $line, 2), 2, '');

            return [trim($term) => collect(explode(',', $values))->map(fn (string $value): string => trim($value))->filter()->values()->all()];
        })->all();
        SearchSettingsModel::query()->updateOrCreate(['store_id' => $this->currentStore()->getKey()], ['synonyms_json' => $synonyms, 'stop_words_json' => collect(explode(',', $validated['stopWords']))->map(fn (string $word): string => trim($word))->filter()->values()->all()]);
        $this->toast('Search settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.search-settings');
    }
}
