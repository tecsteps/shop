<?php

namespace App\Livewire\Admin\Developers;

use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public function render(): View
    {
        return view('livewire.admin.developers.index')->layout('livewire.admin.layout.app', [
            'title' => 'Developers',
        ]);
    }
}
