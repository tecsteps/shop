<?php

namespace App\Livewire\Admin\Search;

use App\Jobs\ReindexProducts;
use App\Livewire\Admin\AdminComponent;
use App\Models\SearchSettings;
use App\Models\StoreSettings;
use Illuminate\View\View;

class Settings extends AdminComponent
{
    /** @var list<string> */
    public array $synonymGroups = [];

    public string $stopWords = '';

    public ?string $lastIndexedAt = null;

    public bool $isReindexing = false;

    public ?int $reindexProgress = null;

    public function mount(): void
    {
        $this->authorizeSettings();
        $settings = SearchSettings::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->first();
        $this->synonymGroups = collect((array) ($settings?->synonyms_json ?? []))->map(function (mixed $group): string {
            return is_array($group) ? implode(', ', array_map('strval', $group)) : (string) $group;
        })->values()->all();
        if ($this->synonymGroups === []) {
            $this->synonymGroups = [''];
        }
        $this->stopWords = implode(', ', array_map('strval', (array) ($settings?->stop_words_json ?? [])));
        $storeSettings = StoreSettings::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->first();
        $searchStatus = (array) data_get($storeSettings?->settings_json, 'search', []);
        $this->lastIndexedAt = $searchStatus['last_reindex_at'] ?? $searchStatus['last_indexed_at'] ?? null;
        $this->reindexProgress = (int) ($searchStatus['progress'] ?? 0);
        $this->isReindexing = in_array($searchStatus['status'] ?? null, ['queued', 'processing'], true);
    }

    public function addSynonymGroup(): void
    {
        $this->authorizeSettings();
        $this->synonymGroups[] = '';
    }

    public function removeSynonymGroup(int $index): void
    {
        $this->authorizeSettings();
        abort_unless(array_key_exists($index, $this->synonymGroups), 404);
        unset($this->synonymGroups[$index]);
        $this->synonymGroups = array_values($this->synonymGroups);
    }

    public function save(): void
    {
        $this->authorizeSettings();
        $data = $this->validate([
            'synonymGroups' => ['array', 'max:100'],
            'synonymGroups.*' => ['nullable', 'string', 'max:1000'],
            'stopWords' => ['nullable', 'string', 'max:10000'],
        ]);
        $synonyms = collect($data['synonymGroups'])->map(fn (string $group): array => $this->commaList($group))->filter()->values()->all();
        SearchSettings::withoutGlobalScopes()->updateOrCreate(['store_id' => $this->currentStore()->id], [
            'synonyms_json' => $synonyms,
            'stop_words_json' => $this->commaList((string) $data['stopWords']),
        ]);
        $this->toast('Search settings saved');
    }

    public function triggerReindex(): void
    {
        $this->authorizeSettings();
        $record = StoreSettings::withoutGlobalScopes()->firstOrNew(['store_id' => $this->currentStore()->id]);
        $values = (array) $record->settings_json;
        if (in_array(data_get($values, 'search.status'), ['queued', 'processing'], true)) {
            $this->pollReindexStatus();
            $this->toast('A search reindex is already in progress');

            return;
        }

        $this->isReindexing = true;
        $this->reindexProgress = 0;
        data_set($values, 'search.status', 'queued');
        data_set($values, 'search.progress', 0);
        $record->settings_json = $values;
        $record->save();
        ReindexProducts::dispatch((int) $this->currentStore()->id)->afterCommit();
        $this->pollReindexStatus();
        $this->toast($this->isReindexing ? 'Search reindex queued' : 'Search index rebuilt');
    }

    public function pollReindexStatus(): void
    {
        $this->authorizeSettings();
        $record = StoreSettings::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->first();
        $settings = (array) data_get($record?->settings_json, 'search', []);
        $this->reindexProgress = (int) ($settings['progress'] ?? 0);
        $this->lastIndexedAt = $settings['last_reindex_at'] ?? $settings['last_indexed_at'] ?? $this->lastIndexedAt;
        $this->isReindexing = in_array($settings['status'] ?? null, ['queued', 'processing'], true);
    }

    public function render(): View
    {
        return $this->admin(view('admin.search.settings'), 'Search Settings', [['label' => 'Search Settings']]);
    }

    private function authorizeSettings(): void
    {
        $this->requireRoles(['owner', 'admin']);
        $this->authorizeAction('update', $this->currentStore());
    }

    /** @return list<string> */
    private function commaList(string $value): array
    {
        return collect(explode(',', $value))->map(fn (string $word): string => trim($word))->filter()->unique()->values()->all();
    }
}
