<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Attributes\On;
use Livewire\Component;

class Sidebar extends Component
{
    public bool $collapsed = true;

    public string $currentRoute = '';

    public function mount(): void
    {
        $this->currentRoute = request()->route()?->getName() ?? '';
    }

    #[On('toggle-sidebar')]
    public function toggle(): void
    {
        $this->collapsed = ! $this->collapsed;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.layout.sidebar');
    }
}
