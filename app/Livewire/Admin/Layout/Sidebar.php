<?php

namespace App\Livewire\Admin\Layout;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class Sidebar extends Component
{
    public bool $collapsed = true;

    public function toggle(): void
    {
        $this->collapsed = ! $this->collapsed;
    }

    public function render(): View
    {
        return view('livewire.admin.layout.sidebar', ['currentRoute' => request()->route()?->getName() ?? '']);
    }
}
