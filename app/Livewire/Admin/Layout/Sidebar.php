<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;

class Sidebar extends Component
{
    public bool $collapsed = false;

    public function toggle(): void
    {
        $this->collapsed = ! $this->collapsed;
    }

    public function render()
    {
        return view('livewire.admin.layout.sidebar', [
            'currentRoute' => request()->route()?->getName() ?? '',
        ]);
    }
}
