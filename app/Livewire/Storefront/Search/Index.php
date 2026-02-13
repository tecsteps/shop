<?php

namespace App\Livewire\Storefront\Search;

use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public string $query = '';

    public function mount(): void
    {
        $this->query = request()->get('q', '');
    }

    public function render(): View
    {
        return view('livewire.storefront.search.index')
            ->layout('storefront.layouts.app', [
                'title' => 'Search',
            ]);
    }
}
