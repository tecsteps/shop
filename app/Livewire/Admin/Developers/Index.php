<?php

namespace App\Livewire\Admin\Developers;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Developers')]
class Index extends Component
{
    public function render(): View
    {
        return view('livewire.admin.developers.index');
    }
}
