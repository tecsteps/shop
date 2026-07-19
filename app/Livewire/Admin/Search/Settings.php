<?php

namespace App\Livewire\Admin\Search;

use App\Models\SearchSettings;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Admin search settings: synonyms, stop words, reindex (spec 03 §18).
 */
class Settings extends Component
{
    /** @var list<string> Synonym groups, each a comma-separated string */
    public array $synonymGroups = [];

    public string $stopWords = '';

    public ?string $lastIndexedAt = null;

    public bool $isReindexing = false;

    public function mount(): void
    {
        Gate::authorize('manage-search-settings');

        /** @var Store $store */
        $store = app('current_store');

        $settings = SearchSettings::query()->find($store->id);

        $this->synonymGroups = collect($settings?->synonyms_json ?? [])
            ->map(fn ($group): string => is_array($group) ? implode(', ', $group) : (string) $group)
            ->values()
            ->all();
        $this->stopWords = implode(', ', $settings?->stop_words_json ?? []);
        $this->lastIndexedAt = $settings?->updated_at?->toDayDateTimeString();
    }

    /**
     * Append a new empty synonym group row.
     */
    public function addSynonymGroup(): void
    {
        $this->synonymGroups[] = '';
    }

    /**
     * Drop a synonym group row.
     */
    public function removeSynonymGroup(int $index): void
    {
        unset($this->synonymGroups[$index]);

        $this->synonymGroups = array_values($this->synonymGroups);
    }

    /**
     * Persist synonym and stop word settings.
     */
    public function save(): void
    {
        Gate::authorize('manage-search-settings');

        /** @var Store $store */
        $store = app('current_store');

        $synonyms = collect($this->synonymGroups)
            ->map(fn (string $group): array => $this->splitTerms($group))
            ->filter(fn (array $group): bool => count($group) > 1)
            ->values()
            ->all();

        $stopWords = $this->splitTerms($this->stopWords);

        SearchSettings::query()->updateOrCreate(
            ['store_id' => $store->id],
            ['synonyms_json' => $synonyms, 'stop_words_json' => $stopWords],
        );

        $this->synonymGroups = collect($synonyms)->map(fn (array $group): string => implode(', ', $group))->all();
        $this->stopWords = implode(', ', $stopWords);

        $this->dispatch('toast', type: 'success', message: 'Search settings saved');
    }

    /**
     * Rebuild the store's FTS index synchronously (SQLite is fast enough
     * that a queued job adds no value here).
     */
    public function triggerReindex(SearchService $search): void
    {
        Gate::authorize('manage-search-settings');

        /** @var Store $store */
        $store = app('current_store');

        $this->isReindexing = true;

        $count = $search->reindex($store);

        $settings = SearchSettings::query()->firstOrNew(['store_id' => $store->id]);
        $settings->save();
        $settings->touch();

        $this->lastIndexedAt = now()->toDayDateTimeString();
        $this->isReindexing = false;

        $this->dispatch('toast', type: 'success', message: "Search index rebuilt ({$count} products)");
    }

    public function render(): View
    {
        return view('livewire.admin.search.settings', [
            'recentQueries' => \App\Models\SearchQuery::query()
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(20)
                ->get(),
        ])->layout('admin.layouts.app')->title('Search Settings');
    }

    /**
     * Split a comma-separated string into trimmed, non-empty terms.
     *
     * @return list<string>
     */
    private function splitTerms(string $value): array
    {
        return array_values(array_filter(
            array_map(fn (string $term): string => trim($term), explode(',', $value)),
            fn (string $term): bool => $term !== '',
        ));
    }
}
