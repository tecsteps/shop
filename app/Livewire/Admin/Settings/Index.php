<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Component;

class Index extends Component
{
    public string $activeTab = 'general';

    public function mount(): void
    {
        $this->activeTab = request()->query('tab', 'general');
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.admin.settings.index')
            ->layout('layouts.admin', ['title' => 'Settings']);
    }
}
