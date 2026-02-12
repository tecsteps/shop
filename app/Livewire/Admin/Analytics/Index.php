<?php

namespace App\Livewire\Admin\Analytics;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Analytics')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.admin.analytics.index');
    }
}
