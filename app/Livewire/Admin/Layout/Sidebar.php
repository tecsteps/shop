<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;

class Sidebar extends Component
{
    public string $currentRoute = '';

    public function mount(): void
    {
        $this->currentRoute = request()->route()?->getName() ?? '';
    }

    public function render()
    {
        return view('livewire.admin.layout.sidebar');
    }
}
