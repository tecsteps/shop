<?php

namespace App\Livewire\Admin\Analytics;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.admin.layout', ['title' => 'Analytics'])]
class Index extends Component
{
    public function render(): mixed
    {
        return view('livewire.admin.analytics.index');
    }
}
