<?php

namespace App\Livewire\Storefront\Collections;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Index extends Component
{
    public function render(): mixed
    {
        return view('livewire.storefront.collections.index');
    }
}
