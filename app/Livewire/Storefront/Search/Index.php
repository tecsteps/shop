<?php

namespace App\Livewire\Storefront\Search;

use Livewire\Component;

class Index extends Component
{
    public string $query = '';

    public function mount(): void
    {
        $this->query = request()->query('q', '');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.storefront.search.index')
            ->layout('layouts.storefront.app', [
                'title' => 'Search',
            ]);
    }
}
