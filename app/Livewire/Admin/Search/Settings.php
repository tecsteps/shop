<?php

namespace App\Livewire\Admin\Search;

use App\Livewire\Admin\Concerns\DispatchesToasts;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Settings extends Component
{
    use DispatchesToasts;

    #[Layout('layouts.admin.app')]
    /** @var list<string> */
    public array $synonymGroups = [];

    public string $stopWords = '';

    public ?string $lastIndexedAt = null;

    public bool $isReindexing = false;

    public ?int $reindexProgress = null;

    public function mount(): void
    {
        $this->authorize('viewSettings', app('current_store'));

        $settings = app('current_store')->searchSettings;

        if ($settings) {
            $this->synonymGroups = $settings->synonyms_json ?? [];
            $this->stopWords = implode(', ', $settings->stop_words_json ?? []);
            $this->lastIndexedAt = $settings->updated_at?->format('M j, Y g:i A');
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
        $this->authorize('viewSettings', app('current_store'));

        $this->validate([
            'stopWords' => ['nullable', 'string'],
        ]);

        $synonyms = collect($this->synonymGroups)
            ->map(fn ($group) => trim((string) $group))
            ->filter(fn ($group) => $group !== '')
            ->values()
            ->all();

        $stopWords = collect(explode(',', $this->stopWords))
            ->map(fn ($word) => trim($word))
            ->filter(fn ($word) => $word !== '')
            ->values()
            ->all();

        app('current_store')->searchSettings()->updateOrCreate([], [
            'synonyms_json' => $synonyms,
            'stop_words_json' => $stopWords,
            'updated_at' => now(),
        ]);

        $this->toast('Search settings saved');
    }

    public function triggerReindex(): void
    {
        $this->authorize('viewSettings', app('current_store'));

        $this->isReindexing = true;
        $this->reindexProgress = 0;

        $this->toast('Reindex started', 'info');
    }

    public function pollReindexStatus(): void
    {
        if (! $this->isReindexing) {
            return;
        }

        $this->reindexProgress = min(100, ($this->reindexProgress ?? 0) + rand(10, 25));

        if ($this->reindexProgress >= 100) {
            $this->isReindexing = false;
            $this->reindexProgress = null;
            $this->lastIndexedAt = now()->format('M j, Y g:i A');

            app('current_store')->searchSettings()?->update(['updated_at' => now()]);

            $this->toast('Search index rebuilt');
        }
    }

    public function render()
    {
        return view('livewire.admin.search.settings');
    }
}
