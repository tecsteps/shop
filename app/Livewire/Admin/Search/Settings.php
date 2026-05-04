<?php

namespace App\Livewire\Admin\Search;

use App\Models\SearchSettings;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Settings extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public int $storeId;

    /**
     * @var array<int, string>
     */
    public array $synonymGroups = [];

    public string $stopWords = '';

    public ?string $lastIndexedAt = null;

    public bool $isReindexing = false;

    public ?int $reindexProgress = null;

    public function mount(): void
    {
        $store = $this->store();

        $this->authorize('update', $store);

        $this->storeId = $store->getKey();

        $settings = $this->settings();
        $this->synonymGroups = collect($settings->synonyms_json ?? [])
            ->map(fn (array $group): string => implode(', ', $group))
            ->values()
            ->all();
        $this->stopWords = implode(', ', $settings->stop_words_json ?? []);
        $this->lastIndexedAt = $settings->updated_at?->toDayDateTimeString();
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
        $this->authorize('update', $this->scopedStore());

        $this->validate([
            'synonymGroups' => ['array', 'max:50'],
            'synonymGroups.*' => ['nullable', 'string', 'max:500'],
            'stopWords' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'synonymGroups.*' => 'synonym group',
            'stopWords' => 'stop words',
        ]);

        $settings = $this->settings();
        $settings->forceFill([
            'synonyms_json' => $this->parseSynonyms(),
            'stop_words_json' => $this->parseWordList($this->stopWords),
        ])->save();

        $this->lastIndexedAt = $settings->updated_at?->toDayDateTimeString();

        session()->flash('status', 'Search settings saved');
        $this->dispatch('toast', type: 'success', message: __('Search settings saved'));
    }

    public function triggerReindex(SearchService $search): void
    {
        $store = $this->scopedStore();

        $this->authorize('update', $store);

        $this->isReindexing = true;
        $this->reindexProgress = 10;

        $count = $search->reindex($store);

        $settings = $this->settings();
        $settings->touch();

        $this->lastIndexedAt = $settings->refresh()->updated_at?->toDayDateTimeString();
        $this->reindexProgress = 100;
        $this->isReindexing = false;

        session()->flash('status', __('Search index rebuilt for :count products', ['count' => $count]));
        $this->dispatch('toast', type: 'success', message: __('Search index rebuilt'));
    }

    public function pollReindexStatus(): void
    {
        $this->reindexProgress = $this->isReindexing ? $this->reindexProgress : null;
    }

    public function render(): mixed
    {
        return view('livewire.admin.search.settings')->layout('layouts.app', [
            'title' => __('Search settings'),
        ]);
    }

    /**
     * @return list<list<string>>
     */
    private function parseSynonyms(): array
    {
        return collect($this->synonymGroups)
            ->map(fn (string $group): array => $this->parseWordList($group))
            ->filter(fn (array $group): bool => count($group) >= 2)
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function parseWordList(string $words): array
    {
        return collect(explode(',', mb_strtolower($words)))
            ->map(fn (string $word): string => trim($word))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function settings(): SearchSettings
    {
        return SearchSettings::withoutGlobalScopes()->firstOrCreate([
            'store_id' => $this->storeId ?? $this->store()->getKey(),
        ]);
    }

    private function store(): Store
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        return $store;
    }

    private function scopedStore(): Store
    {
        return Store::query()->findOrFail($this->storeId);
    }
}
