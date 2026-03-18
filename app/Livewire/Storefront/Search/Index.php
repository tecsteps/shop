<?php

namespace App\Livewire\Storefront\Search;

use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Search')]
class Index extends Component
{
    public string $query = '';

    public function render(): View
    {
        return view('livewire.storefront.search.index')
            ->layout('storefront.layouts.app', ['title' => 'Search']);
    }
}
