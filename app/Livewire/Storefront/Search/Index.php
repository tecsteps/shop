<?php

namespace App\Livewire\Storefront\Search;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Index extends Component
{
    public string $query = '';

    public function render(): View
    {
        return view('livewire.storefront.search.index');
    }
}
