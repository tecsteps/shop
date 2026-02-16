<?php

namespace App\Livewire\Storefront;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Home extends Component
{
    public function render(): mixed
    {
        return view('livewire.storefront.home');
    }
}
