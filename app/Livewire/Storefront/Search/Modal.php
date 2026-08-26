<?php

namespace App\Livewire\Storefront\Search;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Collection;
use App\Services\SearchService;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Modal extends Component
{
    use InteractsWithStore;

    public bool $open = false;

    public string $query = '';

    #[On('open-search-modal')]
    public function openModal(): void
    {
        $this->open = true;
    }

    public function closeModal(): void
    {
        $this->open = false;
    }

    public function updatedQuery(): void
    {
        // Computed results re-render automatically; nothing else to do here.
    }

    #[Computed]
    public function productResults(): SupportCollection
    {
        if (trim($this->query) === '') {
            return collect();
        }

        return app(SearchService::class)
            ->autocomplete($this->store(), $this->query, 5)
            ->load(['variants', 'media' => fn ($media) => $media->where('status', 'ready')->orderBy('position')]);
    }

    #[Computed]
    public function collectionResults(): SupportCollection
    {
        if (trim($this->query) === '') {
            return collect();
        }

        return Collection::where('status', 'active')
            ->where('title', 'like', $this->query.'%')
            ->orderBy('title')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function hasResults(): bool
    {
        return $this->productResults->isNotEmpty() || $this->collectionResults->isNotEmpty();
    }
}
