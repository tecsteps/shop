<?php

namespace App\Livewire\Storefront\Search;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        if (! Schema::hasTable('products') || ! app()->bound('current_store') || trim($this->query) === '') {
            return new LengthAwarePaginator([], 0, 12, 1);
        }

        return DB::table('products')
            ->where('store_id', app('current_store')->id)
            ->where('status', 'active')
            ->where('title', 'like', '%'.$this->query.'%')
            ->orderBy('title')
            ->paginate(12, ['id', 'title', 'handle']);
    }
}
