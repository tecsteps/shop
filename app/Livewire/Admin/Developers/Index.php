<?php

namespace App\Livewire\Admin\Developers;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Developer Settings')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.admin.developers.index');
    }
}
