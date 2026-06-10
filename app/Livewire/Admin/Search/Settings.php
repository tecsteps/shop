<?php

namespace App\Livewire\Admin\Search;

use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\SearchSettings;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Search settings page (spec 03 section 18): synonym groups, stop words,
 * and a full reindex of the store's FTS5 search index.
 */
#[Layout('layouts::admin')]
class Settings extends Component
{
    use AuthorizesRequests, SendsToasts;

    /** @var list<string> Each group is a comma-separated string of equivalent terms. */
    public array $synonymGroups = [];

    public string $stopWords = '';

    public ?string $lastIndexedAt = null;

    public function mount(SearchService $search): void
    {
        $store = $this->store();

        $this->authorize('viewSettings', $store);

        $settings = SearchSettings::query()->find($store->getKey());

        $this->synonymGroups = array_map(
            fn (array $group): string => implode(', ', $group),
            $settings?->synonymGroups() ?? [],
        );

        $this->stopWords = implode(', ', $settings?->stopWords() ?? []);
        $this->lastIndexedAt = $search->lastReindexedAt($store);
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
        $store = $this->store();

        $this->authorize('updateSettings', $store);

        $this->validate([
            'synonymGroups' => ['array'],
            'synonymGroups.*' => ['nullable', 'string', 'max:500'],
            'stopWords' => ['nullable', 'string', 'max:2000'],
        ]);

        SearchSettings::query()->updateOrCreate(
            ['store_id' => $store->getKey()],
            [
                'synonyms_json' => $this->parsedSynonymGroups(),
                'stop_words_json' => $this->parsedStopWords(),
            ],
        );

        $this->toast(__('Settings saved'));
    }

    public function triggerReindex(SearchService $search): void
    {
        $store = $this->store();

        $this->authorize('updateSettings', $store);

        $search->reindexStore($store);

        $this->lastIndexedAt = $search->lastReindexedAt($store);

        $this->toast(__('Search index rebuilt'));
    }

    public function render(): View
    {
        return view('livewire.admin.search.settings')->title(__('Search settings'));
    }

    /**
     * @return list<list<string>>
     */
    protected function parsedSynonymGroups(): array
    {
        return array_values(array_filter(array_map(
            fn (string $group): array => $this->splitCommaList($group),
            $this->synonymGroups,
        ), fn (array $group): bool => count($group) > 1));
    }

    /**
     * @return list<string>
     */
    protected function parsedStopWords(): array
    {
        return $this->splitCommaList($this->stopWords);
    }

    /**
     * @return list<string>
     */
    protected function splitCommaList(string $value): array
    {
        $items = array_map(
            fn (string $item): string => mb_strtolower(trim($item)),
            explode(',', $value),
        );

        return array_values(array_unique(array_filter($items, fn (string $item): bool => $item !== '')));
    }

    protected function store(): Store
    {
        return app('current_store');
    }
}
