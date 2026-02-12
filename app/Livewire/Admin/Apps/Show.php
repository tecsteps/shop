<?php

namespace App\Livewire\Admin\Apps;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('App Details')]
class Show extends Component
{
    public function render()
    {
        return view('livewire.admin.apps.show');
    }
}
