<?php

namespace App\Livewire\Admin\Search;

use App\Jobs\ReindexStoreProducts;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Settings extends Component
{
    /** @var array<int, string> */
    public array $synonymGroups = [];

    public string $stopWords = '';

    public function mount(): void
    {
        Gate::authorize('manage-store-settings');
        $settings = DB::table('search_settings')->where('store_id', $this->store()->id)->first();
        $groups = json_decode((string) ($settings->synonyms_json ?? '[]'), true);
        $stopWords = json_decode((string) ($settings->stop_words_json ?? '[]'), true);
        $this->synonymGroups = collect(is_array($groups) ? $groups : [])
            ->map(static fn (mixed $group): string => is_array($group) ? implode(', ', $group) : '')
            ->values()
            ->all();
        $this->stopWords = implode(', ', is_array($stopWords) ? $stopWords : []);
    }

    public function addSynonymGroup(): void
    {
        Gate::authorize('manage-store-settings');

        if (count($this->synonymGroups) >= 20) {
            $this->addError('synonymGroups', 'You can define up to 20 synonym groups.');

            return;
        }

        $this->synonymGroups[] = '';
    }

    public function removeSynonymGroup(int $index): void
    {
        Gate::authorize('manage-store-settings');
        unset($this->synonymGroups[$index]);
        $this->synonymGroups = array_values($this->synonymGroups);
        $this->resetValidation('synonymGroups');
    }

    public function save(): void
    {
        Gate::authorize('manage-store-settings');
        $this->validate([
            'synonymGroups' => ['array', 'max:20'],
            'synonymGroups.*' => ['nullable', 'string', 'max:500'],
            'stopWords' => ['nullable', 'string', 'max:2000'],
        ]);

        $synonyms = [];
        $seenWords = [];

        foreach ($this->synonymGroups as $index => $rawGroup) {
            $words = collect(explode(',', $rawGroup))
                ->map(static fn (string $word): string => trim($word))
                ->filter()
                ->unique(fn (string $word): string => mb_strtolower($word))
                ->values()
                ->all();

            if ($words === []) {
                continue;
            }

            if (count($words) < 2) {
                throw ValidationException::withMessages(["synonymGroups.{$index}" => 'A synonym group needs at least two distinct words.']);
            }

            foreach ($words as $word) {
                $key = mb_strtolower($word);

                if (isset($seenWords[$key])) {
                    throw ValidationException::withMessages(["synonymGroups.{$index}" => 'Each word can only appear in one synonym group.']);
                }

                $seenWords[$key] = true;
            }

            $synonyms[] = $words;
        }

        $normalizedStopWords = collect(preg_split('/[,\s]+/u', $this->stopWords) ?: [])
            ->map(static fn (string $word): string => mb_strtolower(trim($word)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        DB::table('search_settings')->updateOrInsert(
            ['store_id' => $this->store()->id],
            [
                'synonyms_json' => json_encode($synonyms, JSON_THROW_ON_ERROR),
                'stop_words_json' => json_encode($normalizedStopWords, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ],
        );

        session()->flash('status', 'Search settings saved.');
    }

    public function reindex(): void
    {
        Gate::authorize('manage-store-settings');
        $storeId = $this->store()->id;
        DB::table('search_settings')->insertOrIgnore(['store_id' => $storeId]);
        $settings = DB::table('search_settings')->where('store_id', $storeId)->first();

        if (in_array($settings->index_status, ['queued', 'processing'], true)) {
            $this->addError('reindex', 'A search reindex is already in progress.');

            return;
        }

        DB::table('search_settings')->where('store_id', $storeId)->update([
            'index_status' => 'queued',
            'documents_count' => 0,
            'pending_updates' => DB::table('products')->where('store_id', $storeId)->count(),
        ]);

        ReindexStoreProducts::dispatch($storeId)->onQueue('search');
        session()->flash('status', 'Search reindex queued.');
    }

    public function refreshIndexStatus(): void
    {
        $this->authorizeSettings();
    }

    public function render(): mixed
    {
        $this->authorizeSettings();
        $storeId = $this->store()->id;
        $settings = DB::table('search_settings')->where('store_id', $storeId)->first();
        $indexStatus = $settings->index_status ?? 'ready';

        return view('livewire.admin.search.settings', [
            'indexStatus' => $indexStatus,
            'lastReindexedAt' => $settings?->last_reindex_at,
            'documentsCount' => (int) ($settings->documents_count ?? DB::table('products_fts')->where('store_id', (string) $storeId)->count()),
            'pendingUpdates' => (int) ($settings->pending_updates ?? 0),
            'progress' => (int) ((($settings->documents_count ?? 0) / max(1, ($settings->documents_count ?? 0) + ($settings->pending_updates ?? 0))) * 100),
        ])->layout('layouts.admin');
    }

    private function authorizeSettings(): void
    {
        Gate::authorize('manage-store-settings');
    }

    private function store(): Store
    {
        $store = app()->bound('current_store') ? app('current_store') : null;
        abort_unless($store instanceof Store, 404);

        return $store;
    }
}
