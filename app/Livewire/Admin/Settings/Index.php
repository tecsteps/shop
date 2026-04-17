<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Component;

class Index extends Component
{
    public function render(): mixed
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        return view('livewire.admin.settings.index', ['store' => $store])
            ->layout('layouts.admin.app', ['title' => 'Settings']);
    }
}
