<?php

namespace App\Livewire\Storefront\Search;

use App\Services\SearchService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.storefront')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function render(): mixed
    {
        $products = null;

        if (strlen(trim($this->q)) >= 2) {
            $store = app('current_store');
            $searchService = app(SearchService::class);
            $products = $searchService->search($store, $this->q);
        }

        return view('livewire.storefront.search.index', [
            'products' => $products,
        ]);
    }
}
