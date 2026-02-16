<?php

namespace App\Livewire\Storefront\Search;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Index extends Component
{
    #[Url]
    public string $q = '';

    public function render(): mixed
    {
        return view('livewire.storefront.search.index');
    }
}
