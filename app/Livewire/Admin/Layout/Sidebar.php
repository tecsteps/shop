<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Attributes\Computed;
use Livewire\Component;

class Sidebar extends Component
{
    public bool $collapsed = false;

    public function toggle(): void
    {
        $this->collapsed = ! $this->collapsed;
    }

    #[Computed]
    public function currentRoute(): ?string
    {
        return request()->route()?->getName();
    }

    public function render()
    {
        return view('livewire.admin.layout.sidebar');
    }
}
