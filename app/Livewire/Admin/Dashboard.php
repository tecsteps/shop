<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.dashboard');
    }
}
