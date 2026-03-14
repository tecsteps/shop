<?php

namespace App\Livewire\Storefront\Search;

use Livewire\Component;

class Index extends Component
{
    public string $query = '';

    public function render(): mixed
    {
        return view('livewire.storefront.search.index')
            ->layout('layouts.storefront', ['title' => 'Search']);
    }
}
