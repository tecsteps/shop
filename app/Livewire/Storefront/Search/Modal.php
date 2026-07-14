<?php

namespace App\Livewire\Storefront\Search;

use App\Livewire\Storefront\StorefrontComponent;
use App\Models\Collection;
use App\Services\SearchService;
use Illuminate\View\View;
use Livewire\Attributes\On;

class Modal extends StorefrontComponent
{
    public bool $open = false;

    public string $query = '';

    /** @var array<int, array<string, mixed>> */
    public array $products = [];

    /** @var array<int, array<string, mixed>> */
    public array $collections = [];

    #[On('open-search-modal')]
    public function openModal(): void
    {
        $this->open = true;
    }

    #[On('close-search-modal')]
    public function closeModal(): void
    {
        $this->open = false;
    }

    public function updatedQuery(): void
    {
        $query = trim($this->query);
        if (mb_strlen($query) < 2) {
            $this->reset('products', 'collections');

            return;
        }

        $products = app(SearchService::class)->autocomplete($this->currentStore(), $query, 5);
        $this->products = collect($products)->take(5)->map(function ($product): array {
            $product->loadMissing(['variants', 'media']);
            $variant = $product->variants->firstWhere('is_default', true) ?: $product->variants->first();
            $media = $product->media->first();

            return [
                'title' => $product->title,
                'handle' => $product->handle,
                'price' => (int) ($variant?->price_amount ?? 0),
                'currency' => (string) ($variant?->currency ?? $this->currentStore()->default_currency),
                'image' => $media?->storage_key,
            ];
        })->values()->all();

        $this->collections = Collection::query()
            ->where('store_id', $this->currentStore()->getKey())
            ->where('status', 'active')
            ->where('title', 'like', '%'.$query.'%')
            ->limit(5)
            ->get(['title', 'handle'])
            ->map(fn ($collection) => ['title' => $collection->title, 'handle' => $collection->handle])
            ->all();
    }

    public function goToResults(): mixed
    {
        $query = trim($this->query);
        if ($query === '') {
            $this->addError('query', 'Enter a search term.');

            return null;
        }

        $this->open = false;

        return $this->redirect(url('/search').'?q='.rawurlencode($query), navigate: true);
    }

    public function render(): View
    {
        return view('storefront.components.search-modal');
    }
}
