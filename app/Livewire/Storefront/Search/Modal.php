<?php

namespace App\Livewire\Storefront\Search;

use App\Services\SearchService;
use Livewire\Component;

class Modal extends Component
{
    public string $query = '';

    public bool $open = false;

    public function updatedQuery(SearchService $search): void
    {
        if (! $this->open) {
            return;
        }

        $this->suggestions = $search->autocomplete(app('current_store'), $this->query)->map(fn ($product): array => ['id' => $product->getKey(), 'title' => $product->title, 'handle' => $product->handle])->all();
    }

    /** @var array<int, array{id: int, title: string, handle: string}> */
    public array $suggestions = [];

    public function render(): mixed
    {
        return view('livewire.storefront.search.modal');
    }
}
