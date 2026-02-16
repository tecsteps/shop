<?php

namespace App\Livewire\Admin\Developers;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.admin.layout', ['title' => 'Developers'])]
class Index extends Component
{
    public function render(): mixed
    {
        return view('livewire.admin.developers.index');
    }
}
