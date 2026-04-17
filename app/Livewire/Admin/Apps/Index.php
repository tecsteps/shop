<?php

namespace App\Livewire\Admin\Apps;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.apps.index');
    }
}
