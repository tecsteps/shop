<?php

namespace App\Livewire\Storefront;

use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Home extends Component
{
    public function render(): View
    {
        return view('livewire.storefront.home');
    }
}
