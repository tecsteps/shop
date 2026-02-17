<?php

namespace App\Livewire\Admin\Developers;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Developers')]
class Index extends Component
{
    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.developers.index');
    }
}
