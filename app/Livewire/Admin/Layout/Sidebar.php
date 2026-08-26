<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;

class Sidebar extends Component
{
    public bool $collapsed = false;

    /**
     * Toggle the desktop collapsed (icon-only) state.
     */
    public function toggle(): void
    {
        $this->collapsed = ! $this->collapsed;
    }

    public function render()
    {
        return view('livewire.admin.layout.sidebar');
    }
}
