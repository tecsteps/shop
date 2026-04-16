<?php

namespace App\Livewire\Storefront\Search;

use App\Models\Product;
use App\Models\SearchQuery;
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
        $q = trim($this->query);

        $results = Product::query()
            ->published()
            ->when($q !== '', function ($builder) use ($q) {
                $like = '%'.$q.'%';
                $builder->where(function ($inner) use ($like) {
                    $inner->where('title', 'like', $like)
                        ->orWhere('description_html', 'like', $like)
                        ->orWhere('vendor', 'like', $like)
                        ->orWhere('product_type', 'like', $like);
                });
            })
            ->with('variants', 'media')
            ->paginate(12);

        if ($q !== '') {
            SearchQuery::create([
                'store_id' => app('current_store')->id,
                'query' => $q,
                'results_count' => $results->total(),
            ]);
        }

        return view('livewire.storefront.search.index', compact('results'))->title('Search');
    }
}
