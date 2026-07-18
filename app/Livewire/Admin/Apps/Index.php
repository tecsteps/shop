<?php

namespace App\Livewire\Admin\Apps;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Apps')]
class Index extends Component
{
    public function render(): View
    {
        return view('livewire.admin.apps.index');
    }
}
