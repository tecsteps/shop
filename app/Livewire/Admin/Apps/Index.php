<?php

namespace App\Livewire\Admin\Apps;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Apps')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.admin.apps.index');
    }
}
