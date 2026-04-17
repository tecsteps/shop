<?php

namespace App\Livewire\Storefront\Search;

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

    #[On('close-search-modal')]
    public function closeModal(): void
    {
        $this->open = false;
        $this->query = '';
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.storefront.search.modal');
    }
}
