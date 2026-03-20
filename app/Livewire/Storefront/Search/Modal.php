<?php

namespace App\Livewire\Storefront\Search;

use Livewire\Component;

class Modal extends Component
{
    public bool $open = false;

    public string $query = '';

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
        return view('livewire.storefront.search.modal');
    }
}
