<?php

namespace App\Livewire\Storefront\Search;

use App\Services\SearchService;
use Livewire\Component;

class Index extends Component
{
    public string $query = '';

    public function mount(): void
    {
        $this->query = request()->query('q', '');
    }

    public function render(): \Illuminate\View\View
    {
        $results = collect();

        if ($this->query !== '' && app()->bound('current_store')) {
            $store = app('current_store');
            $searchService = app(SearchService::class);
            $results = $searchService->search($store, $this->query);
        }

        return view('livewire.storefront.search.index', [
            'results' => $results,
        ])->layout('layouts.storefront.app', [
            'title' => 'Search',
        ]);
    }
}
