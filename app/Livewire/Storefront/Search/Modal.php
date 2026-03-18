<?php

namespace App\Livewire\Storefront\Search;

use Illuminate\View\View;
use Livewire\Component;

class Modal extends Component
{
    public bool $isOpen = false;

    public string $query = '';

    public function open(): void
    {
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->query = '';
    }

    public function render(): View
    {
        return view('livewire.storefront.search.modal');
    }
}
