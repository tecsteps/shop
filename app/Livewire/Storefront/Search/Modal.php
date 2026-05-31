<?php

namespace App\Livewire\Storefront\Search;

use App\Services\SearchService;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Header search modal with live product autocomplete.
 *
 * Opens on the `open-search-modal` window event dispatched by the layout header
 * and queries the {@see SearchService} (platform/#8) as the customer types
 * (debounced in the view). Product suggestions link to the product page; the
 * footer links to the full results page. Collection autocomplete is out of
 * scope for #8 (SearchService indexes products only), so that group is empty.
 */
class Modal extends Component
{
    public bool $open = false;

    public string $query = '';

    #[On('open-search-modal')]
    public function openModal(): void
    {
        $this->open = true;
    }

    #[On('close-search-modal')]
    public function closeModal(): void
    {
        $this->open = false;
        $this->query = '';
    }

    public function render(SearchService $search)
    {
        $products = strlen(trim($this->query)) >= 2
            ? $search->autocomplete(app('current_store'), $this->query, 6)
            : collect();

        return view('livewire.storefront.search.modal', [
            'products' => $products,
            'collections' => collect(),
        ]);
    }
}
