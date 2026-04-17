<?php

namespace App\Livewire\Storefront\Search;

use App\Models\Product;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Index extends Component
{
    #[Url(as: 'q')]
    public string $query = '';

    public function render(SearchService $search): View
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        /** @var Collection<int, Product> $results */
        $results = $store instanceof Store && trim($this->query) !== ''
            ? $search->search($store, $this->query, [], session()->getId())
            : Product::hydrate([]);

        return view('livewire.storefront.search.index', [
            'results' => $results,
        ]);
    }
}
