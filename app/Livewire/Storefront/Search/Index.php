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
        $this->query = request()->query('q', '');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.storefront.search.index');
    }
}
