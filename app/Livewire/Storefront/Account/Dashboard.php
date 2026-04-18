<?php

namespace App\Livewire\Storefront\Account;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.storefront.account.dashboard');
    }
}
