<?php

namespace App\Livewire\Admin\Search;

use Livewire\Component;

class Settings extends Component
{
    public function render()
    {
        return view('livewire.admin.search.settings')
            ->layout('layouts.admin', ['title' => 'Search Settings']);
    }
}
