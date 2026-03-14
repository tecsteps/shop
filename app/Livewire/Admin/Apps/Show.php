<?php

namespace App\Livewire\Admin\Apps;

use Livewire\Component;

class Show extends Component
{
    public function render()
    {
        return view('livewire.admin.apps.show')
            ->layout('layouts.admin', ['title' => 'App Details']);
    }
}
