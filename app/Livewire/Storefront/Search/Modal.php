<?php

namespace App\Livewire\Storefront\Search;

use App\Services\SearchService;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Modal extends Component
{
    public bool $open = false;

    public string $searchQuery = '';

    #[On('open-search-modal')]
    public function openModal(): void
    {
        $this->open = true;
        $this->searchQuery = '';
    }

    #[On('close-search-modal')]
    public function closeModal(): void
    {
        $this->open = false;
        $this->searchQuery = '';
    }

    public function render(): View
    {
        $results = collect();

        if ($this->open && strlen($this->searchQuery) >= 2) {
            $store = app()->bound('current_store') ? app('current_store') : null;

            if ($store) {
                $searchService = app(SearchService::class);
                $results = $searchService->autocomplete($store, $this->searchQuery, 5);
            }
        }

        return view('livewire.storefront.search.modal', [
            'results' => $results,
        ]);
    }
}
