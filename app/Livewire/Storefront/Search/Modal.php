<?php

namespace App\Livewire\Storefront\Search;

use App\Services\SearchService;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Modal extends Component
{
    public bool $isOpen = false;

    public string $q = '';

    #[On('open-search-modal')]
    public function show(): void
    {
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->q = '';
    }

    public function render(): View
    {
        $suggestions = $this->isOpen
            ? app(SearchService::class)->autocomplete(app('current_store'), $this->q, 6)
            : collect();

        return view('livewire.storefront.search.modal', [
            'suggestions' => $suggestions,
        ]);
    }
}
