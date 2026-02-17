<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;

class Sidebar extends Component
{
    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.layout.sidebar');
    }
}
