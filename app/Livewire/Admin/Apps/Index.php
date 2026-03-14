<?php

namespace App\Livewire\Admin\Apps;

use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        return view('livewire.admin.apps.index')
            ->layout('layouts.admin', ['title' => 'Apps']);
    }
}
