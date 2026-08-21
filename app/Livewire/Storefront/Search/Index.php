<?php

namespace App\Livewire\Storefront\Search;

use App\Services\SearchService;
use Livewire\Component;

class Index extends Component
{
    public string $query = '';

    public function mount(?string $q = null): void
    {
        $this->query = $q ?? request()->string('q')->toString();
    }

    public function render(SearchService $search): mixed
    {
        $products = $search->search(app('current_store'), $this->query, [], 12);

        return view('livewire.storefront.search.index', compact('products'))->layout('layouts.storefront');
    }
}
