<?php

namespace App\Livewire\Admin\Search;

use App\Models\Product;
use App\Models\SearchSetting;
use App\Services\SearchService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Settings extends Component
{
    public bool $enabled = true;

    public string $synonyms = '';

    public string $stopwords = '';

    public string $message = '';

    public function mount(): void
    {
        $settings = SearchSetting::query()->first();
        $this->enabled = $settings?->enabled ?? true;
        $this->synonyms = collect($settings?->synonyms ?? [])->map(fn ($group): string => is_array($group) ? implode(',', $group) : (string) $group)->implode("\n");
        $this->stopwords = implode("\n", $settings?->stopwords ?? []);
    }

    public function save(): void
    {
        $this->authorizeStoreManager();
        $data = $this->validate(['enabled' => ['boolean'], 'synonyms' => ['nullable', 'string'], 'stopwords' => ['nullable', 'string']]);
        SearchSetting::query()->updateOrCreate(['store_id' => app('current_store')->getKey()], ['enabled' => $data['enabled'], 'synonyms' => collect(preg_split('/\R/', $data['synonyms'] ?? '', -1, PREG_SPLIT_NO_EMPTY))->map(fn (string $line): array => array_values(array_filter(array_map('trim', explode(',', $line)))))->filter()->values()->all(), 'stopwords' => array_values(array_filter(array_map('trim', preg_split('/\R/', $data['stopwords'] ?? '', -1, PREG_SPLIT_NO_EMPTY))))]);
        $this->message = 'Search settings saved.';
    }

    public function reindex(SearchService $search): void
    {
        $this->authorizeStoreManager();
        Product::query()->each(fn (Product $product) => $search->syncProduct($product));
        $this->message = 'Search index rebuilt.';
    }

    public function render(): View
    {
        return view('livewire.admin.search.settings')->layout('layouts.admin');
    }

    private function authorizeStoreManager(): void
    {
        abort_unless(auth()->user()?->canManageStore(app('current_store')), 403);
    }
}
