<?php

namespace App\Livewire\Admin\Search;

use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\SearchSetting;
use App\Services\SearchService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Search settings: synonym groups and stop words (persisted to the
 * search_settings bag) plus a reindex action wired to the platform
 * SearchService. Restricted to roles that may manage search settings.
 */
#[Layout('livewire.admin.layout.app')]
class Settings extends Component
{
    use BindsCurrentStore;

    /** @var array<int, string> Each group is a comma-separated string. */
    public array $synonymGroups = [];

    public string $stopWords = '';

    public ?string $lastIndexedAt = null;

    public ?int $lastIndexedCount = null;

    public function mount(): void
    {
        if (! Gate::allows('manage-search-settings')) {
            abort(403);
        }

        $setting = SearchSetting::query()->find(app('current_store')->id);

        if ($setting !== null) {
            $this->synonymGroups = array_map(
                fn (array $group): string => implode(', ', $group),
                $setting->synonyms_json ?? [],
            );
            $this->stopWords = implode(', ', $setting->stop_words_json ?? []);
        }

        if ($this->synonymGroups === []) {
            $this->synonymGroups = [''];
        }
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
        if (! Gate::allows('manage-search-settings')) {
            abort(403);
        }

        $synonyms = collect($this->synonymGroups)
            ->map(fn (string $group): array => collect(explode(',', $group))->map(fn ($w) => trim($w))->filter()->values()->all())
            ->filter(fn (array $g): bool => count($g) > 1)
            ->values()
            ->all();

        $stops = collect(explode(',', $this->stopWords))->map(fn ($w) => trim($w))->filter()->values()->all();

        SearchSetting::query()->updateOrCreate(
            ['store_id' => app('current_store')->id],
            ['synonyms_json' => $synonyms, 'stop_words_json' => $stops],
        );

        $this->dispatch('toast', type: 'success', message: __('Settings saved'));
    }

    public function triggerReindex(SearchService $search): void
    {
        if (! Gate::allows('manage-search-settings')) {
            abort(403);
        }

        $count = $search->reindexStore(app('current_store'));

        $this->lastIndexedAt = now()->format('M j, Y g:i A');
        $this->lastIndexedCount = $count;

        $this->dispatch('toast', type: 'success', message: __(':count products reindexed.', ['count' => $count]));
    }

    public function render()
    {
        return view('livewire.admin.search.settings');
    }
}
