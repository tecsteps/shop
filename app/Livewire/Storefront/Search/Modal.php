<?php

namespace App\Livewire\Storefront\Search;

use App\Services\SearchService;
use Livewire\Component;

class Modal extends Component
{
    public bool $open = false;

    public string $query = '';

    /**
     * @var array<int, array{id: int, title: string, handle: string}>
     */
    public array $results = [];

    public function toggle(): void
    {
        $this->open = ! $this->open;

        if (! $this->open) {
            $this->query = '';
            $this->results = [];
        }
    }

    public function updatedQuery(): void
    {
        $this->results = $this->searchProducts();
    }

    public function render()
    {
        return view('livewire.storefront.search.modal');
    }

    /**
     * @return array<int, array{id: int, title: string, handle: string}>
     */
    protected function searchProducts(): array
    {
        if (! app()->bound('current_store') || trim($this->query) === '') {
            return [];
        }

        return app(SearchService::class)
            ->autocomplete(app('current_store'), $this->query, 8)
            ->map(fn ($p): array => [
                'id' => (int) $p->id,
                'title' => $p->title,
                'handle' => $p->handle,
            ])
            ->all();
    }
}
