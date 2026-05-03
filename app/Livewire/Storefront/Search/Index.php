<?php

namespace App\Livewire\Storefront\Search;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public string $q = '';

    protected $queryString = [
        'q' => ['except' => ''],
    ];

    public function render(): View
    {
        $query = Product::query()
            ->with('variants', 'media')
            ->where('status', ProductStatus::Active)
            ->whereNotNull('published_at');

        if (trim($this->q) !== '') {
            $term = '%'.str_replace('%', '\\%', trim($this->q)).'%';

            $query->where(function ($query) use ($term): void {
                $query->where('title', 'like', $term)
                    ->orWhere('vendor', 'like', $term)
                    ->orWhere('product_type', 'like', $term);
            });
        }

        return view('livewire.storefront.search.index', [
            'products' => $query->latest('published_at')->get(),
        ])->layout('storefront.layouts.app', [
            'title' => 'Search',
        ]);
    }
}
