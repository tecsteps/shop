<?php

namespace App\Livewire\Admin\Search;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Search Settings')]
class Settings extends Component
{
    public function render()
    {
        return view('livewire.admin.search.settings');
    }
}
