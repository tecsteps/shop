<?php

namespace App\Livewire\Admin\Apps;

use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public string $installation;

    public function mount(string $installation): void
    {
        $this->installation = $installation;
    }

    public function render(): View
    {
        return view('livewire.admin.apps.show')->layout('livewire.admin.layout.app', [
            'title' => 'App detail',
        ]);
    }
}
