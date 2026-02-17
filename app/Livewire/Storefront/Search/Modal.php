<?php

namespace App\Livewire\Storefront\Search;

use App\Services\SearchService;
use Illuminate\Support\Collection;
use Livewire\Component;

class Modal extends Component
{
    public string $query = '';

    /** @var Collection<int, \App\Models\Product> */
    public Collection $suggestions;

    public function mount(): void
    {
        $this->suggestions = collect();
    }

    public function updatedQuery(): void
    {
        if (mb_strlen(trim($this->query)) < 2 || ! app()->bound('current_store')) {
            $this->suggestions = collect();

            return;
        }

        $store = app('current_store');
        $searchService = app(SearchService::class);

        $this->suggestions = $searchService->autocomplete($store, $this->query, 5);
    }

    public function goToSearch(): string
    {
        return $this->redirect(
            route('storefront.search', ['q' => $this->query]),
            navigate: true,
        );
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.storefront.search.modal');
    }
}
