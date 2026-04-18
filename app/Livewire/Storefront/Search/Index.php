<?php

namespace App\Livewire\Storefront\Search;

use App\Services\SearchService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.storefront')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $query = '';

    public function updatedQuery(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.storefront.search.index', [
            'results' => $this->searchProducts(),
        ]);
    }

    protected function searchProducts(): LengthAwarePaginator
    {
        if (! app()->bound('current_store') || trim($this->query) === '') {
            return new Paginator([], 0, 12, 1);
        }

        return app(SearchService::class)->search(
            app('current_store'),
            $this->query,
            [],
            12,
            $this->getPage()
        );
    }
}
