<?php

namespace App\Livewire\Admin\Themes;

use Livewire\Component;

class Index extends Component
{
    public function render(): mixed
    {
        return view('livewire.admin.themes.index')
            ->layout('layouts.admin', ['breadcrumbs' => [['label' => 'Themes']]]);
    }
}
