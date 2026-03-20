<?php

namespace App\Livewire\Storefront\Search;

use App\Models\Store;
use App\Services\SearchService;
use Livewire\Attributes\On;
use Livewire\Component;

class Modal extends Component
{
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
        $this->query = '';
    }

    public function search(): void
    {
        if ($this->query) {
            $this->redirect('/search?q='.urlencode($this->query));
        }
    }

    public function render(): mixed
    {
        $suggestions = collect();

        if (mb_strlen($this->query) >= 2 && app()->bound('current_store')) {
            $store = app('current_store');
            if ($store instanceof Store) {
                $suggestions = app(SearchService::class)->autocomplete($store, $this->query, 5);
            }
        }

        return view('livewire.storefront.search.modal', [
            'suggestions' => $suggestions,
        ]);
    }
}
