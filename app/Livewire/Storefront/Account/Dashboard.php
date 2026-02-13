<?php

namespace App\Livewire\Storefront\Account;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.storefront.account.dashboard');
    }
}
