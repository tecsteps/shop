<?php

namespace App\Livewire\Admin\Search;

use App\Jobs\ReindexSearch;
use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\SearchSetting;
use App\Services\SearchService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Settings extends Component
{
    use ResolvesAdminStore;

    /** @var array<int, string> */
    public array $synonymGroups = [];

    public string $stopWords = '';

    public bool $isReindexing = false;

    public ?int $reindexProgress = null;

    public function mount(): void
    {
        Gate::authorize('manage-search-settings');

        $settings = $this->settings();
        $synonyms = is_array($settings->synonyms_json) ? $settings->synonyms_json : [];
        $stopWords = is_array($settings->stop_words_json) ? $settings->stop_words_json : [];

        $this->synonymGroups = array_map(
            fn ($group): string => is_array($group) ? implode(', ', $group) : (string) $group,
            array_values($synonyms)
        );
        $this->stopWords = implode(', ', array_map('strval', $stopWords));
    }

    public function addSynonymGroup(): void
    {
        $this->synonymGroups[] = '';
    }

    public function removeSynonymGroup(int $index): void
    {
        unset($this->synonymGroups[$index]);
        $this->synonymGroups = array_values($this->synonymGroups);
    }

    public function save(): void
    {
        Gate::authorize('manage-search-settings');

        $validated = $this->validate([
            'synonymGroups' => 'nullable|array',
            'synonymGroups.*' => 'nullable|string|max:1000',
            'stopWords' => 'nullable|string|max:5000',
        ]);

        $synonyms = [];

        foreach ($validated['synonymGroups'] ?? [] as $group) {
            $words = array_values(array_filter(array_map(trim(...), explode(',', (string) $group))));

            if ($words !== []) {
                $synonyms[] = $words;
            }
        }

        $this->settings()->update([
            'synonyms_json' => $synonyms,
            'stop_words_json' => array_values(array_filter(array_map(trim(...), explode(',', (string) $validated['stopWords'])))),
        ]);

        $this->toast('success', 'Search settings saved.');
    }

    public function triggerReindex(): void
    {
        Gate::authorize('manage-search-settings');

        $this->isReindexing = true;
        $this->reindexProgress = 0;

        ReindexSearch::dispatch($this->store()->getKey());

        $this->pollReindexStatus();
    }

    public function pollReindexStatus(): void
    {
        $count = app(SearchService::class)->documentCount($this->store());

        $this->reindexProgress = 100;
        $this->isReindexing = false;

        if ($count >= 0) {
            $this->toast('success', "Reindex complete. {$count} documents indexed.");
        }
    }

    public function render()
    {
        return view('livewire.admin.search.settings', [
            'lastIndexedAt' => null,
            'documentCount' => app(SearchService::class)->documentCount($this->store()),
        ])->layout('livewire.admin.layout.app', ['title' => 'Search settings']);
    }

    protected function settings(): SearchSetting
    {
        return SearchSetting::query()->firstOrCreate(
            ['store_id' => $this->store()->getKey()],
            ['synonyms_json' => [], 'stop_words_json' => []]
        );
    }
}
