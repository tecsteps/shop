<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;

class Sidebar extends Component
{
    public bool $collapsed = true;

    public string $currentRoute = '';

    public function mount(): void
    {
        $this->currentRoute = request()->route()?->getName() ?? '';
    }

    public function toggle(): void
    {
        $this->collapsed = ! $this->collapsed;
    }

    public function close(): void
    {
        $this->collapsed = true;
    }

    public function render()
    {
        return view('livewire.admin.layout.sidebar');
    }
}
