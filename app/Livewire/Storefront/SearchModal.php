<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\ResolvesStore;
use App\Services\SearchService;
use Livewire\Attributes\On;
use Livewire\Component;

class SearchModal extends Component
{
    use ResolvesStore;

    public bool $open = false;

    public string $query = '';

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

    public function render()
    {
        $suggestions = collect();

        if ($this->open && trim($this->query) !== '') {
            $suggestions = app(SearchService::class)->autocomplete($this->store(), $this->query, 5);
        }

        return view('livewire.storefront.search-modal', ['suggestions' => $suggestions]);
    }
}
