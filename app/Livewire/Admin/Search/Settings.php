<?php

namespace App\Livewire\Admin\Search;

use App\Livewire\Admin\Concerns\UsesAdminStore;
use App\Models\StoreSettings;
use Illuminate\View\View;
use Livewire\Component;

class Settings extends Component
{
    use UsesAdminStore;

    public string $synonyms = '';

    public string $stopWords = '';

    public function mount(): void
    {
        $settings = $this->settings()->settings_json;
        $this->synonyms = implode("\n", data_get($settings, 'search.synonyms', []));
        $this->stopWords = implode("\n", data_get($settings, 'search.stop_words', []));
    }

    public function save(): void
    {
        $settings = $this->settings();
        $payload = $settings->settings_json;
        data_set($payload, 'search.synonyms', $this->lines($this->synonyms));
        data_set($payload, 'search.stop_words', $this->lines($this->stopWords));
        $settings->forceFill(['settings_json' => $payload])->save();

        $this->notify('Search settings saved.');
    }

    public function reindex(): void
    {
        $this->notify('Search index queued.');
    }

    public function render(): View
    {
        return view('livewire.admin.search.settings')->layout('livewire.admin.layout.app', [
            'title' => 'Search settings',
        ]);
    }

    private function settings(): StoreSettings
    {
        return StoreSettings::query()->firstOrCreate(['store_id' => $this->currentStore()->id]);
    }

    /**
     * @return list<string>
     */
    private function lines(string $value): array
    {
        return collect(explode("\n", $value))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}
