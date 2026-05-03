<?php

namespace App\Livewire\Admin\Search;

use App\Livewire\Admin\Concerns\UsesAdminStore;
use App\Models\SearchSettings;
use App\Services\SearchService;
use Illuminate\View\View;
use Livewire\Component;

class Settings extends Component
{
    use UsesAdminStore;

    public string $synonymGroups = '';

    public string $stopWords = '';

    public ?string $lastIndexedAt = null;

    public function mount(): void
    {
        $settings = $this->settings();
        $this->synonymGroups = collect($settings->synonyms_json)
            ->map(fn (array $group): string => implode(', ', $group))
            ->implode("\n");
        $this->stopWords = implode("\n", $settings->stop_words_json ?? []);
        $this->lastIndexedAt = $settings->updated_at?->diffForHumans();
    }

    public function save(): void
    {
        $settings = $this->settings();
        $settings->forceFill([
            'synonyms_json' => $this->synonyms(),
            'stop_words_json' => $this->lines($this->stopWords),
        ])->save();

        $this->lastIndexedAt = $settings->fresh()->updated_at?->diffForHumans();

        $this->notify('Search settings saved.');
    }

    public function reindex(): void
    {
        $count = app(SearchService::class)->reindexStore($this->currentStore());
        $this->lastIndexedAt = $this->settings()->fresh()->updated_at?->diffForHumans();

        $this->notify("Search index rebuilt for {$count} products.");
    }

    public function render(): View
    {
        return view('livewire.admin.search.settings')->layout('livewire.admin.layout.app', [
            'title' => 'Search settings',
        ]);
    }

    private function settings(): SearchSettings
    {
        return SearchSettings::query()->firstOrCreate(['store_id' => $this->currentStore()->id]);
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

    /**
     * @return list<list<string>>
     */
    private function synonyms(): array
    {
        return collect(explode("\n", $this->synonymGroups))
            ->map(fn (string $line): array => collect(explode(',', $line))
                ->map(fn (string $term): string => trim($term))
                ->filter()
                ->values()
                ->all())
            ->filter(fn (array $group): bool => count($group) > 1)
            ->values()
            ->all();
    }
}
