<?php

namespace App\Livewire\Storefront\Search;

use App\Models\Store;
use App\Services\SearchService;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Search-as-you-type modal (spec 04 §11.1). The open/close shell is Alpine
 * (triggered by the header search icon via the `open-search-modal` browser
 * event); suggestions are computed server-side on each debounced keystroke.
 */
class Modal extends Component
{
    /**
     * Suggestions per category in the modal.
     */
    public const LIMIT = 5;

    public string $query = '';

    /**
     * Enter submits the full search page.
     */
    public function search()
    {
        return $this->redirectRoute('storefront.search', ['q' => $this->query]);
    }

    /**
     * Render the modal with autocomplete suggestions for the current query.
     */
    public function render(): View
    {
        $suggestions = collect();

        if (trim($this->query) !== '') {
            /** @var Store $store */
            $store = app('current_store');

            $suggestions = app(SearchService::class)->autocomplete($store, $this->query, self::LIMIT);
        }

        return view('livewire.storefront.search.modal', [
            'suggestions' => $suggestions,
            'products' => $suggestions->where('type', 'product'),
            'collections' => $suggestions->where('type', 'collection'),
            'pastQueries' => $suggestions->where('type', 'query'),
        ]);
    }
}
