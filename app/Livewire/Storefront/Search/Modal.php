<?php

namespace App\Livewire\Storefront\Search;

use App\Models\Collection;
use App\Models\Product;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Search-as-you-type modal (spec 04 section 11.1): opened from the header
 * search icon, autocompletes products and collections with a 300ms
 * debounce, max 5 results per category, and a "view all" link to the full
 * search results page.
 */
class Modal extends Component
{
    public string $query = '';

    public function render(SearchService $search): View
    {
        $hasQuery = mb_strlen(trim($this->query)) >= SearchService::MIN_PREFIX_LENGTH;

        return view('livewire.storefront.search.modal', [
            'hasQuery' => $hasQuery,
            'products' => $hasQuery ? $this->productSuggestions($search) : [],
            'collections' => $hasQuery ? $this->collectionSuggestions() : [],
            'totalResults' => $hasQuery ? $search->countMatches($this->store(), $this->query) : 0,
        ]);
    }

    /**
     * @return list<array{title: string, handle: string, price_amount: int, currency: string, image_url: string|null}>
     */
    protected function productSuggestions(SearchService $search): array
    {
        return $search->autocomplete($this->store(), $this->query, 5)
            ->map(function (Product $product): array {
                $variant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();
                $media = $product->media->first();

                return [
                    'title' => $product->title,
                    'handle' => $product->handle,
                    'price_amount' => $variant?->price_amount ?? 0,
                    'currency' => $variant?->currency ?? ($this->store()->default_currency ?? 'EUR'),
                    'image_url' => $media !== null ? Storage::disk('public')->url($media->storage_key) : null,
                ];
            })
            ->all();
    }

    /**
     * @return list<array{title: string, handle: string}>
     */
    protected function collectionSuggestions(): array
    {
        return Collection::query()
            ->published()
            ->where('title', 'like', trim($this->query).'%')
            ->orderBy('title')
            ->limit(5)
            ->get()
            ->map(fn (Collection $collection): array => [
                'title' => $collection->title,
                'handle' => $collection->handle,
            ])
            ->all();
    }

    protected function store(): Store
    {
        return app('current_store');
    }
}
