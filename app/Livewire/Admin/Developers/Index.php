<?php

namespace App\Livewire\Admin\Developers;

use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        return view('livewire.admin.developers.index')
            ->layout('layouts.admin', ['title' => 'Developers']);
    }
}
