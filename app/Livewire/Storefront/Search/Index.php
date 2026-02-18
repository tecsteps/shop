<?php

namespace App\Livewire\Storefront\Search;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Index extends Component
{
    public string $query = '';

    public function mount(): void
    {
        $q = request()->query('q', '');

        $this->query = is_string($q) ? $q : '';
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.storefront.search.index');
    }
}
